<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Success Response
     */
    public static function success($data = [], $message = 'Success', $code = 200, $meta = null)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $code);
    }

    /**
     * Error Response
     */
    public static function error($message = 'Error', $code = 400, $errors = [])
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
