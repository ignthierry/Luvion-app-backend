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
}
