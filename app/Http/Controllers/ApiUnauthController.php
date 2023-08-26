<?php

namespace App\Http\Controllers;

use App\BLL\ApiGatewayBll;
use Exception;
use Illuminate\Http\Request;


/**
 * | Author-Anshu Kumar
 * | Created On-11-08-2023 
 * | Created for the un authenticated apis
 * | Status-Closed
 */
class ApiUnauthController extends Controller
{

    /**
     * | Un Authenticated Apis(1)
     */
    public function unAuthApis(Request $req)
    {
        try {
            $req->merge(['authRequired' => false]);
            $apiGatewayBll = new ApiGatewayBll;
            return $apiGatewayBll->getApiResponse($req);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }
}
