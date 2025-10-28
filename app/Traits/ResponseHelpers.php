<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ResponseHelpers
{
    protected function success(
        mixed $data = null,
        string $message = "Success",
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $response = ["success" => true, "message" => $message];

        if ($data !== null) {
            $response["data"] = $data;
        }

        return response()->json($response, $status);
    }

    protected function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
    ): JsonResponse {
        $response = ["success" => false, "message" => $message];

        if ($errors !== null) {
            $response["errors"] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function created(
        mixed $data = null,
        string $message = "Created successfully",
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function notFound(
        string $message = "Resource not found",
    ): JsonResponse {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    protected function unauthorized(
        string $message = "Unauthorized",
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    protected function tooManyRequests(
        string $message = "Too many requests",
        ?int $retryAfter = null,
    ): JsonResponse {
        $response = response()->json(
            [
                "success" => false,
                "message" => $message,
                "retry_after" => $retryAfter,
            ],
            Response::HTTP_TOO_MANY_REQUESTS,
        );

        if ($retryAfter) {
            $response->header("Retry-After", $retryAfter);
        }

        return $response;
    }
}
