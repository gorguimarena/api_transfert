<?php

namespace App\Http\Middleware;

use App\Exceptions\CompteException;
use App\ResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleCompteExceptions
{
    use ResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (CompteException $e) {
            Log::warning('Exception Compte capturée: ' . $e->getMessage(), [
                'user_id' => auth('api')->id(),
                'request' => $request->all(),
                'exception_code' => $e->getCode()
            ]);

            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
}