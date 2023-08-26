<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuApiMap;
use App\Models\UserApiExclude;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ApiMasterController extends Controller
{
    /**
     * -----------------------------------------------------------------------------------------
     * CreatedOn-06-06-2023
     * CreatedBy-Sandeep Bara
     * ------------------------------------------------------------------------------------------
     * Code Testing
     * Tested By-
     * Feedback-
     * ------------------------------------------------------------------------------------------
     */

    # Menu-Api-map curde 

    
    public function getRowApiList(Request $request)
    {
        try {
            if(!$request->service)
            {
                return $this->selfRowApiList();
            }
            // $request->header() ="api/property/row-api-list";
            return $this->anuthinticatedApiGateway($request);
            return (new ApiGatewayController())->apiGatewayService($request);
        }
        catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
        
    }

    private function anuthinticatedApiGateway(Request $req)
    {
        try {
            // Converting environmental variables to Services
            $ApiGatewayController = new ApiGatewayController();
            $baseURLs = Config::get('constants.MICROSERVICES_APIS');
            $services = json_decode($baseURLs, true);
            // Sending to Microservices
            $segments = explode('/', $req->path());
            $service = $segments[2];
            $module = $segments[3]??"";
            if (!array_key_exists($service, $services))
                throw new Exception("Service Not Available");

            $url = $services[$service].("/".$segments[0]."/".$segments[1]."/$module");
            $ipAddress = getClientIpAddress();
            $method = $req->method();
            
            $req = $req->merge([
                'token' => $req->bearerToken(),
                'ipAddress' => $ipAddress
            ]);
            #======================
            $header = [];
            foreach($ApiGatewayController->generateDotIndexes(($req->headers->all())) as $key )
            {
                $val = explode(".",$key)[0]??"";
                if(in_array($val,["host","accept","content-length",($_FILES)?"content-type":""]))
                {
                    continue;
                }
                if(strtolower($val)=="content-type" && preg_match("/multipart/i", $ApiGatewayController->getArrayValueByDotNotation(($req->headers->all()),$key)) && !($_FILES) )
                {
                    
                    continue;
                }
                $header[explode(".",$key)[0]??""]=$ApiGatewayController->getArrayValueByDotNotation(($req->headers->all()),$key);
            }  
            $response = Http::withHeaders(                
                $header 
            );            
            $new2 = [];           
            if($_FILES)
            {
                $response = $this->fileHandeling($response);
                $new2 = $this->inputHandeling($req);
                
            }

            # Check if the response is valid to return in json format 
            $response = $response->$method($url , ($_FILES ? $new2 : $req->all()));
        
            if (isset(json_decode($response)->status)) {
                if (json_decode($response)->status == false) {
                    return json_decode($response);
                }
                return json_decode($response);
            } else {
                return $response;
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    private function selfRowApiList()
    {
        try{
            $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(function ($route) {  
                return [
                    'Prefix'=>explode("/",$route->getPrefix())[0]??"",
                    'Module'=>explode("/",$route->getPrefix())[1]??"",
                    'Method' => implode('|', $route->methods()),
                    'URI' => $route->uri(),
                    'Name' => $route->getName(),
                    'Action' => ltrim($route->getActionName(), '\\'),
                    'Middleware' => implode(', ', $route->gatherMiddleware()),
                ];
            });
        
            $routes =  $routes->where("Prefix","api")->values();

            return responseMsgs(true,"data Fetched",remove_null($routes));
        }
        catch(Exception $e)
        {
            return responseMsgs(false,"data Not Fetched","");
        }
    }

    public function menuApiMapStore(Request $request)
    {
        try{  

            $rules["menuMstrId"] = "required|digits_between:1,9223372036854775807";
            $rules["apiMstrId"] = "required|digits_between:1,9223372036854775807";             
            $rules["roleId"] = "required|digits_between:1,9223372036854775807";       
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsgs(false, $validator->errors(), $request->all());
            }
            $menuApiMap = new MenuApiMap();
            $testData = $menuApiMap->where("menu_mstr_id",$request->menuMstrId)
                        ->where("api_mstr_id",$request->apiMstrId)
                        ->where("role_id",$request->roleId)
                        ->orderBy("id","DESC")        
                        ->first();

            $sms = "SuccessFully Api Maped";
            if(!$testData)
            {
                $menuApiMap->menu_mstr_id = $request->menuMstrId;
                $menuApiMap->api_mstr_id = $request->apiMstrId;
                $menuApiMap->role_id = $request->roleId;
                $menuApiMap->save();
            }
            if($testData)
            {
                $sms = "SuccessFully Api Maped Updated";
                $testData->update();
            }
            return responseMsgs(true, $sms,"" );
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    
    }

    public function menuApiMapList(Request $request)
    {
        try{
            $menuApiMap = new MenuApiMap();
            $list = $menuApiMap->select("menu_api_maps.*",
                                        "menu_masters.menu_string",
                                        "menu_masters.module_id",
                                        "module_masters.module_name",
                                        "api_masters.end_point",
                                        "api_masters.category",
                                        "api_masters.description",
                                        "wf_roles.role_name",
                    )
                    ->join("menu_masters","menu_masters.id","menu_api_maps.menu_mstr_id")
                    ->join("api_masters","api_masters.id","menu_api_maps.api_mstr_id")
                    ->join("wf_roles","wf_roles.id","menu_api_maps.role_id")
                    ->leftjoin("module_masters","module_masters.id","menu_masters.module_id")
                    ->orderBy("menu_mstr_id")
                    ->get();
            return responseMsg(true,["heard"=>"Menu Api Map List"],remove_null($list));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),[]);
        }
    }

    public function menuApiMap(Request $request)
    {
        try{
            $request->validate(
                [
                    "id" => "required|digits_between:1,9223372036854775807",
                ]
            );
            $menuApiMap = new MenuApiMap();
            $menuApiMapData   = $menuApiMap->select(
                                "menu_api_maps.*",
                                "menu_masters.menu_string",
                                "menu_masters.module_id",
                                "module_masters.module_name",
                                "api_masters.end_point",
                                "api_masters.category",
                                "api_masters.description",
                                "wf_roles.role_name",
                                )
                                ->join("menu_masters","menu_masters.id","menu_api_maps.menu_mstr_id")
                                ->join("api_masters","api_masters.id","menu_api_maps.api_mstr_id")
                                ->join("wf_roles","wf_roles.id","menu_api_maps.role_id")
                                ->leftjoin("module_masters","module_masters.id","menu_masters.module_id")                                
                                ->find($request->id);
            if(!$menuApiMapData)
            {
                  throw new Exception("Data Not Found");   
            }
            return responseMsg(true,["heard"=>"Menu Api Map Detail"],remove_null($menuApiMapData));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }

    public function menuApiMapUpdate(Request $request)
    {
        try{    
            $rules["id"]         = "required|digits_between:1,9223372036854775807";   
            $rules["menuMstrId"] = "required|digits_between:1,9223372036854775807";
            $rules["apiMstrId"]  = "required|digits_between:1,9223372036854775807"; 
            $rules["roleId"] = "required|digits_between:1,9223372036854775807"; 
            $rules["status"]     =     "nullable|in:0,1" ;      
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsgs(false, $validator->errors(), $request->all());
            }
            $menuApiMap = new MenuApiMap();
            $testData = $menuApiMap->find($request->id);
            $sms = "SuccessFully Api Maped";
            if(!$testData)
            {
                    throw new Exception("Data Not Found");   
            }

            DB::beginTransaction();    
            #update data
            $sms="Updated Recode";
            $testData->menu_mstr_id = $request->menuMstrId;
            $testData->api_mstr_id = $request->apiMstrId;
            $testData->role_id = $request->roleId;
            if(isset($request->status))
            {
                switch($request->status)
                {
                    case 0 : $testData->status   = 0;
                            break;
                    case 1  : $testData->status  = 1;
                            break;
                }

            }
            $testData->update();
            DB::commit();
            return responseMsgs(true, $sms,"" );
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    }

    #===========User-Api-Exclude Crude=================
    public function userApiExcluldeStor(Request $request)
    {
        try{  

            $rules["userId"] = "required|digits_between:1,9223372036854775807";
            $rules["apiMstrId"] = "required|digits_between:1,9223372036854775807";   
            $rules["moduleId"] = "nullable|digits_between:1,9223372036854775807";       
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsgs(false, $validator->errors(), $request->all());
            }
            $menuApiMap = new UserApiExclude();
            $testData = $menuApiMap->where("user_id",$request->userId)
                        ->where("api_mstr_id",$request->apiMstrId);
            if($request->moduleId)
            {
                $testData = $testData->where("module_id",$request->moduleId);
            }                        
            $testData = $testData->orderBy("id","DESC")        
                        ->first();

            $sms = "SuccessFully Api Excluded";
            if(!$testData)
            {
                $menuApiMap->user_id = $request->userId;
                $menuApiMap->api_mstr_id = $request->apiMstrId;
                $menuApiMap->module_id = $request->moduleId;
                $menuApiMap->save();
            }
            if($testData)
            {
                $sms = "SuccessFully Api Excluded Updated";
                $testData->update();
            }
            return responseMsgs(true, $sms,"" );
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    }

    public function userApiExcluldeList(Request $request)
    {
        try{
            $menuApiMap = new UserApiExclude();
            $list = $menuApiMap->select("user_api_excludes.*",
                                        "module_masters.module_name",
                                        "api_masters.end_point",
                                        "api_masters.category",
                                        "api_masters.description",
                                        "users.user_name",
                                        "users.mobile",
                                        "users.name",
                                        "users.user_code",
                    )
                    ->join("api_masters","api_masters.id","user_api_excludes.api_mstr_id")
                    ->join("users","users.id","user_api_excludes.user_id")
                    ->leftjoin("module_masters","module_masters.id","user_api_excludes.module_id")
                    ->orderBy("user_api_excludes.user_id")
                    ->orderBy("user_api_excludes.module_id")
                    ->orderBy("user_api_excludes.id")
                    ->get();
            return responseMsg(true,["heard"=>"User Api Excluded List"],remove_null($list));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),[]);
        }
    }

    public function userApiExclulde(Request $request)
    {
        try{
            $request->validate(
                [
                    "id" => "required|digits_between:1,9223372036854775807",
                ]
            );
            $menuApiMap = new UserApiExclude();
            $menuApiMapData   = $menuApiMap->select(
                                "user_api_excludes.*",
                                "module_masters.module_name",
                                "api_masters.end_point",
                                "api_masters.category",
                                "api_masters.description",
                                "users.user_name",
                                "users.mobile",
                                "users.name",
                                "users.user_code",
                                )
                                ->join("api_masters","api_masters.id","user_api_excludes.api_mstr_id")
                                ->join("users","users.id","user_api_excludes.user_id")
                                ->leftjoin("module_masters","module_masters.id","user_api_excludes.module_id")
                                ->find($request->id);
            if(!$menuApiMapData)
            {
                  throw new Exception("Data Not Found");   
            }
            return responseMsg(true,["heard"=>"User Api Exclude Detail"],remove_null($menuApiMapData));
        }
        catch(Exception $e)
        {
            return responseMsg(false,$e->getMessage(),$request->all());
        }
        
    }

    public function userApiExcluldeUpdate(Request $request)
    {
        try{    
            $rules["id"]         = "required|digits_between:1,9223372036854775807";   
            $rules["userId"] = "required|digits_between:1,9223372036854775807";
            $rules["apiMstrId"] = "required|digits_between:1,9223372036854775807";   
            $rules["moduleId"] = "nullable|digits_between:1,9223372036854775807";   
            $rules["status"]     =     "nullable|in:0,1" ;      
            $validator = Validator::make($request->all(), $rules,);
            if ($validator->fails()) 
            {
                return responseMsgs(false, $validator->errors(), $request->all());
            }
            $menuApiMap = new UserApiExclude();
            $testData = $menuApiMap->find($request->id);
            $sms = "SuccessFully Api Maped";
            if(!$testData)
            {
                    throw new Exception("Data Not Found");   
            }

            DB::beginTransaction();    
            #update data
            $sms="Updated Recode";
            $testData->user_id = $request->userId;
            $testData->api_mstr_id = $request->apiMstrId;
            $testData->module_id = $request->moduleId;
            if(isset($request->status))
            {
                switch($request->status)
                {
                    case 0 : $testData->status   = 0;
                            break;
                    case 1  : $testData->status  = 1;
                            break;
                }

            }
            $testData->update();
            DB::commit();
            return responseMsgs(true, $sms,"" );
        }
        catch(Exception $e)
        {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    }
}
