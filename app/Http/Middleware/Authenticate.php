<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Route API murni: jangan redirect ke route('login') (tidak ada di API, sebabkan 500).
        // Middleware akan otomatis memberi respons 401 JSON untuk request tanpa token.
        return null;
    }
}
