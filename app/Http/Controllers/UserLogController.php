<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserLogController extends Controller
{
    public function index()
    {
        $logs = \App\Models\UserLog::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($logs);
    }
}
