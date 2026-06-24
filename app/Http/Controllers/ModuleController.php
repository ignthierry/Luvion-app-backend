<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;

class ModuleController extends Controller
{
    public function index()
    {
        return response()->json(Module::all());
    }

    public function show($id)
    {
        $module = Module::findOrFail($id);
        return response()->json($module);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:modules,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'icon' => 'required|string',
            'color' => 'required|string',
            'bg_grad' => 'required|string',
            'demo_type' => 'required|string',
            'demo_title' => 'required|string',
            'demo_link' => 'nullable|string',
        ]);

        $module = Module::create($validated);
        return response()->json($module, 201);
    }

    public function update(Request $request, $id)
    {
        $module = Module::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'icon' => 'sometimes|required|string',
            'color' => 'sometimes|required|string',
            'bg_grad' => 'sometimes|required|string',
            'demo_type' => 'sometimes|required|string',
            'demo_title' => 'sometimes|required|string',
            'demo_link' => 'nullable|string',
        ]);

        $module->update($validated);
        return response()->json($module);
    }

    public function destroy($id)
    {
        $module = Module::findOrFail($id);
        $module->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
