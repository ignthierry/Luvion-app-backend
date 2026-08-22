<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request)
    {
        $query = Appointment::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%")
                  ->orWhere('agenda', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->get();

        return response()->json($appointments);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'appointment_date' => 'required|date',
            'agenda' => 'required|string|max:255',
            'status' => 'nullable|string|in:PENDING_CONFIRMATION,CONFIRMED,CANCELLED',
            'session_id' => 'nullable|string|max:100',
        ]);

        if (empty($validated['session_id'])) {
            $validated['session_id'] = 'MANUAL-' . strtoupper(substr(md5(uniqid()), 0, 8));
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'PENDING_CONFIRMATION';
        }

        $validated['created_at'] = now();

        $appointment = Appointment::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Janji temu berhasil ditambahkan.',
            'data' => $appointment,
        ], 201);
    }

    /**
     * Update an appointment.
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string|in:PENDING_CONFIRMATION,CONFIRMED,CANCELLED',
            'customer_name' => 'nullable|string|max:100',
            'appointment_date' => 'nullable|date',
            'agenda' => 'nullable|string|max:255',
        ]);

        $appointment->update(array_filter($validated, function ($value) {
            return !is_null($value);
        }));

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment berhasil diperbarui.',
            'data' => $appointment,
        ]);
    }

    /**
     * Remove an appointment.
     */
    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment berhasil dihapus.',
        ]);
    }

    /**
     * Sync events from Google Calendar iCal Feed URL into Database.
     */
    public function syncGoogleCalendar(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string',
        ]);

        $url = trim($validated['url']);
        // Convert webcal:// to https://
        if (str_starts_with(strtolower($url), 'webcal://')) {
            $url = 'https://' . substr($url, 9);
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Luvion-Calendar-Sync/1.0',
                ])
                ->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengunduh kalender dari Google Calendar. Status code: ' . $response->status() . '. Pastikan link iCal publik atau secret address valid.',
                ], 400);
            }

            $icsContent = $response->body();
            $result = $this->parseAndSaveIcsEvents($icsContent);

            return response()->json([
                'status' => 'success',
                'message' => "Sinkronisasi berhasil! {$result['new_count']} event baru ditambahkan, {$result['updated_count']} event diperbarui.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Google Calendar Sync Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data kalender: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import .ics file from Google Calendar export.
     */
    public function importIcs(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file',
            'ics_content' => 'nullable|string',
        ]);

        try {
            $icsContent = '';
            if ($request->hasFile('file')) {
                $icsContent = file_get_contents($request->file('file')->getRealPath());
            } elseif ($request->filled('ics_content')) {
                $icsContent = $request->input('ics_content');
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File .ics atau konten iCal tidak ditemukan.',
                ], 400);
            }

            $result = $this->parseAndSaveIcsEvents($icsContent);

            return response()->json([
                'status' => 'success',
                'message' => "Import berhasil! {$result['new_count']} event baru ditambahkan, {$result['updated_count']} event diperbarui.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengimpor file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook endpoint for Google Calendar / Google Apps Script / n8n push notifications.
     */
    public function handleGoogleWebhook(Request $request)
    {
        $data = $request->all();

        if (empty($data['start']) && empty($data['appointment_date'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field start atau appointment_date wajib disertakan.',
            ], 400);
        }

        try {
            $dateStr = $data['appointment_date'] ?? $data['start'];
            $appointmentDate = Carbon::parse($dateStr)->setTimezone('Asia/Jakarta');

            $uid = $data['id'] ?? $data['uid'] ?? uniqid('gcal_');
            $sessionId = 'GCAL-' . substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $uid), 0, 50);

            $summary = $data['summary'] ?? $data['title'] ?? 'Janji Temu Google Calendar';
            $description = $data['description'] ?? $data['agenda'] ?? $summary;

            // Extract customer name & agenda
            $extracted = $this->extractCustomerAndAgenda($summary, $description);

            $customerName = $data['customer_name'] ?? $extracted['customer_name'];
            $agenda = $data['agenda'] ?? $extracted['agenda'];

            $status = 'CONFIRMED';
            if (isset($data['status'])) {
                $st = strtoupper($data['status']);
                if ($st === 'CANCELLED' || $st === 'CANCELED') $status = 'CANCELLED';
                elseif ($st === 'TENTATIVE') $status = 'PENDING_CONFIRMATION';
            }

            $appointment = Appointment::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'customer_name' => substr($customerName, 0, 100),
                    'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                    'agenda' => substr($agenda, 0, 255),
                    'status' => $status,
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Event Google Calendar berhasil disinkronkan ke database.',
                'data' => $appointment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse iCalendar string and save/update database records.
     */
    private function parseAndSaveIcsEvents(string $icsContent): array
    {
        // Unfold lines (RFC 5545)
        $unfolded = preg_replace("/\r\n[ \t]/", '', $icsContent);
        $unfolded = preg_replace("/\n[ \t]/", '', $unfolded);

        // Find all VEVENT blocks
        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $unfolded, $matches);

        $events = $matches[1] ?? [];
        $newCount = 0;
        $updatedCount = 0;
        $processed = [];

        foreach ($events as $eventBlock) {
            // Extract UID
            $uid = '';
            if (preg_match('/UID:(.+)/i', $eventBlock, $m)) {
                $uid = trim($m[1]);
            }
            if (empty($uid)) {
                $uid = md5($eventBlock);
            }
            $sessionId = 'GCAL-' . substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $uid), 0, 50);

            // Extract Summary
            $summary = '';
            if (preg_match('/SUMMARY:(.+)/i', $eventBlock, $m)) {
                $summary = $this->unescapeIcs(trim($m[1]));
            }

            // Extract Description
            $description = '';
            if (preg_match('/DESCRIPTION:(.+)/i', $eventBlock, $m)) {
                $description = $this->unescapeIcs(trim($m[1]));
            }

            // Extract DTSTART
            $appointmentDate = null;
            if (preg_match('/DTSTART(?:;[^:]+)?:([0-9TZ]+)/i', $eventBlock, $m)) {
                $rawDt = trim($m[1]);
                $appointmentDate = $this->parseIcsDateTime($rawDt, $eventBlock);
            }

            if (!$appointmentDate) {
                // Skip events without valid start date
                continue;
            }

            // Extract Status
            $status = 'CONFIRMED';
            if (preg_match('/STATUS:(.+)/i', $eventBlock, $m)) {
                $st = strtoupper(trim($m[1]));
                if ($st === 'CANCELLED') {
                    $status = 'CANCELLED';
                } elseif ($st === 'TENTATIVE') {
                    $status = 'PENDING_CONFIRMATION';
                }
            }

            // Extract Customer Name and Agenda
            $extracted = $this->extractCustomerAndAgenda($summary, $description);

            $customerName = substr($extracted['customer_name'], 0, 100);
            $agenda = substr($extracted['agenda'], 0, 255);

            // Check if exists
            $existing = Appointment::where('session_id', $sessionId)->first();

            if ($existing) {
                $existing->update([
                    'customer_name' => $customerName,
                    'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                    'agenda' => $agenda,
                    'status' => $status,
                ]);
                $updatedCount++;
                $processed[] = [
                    'action' => 'updated',
                    'customer_name' => $customerName,
                    'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                    'agenda' => $agenda,
                ];
            } else {
                Appointment::create([
                    'session_id' => $sessionId,
                    'customer_name' => $customerName,
                    'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                    'agenda' => $agenda,
                    'status' => $status,
                    'created_at' => now(),
                ]);
                $newCount++;
                $processed[] = [
                    'action' => 'created',
                    'customer_name' => $customerName,
                    'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                    'agenda' => $agenda,
                ];
            }
        }

        return [
            'total_found' => count($events),
            'new_count' => $newCount,
            'updated_count' => $updatedCount,
            'events' => $processed,
        ];
    }

    /**
     * Parse date string from iCal format into Carbon instance in Asia/Jakarta timezone.
     */
    private function parseIcsDateTime(string $rawDt, string $eventBlock): ?Carbon
    {
        try {
            // Check for UTC format e.g. 20260825T140000Z
            if (str_ends_with($rawDt, 'Z')) {
                $clean = rtrim($rawDt, 'Z');
                return Carbon::createFromFormat('Ymd\THis', $clean, 'UTC')->setTimezone('Asia/Jakarta');
            }

            // Check for full datetime format Ymd\THis
            if (strpos($rawDt, 'T') !== false) {
                // If TZID is specified in event block
                $tz = 'Asia/Jakarta';
                if (preg_match('/DTSTART;TZID=([^:]+):/i', $eventBlock, $tzMatch)) {
                    $tz = trim($tzMatch[1]);
                }
                return Carbon::createFromFormat('Ymd\THis', $rawDt, $tz)->setTimezone('Asia/Jakarta');
            }

            // Date only format Ymd e.g. 20260825 (All day event - default 09:00:00)
            if (strlen($rawDt) === 8) {
                return Carbon::createFromFormat('Ymd', $rawDt, 'Asia/Jakarta')->setTime(9, 0, 0);
            }

            return Carbon::parse($rawDt)->setTimezone('Asia/Jakarta');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($rawDt)->setTimezone('Asia/Jakarta');
            } catch (\Exception $ex) {
                return null;
            }
        }
    }

    /**
     * Extract customer name & agenda smartly from summary & description.
     */
    private function extractCustomerAndAgenda(string $summary, string $description): array
    {
        $customerName = 'Google Calendar User';
        $agenda = $summary ?: 'Janji Temu Google Calendar';

        if (empty($summary) && !empty($description)) {
            $summary = substr($description, 0, 50);
        }

        // Pattern 1: "Janji Temu: [Name] - [Agenda]" or "Meeting: [Name] - [Agenda]"
        if (preg_match('/^(?:Janji Temu|Meeting|Konsultasi|Appointment|Temu)\s*[:\-]\s*(.+?)(?:\s*[-–]\s*(.+))?$/i', $summary, $matches)) {
            $customerName = trim($matches[1]);
            $agenda = !empty($matches[2]) ? trim($matches[2]) : ($description ?: $summary);
        }
        // Pattern 2: "[Name] - [Agenda]"
        elseif (preg_match('/^([^\-–:]+)\s*[-–:]\s*(.+)$/i', $summary, $matches)) {
            $customerName = trim($matches[1]);
            $agenda = trim($matches[2]);
        }
        // Pattern 3: "Meeting with [Name]"
        elseif (preg_match('/(?:Meeting|Konsultasi|Diskusi)\s+(?:with|bersama|dengan)\s+(.+)/i', $summary, $matches)) {
            $customerName = trim($matches[1]);
            $agenda = $summary;
        } else {
            $customerName = $summary ?: 'Google Calendar Event';
            $agenda = $description ?: $summary ?: 'Janji Temu Kalender';
        }

        return [
            'customer_name' => $customerName,
            'agenda' => $agenda,
        ];
    }

    /**
     * Unescape iCal characters.
     */
    private function unescapeIcs(string $str): string
    {
        return str_replace(['\,', '\;', '\n', '\N', '\\\\'], [',', ';', "\n", "\n", '\\'], $str);
    }
}

