<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use App\Http\Traits\ApiResponseTrait; // Moved to the end of imports
use App\Exceptions\CompteNotFoundException; // Added

class Handler extends ExceptionHandler
{
    use ApiResponseTrait; // Use the trait here

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

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return $this->error('Resource not found', 404);
            }
        });

        $this->renderable(function (CompteNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return $this->error($e->getMessage(), 404);
            }
        });
    }
}
