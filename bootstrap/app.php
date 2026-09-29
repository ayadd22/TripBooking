<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
       
        $isApiRequest = fn (\Illuminate\Http\Request $request): bool =>
            str_starts_with($request->getPathInfo(), '/api');

       
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], \Illuminate\Http\Response::HTTP_NOT_FOUND);
            }
        });

     
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], \Illuminate\Http\Response::HTTP_NOT_FOUND);
            }
        });

      
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });


        $exceptions->render(function (
            \App\Exceptions\SeatNotAvailableException $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        $exceptions->render(function (
            \App\Exceptions\SeatBusMismatchException $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

      
        $exceptions->render(function (
            \Throwable $e,
            \Illuminate\Http\Request $request,
        ) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred.',
                ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    })->create();
