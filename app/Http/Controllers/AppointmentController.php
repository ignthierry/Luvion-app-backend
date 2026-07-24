<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

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

        $appointments = $query->orderBy('created_at', 'desc')->get();

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
}
