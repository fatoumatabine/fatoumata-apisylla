<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Http\Traits\ApiResponseTrait;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof NotFoundHttpException) {
                    return $this->error('Resource not found', 404, 'NOT_FOUND', [], $request->fullUrl());
                }

                if ($e instanceof CompteNotFoundException) {
                    return $this->error($e->getMessage(), 404, 'COMPTE_NOT_FOUND', [], $request->fullUrl());
                }

                if ($e instanceof AuthenticationException) {
                    return $this->error('Unauthenticated', 401, 'UNAUTHENTICATED', [], $request->fullUrl());
                }

                if ($e instanceof AccessDeniedHttpException) {
                    return $this->error('Forbidden', 403, 'FORBIDDEN', [], $request->fullUrl());
                }

                if ($e instanceof MethodNotAllowedHttpException) {
                    return $this->error('Method not allowed', 405, 'METHOD_NOT_ALLOWED', [], $request->fullUrl());
                }

                if ($e instanceof ValidationException) {
                    $errors = $e->validator->errors()->getMessages();
                    return $this->error('Validation failed', 422, 'VALIDATION_ERROR', $errors, $request->fullUrl());
                }

                if ($e instanceof TooManyRequestsHttpException) {
                    return $this->error('Too many requests', 429, 'TOO_MANY_REQUESTS', [], $request->fullUrl());
                }

                // Gérer les autres exceptions non capturées
                return $this->error(
                    'Internal Server Error',
                    500,
                    'INTERNAL_SERVER_ERROR',
                    ['exception' => $e->getMessage()],
                    $request->fullUrl(),
                    (string) \Illuminate\Support\Str::uuid()
                );
            }
        });
    }
}
