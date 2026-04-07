<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogger
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $status = $response->getStatusCode();

        if ($duration > 1000 || $status >= 500) {
            Log::warning('Slow/Error request', [
                'method' => $request->method(),
                'uri' => $request->path(),
                'status' => $status,
                'duration_ms' => $duration,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
