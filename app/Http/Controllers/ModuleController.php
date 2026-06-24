<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Module::all());
    }
}
