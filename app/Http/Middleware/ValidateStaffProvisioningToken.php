<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateStaffProvisioningToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('services.staff_provisioning.token');
        $provided = $request->bearerToken() ?? $request->header('X-Staff-Provisioning-Token');

        if ($token === null || $token === '' || ! hash_equals($token, (string) $provided)) {
            abort(403, 'Jeton de provisionnement invalide.');
        }

        return $next($request);
    }
}
