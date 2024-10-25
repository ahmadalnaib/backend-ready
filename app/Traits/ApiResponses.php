<?php
namespace App\Traits;

trait ApiResponses{

protected function ok($message){
    return $this->successResponse($message, 200);
}


protected function successResponse($message, $statusCode = 200)
    {
        return response()->json([
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }
}
