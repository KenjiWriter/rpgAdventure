<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCharacterSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->characters()->exists()) {
            // Inertia Redirect vs API Json?
            // Since this shields game routes, we assume browser or API.
            // If JSON:
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No active character selected'], 422);
            }

            return redirect()->route('character.create');
        }

        return $next($request);
    }
}
