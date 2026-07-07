<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ClientOrder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientOrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'website' => 'nullable|string|max:255',
            'plan_name' => 'required|string',
            'billing_cycle' => 'required|string',
            'users_count' => 'required|integer|min:1',
            'purpose' => 'nullable|string',
            'addons' => 'nullable|array',
            'integration_needs' => 'nullable|string',
            'subdomain' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'theme_color' => 'nullable|string',
            'notes' => 'nullable|string',
            'timeline' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $order = ClientOrder::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }

    public function index()
    {
        $orders = ClientOrder::orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function update(Request $request, $id)
    {
        $order = ClientOrder::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'payment_status' => 'nullable|string|in:unpaid,paid,overdue,failed',
            'billing_due_day' => 'nullable|integer|min:1|max:31',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($request->only(['status', 'payment_status', 'billing_due_day']));

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order
        ]);
    }

    public function destroy($id)
    {
        $order = ClientOrder::findOrFail($id);
        
        if ($order->logo_path) {
            Storage::disk('public')->delete($order->logo_path);
        }
        
        $order->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully'
        ]);
    }
}
