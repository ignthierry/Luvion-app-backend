<?php

namespace App\Http\Controllers;

use App\Models\CustomerRequest;
use Illuminate\Http\Request;

class CustomerRequestController extends Controller
{
    /**
     * List all customer requests for Admin
     */
    public function index()
    {
        $requests = CustomerRequest::with(['user', 'clientOrder'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Update customer request status (Admin)
     */
    public function update(Request $request, $id)
    {
        $customerRequest = CustomerRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,in_progress,approved,rejected',
        ]);

        $customerRequest->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status request berhasil diperbarui.',
            'data' => $customerRequest->load(['user', 'clientOrder'])
        ]);
    }

    /**
     * Delete customer request
     */
    public function destroy($id)
    {
        $customerRequest = CustomerRequest::findOrFail($id);
        $customerRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Request berhasil dihapus.'
        ]);
    }
}
