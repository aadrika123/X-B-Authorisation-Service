<?php

namespace App\Http\Controllers;

use App\BLL\ApiGatewayBll;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class ApiGatewayController extends Controller
{

    /**
     * | Author-Anshu Kumar
     * | Created On-11-08-2023 
     * | Created for the un authenticated apis
     * | Status - Closed
     */

    public function apiGatewayService(Request $req)
    {
        try {
            $req->merge(['authRequired' => true]);
            $apiGatewayBll = new ApiGatewayBll;
            return $apiGatewayBll->getApiResponse($req);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }
}
