<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function success($data = null, string $message = '', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 400, $errors = null)
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
