<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as HTTP;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http as HttpRequest;

class FrontendController extends Controller
{
    /**
     * Display a index route default response.
     */
    public function index(Request $request)
    {
        try {
            return Response::json([
                "success" => true,
                "status" => 200,
                "message" => "Departure API Version V0.1",
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * Display a auth route default response.
     */
    public function auth(Request $request)
    {
        return Response::json([
            "success" => true,
            "status" => 200,
            "message" => "Departure, Auth Version V0.1",
        ],  HTTP::HTTP_OK); // HTTP::HTTP_OK

    }
}
