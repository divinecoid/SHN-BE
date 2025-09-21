<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Database\ConnectionException;
use PDOException;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Handler extends ExceptionHandler
{
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
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // Handle database connection errors
        if ($this->isDatabaseConnectionError($exception)) {
            return $this->handleDatabaseConnectionError($request, $exception);
        }

        // Handle other database errors
        if ($exception instanceof QueryException) {
            return $this->handleDatabaseError($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Check if the exception is a database connection error
     */
    private function isDatabaseConnectionError(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof PDOException) {
            $message = $exception->getMessage();
            return str_contains($message, 'No connection could be made') ||
                   str_contains($message, 'Connection refused') ||
                   str_contains($message, 'SQLSTATE[HY000]') ||
                   str_contains($message, 'target machine actively refused');
        }

        return false;
    }

    /**
     * Handle database connection errors
     */
    private function handleDatabaseConnectionError(Request $request, Throwable $exception): JsonResponse
    {
        // Log the error for debugging
        \Log::error('Database connection error: ' . $exception->getMessage(), [
            'exception' => $exception,
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke database gagal. Silakan coba lagi nanti.',
                'error' => 'DATABASE_CONNECTION_ERROR',
                'data' => null,
            ], 503); // Service Unavailable
        }

        // For non-API requests, return a simple error page
        return response()->view('errors.503', [], 503);
    }

    /**
     * Handle general database errors
     */
    private function handleDatabaseError(Request $request, QueryException $exception): JsonResponse
    {
        // Log the error for debugging
        \Log::error('Database query error: ' . $exception->getMessage(), [
            'exception' => $exception,
            'sql' => $exception->getSql(),
            'bindings' => $exception->getBindings(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada database. Silakan coba lagi.',
                'error' => 'DATABASE_ERROR',
                'data' => null,
            ], 500);
        }

        return response()->view('errors.500', [], 500);
    }
}
