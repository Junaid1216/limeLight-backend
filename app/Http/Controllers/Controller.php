<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function message($message, $alert) {
        return array(
            'message' => $message,
            'alert' => $alert,
        );
    }
    function sendSuccess($message, $data = '')
    {
        return Response::json(array('status' => 200, 'message' => $message, 'successData' => $data), 200, []);
    }

    function sendError($error_message, $code = 400)
    {
        return Response::json(array('status' => $code, 'message' => $error_message), $code)->setStatusCode($code, $error_message);
    }
}
