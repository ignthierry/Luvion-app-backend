<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PricingTier;
use Illuminate\Support\Str;

class PricingController extends Controller
{
    public function index()
    {
        return response()->json(PricingTier::all());
    }

    public function show($id)
    {
        $tier = PricingTier::findOrFail($id);
        return response()->json($tier);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:pricing_tiers,id',
            'name' => 'required|string',
            'subtitle' => 'required|string',
            'price' => 'required|string',
            'original_price' => 'nullable|string',
            'price_suffix' => 'nullable|string',
            'description' => 'required|string',
            'features' => 'required|array',
            'popular' => 'boolean',
            'highlight_color' => 'nullable|string',
        ]);

        $tier = PricingTier::create($validated);
        return response()->json($tier, 201);
    }

    public function update(Request $request, $id)
    {
        $tier = PricingTier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'subtitle' => 'sometimes|required|string',
            'price' => 'sometimes|required|string',
            'original_price' => 'nullable|string',
            'price_suffix' => 'nullable|string',
            'description' => 'sometimes|required|string',
            'features' => 'sometimes|required|array',
            'popular' => 'boolean',
            'highlight_color' => 'nullable|string',
        ]);

        $tier->update($validated);
        return response()->json($tier);
    }

    public function destroy($id)
    {
        $tier = PricingTier::findOrFail($id);
        $tier->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
