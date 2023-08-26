<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Menu\MenuController;
use App\Models\MenuApiMap;
use App\Models\RoleApiMap;
use App\Models\UserApiExclude;
use App\Models\Workflows\WfRoleusermap;
use App\Repository\Menu\Interface\iMenuRepo;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ApiPermission
{
    /**
      created By Sandeep Bara
      Date 08/08/2023
      status open
     */

    private $_user;
    private $_tableName;
    private $_MENU_CONTROLLER;
    private $_MENU_API_MAP_MODEL;
    private $_USER_API_EXCLUDE_MODEL;
    private $_ROLE_API_MODEL;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $this->_user = auth()->user();
        $this->_tableName = $this->_user ? $this->_user->gettable() : null;
        $segments = explode('/', $request->path());
        $service = $segments[1] ?? "";
        if (!$request->has("moduleId")) {
            $request->merge(["moduleId" => Config::get('constants.' . (Config::get("apiPermission." . Str::upper($service))))]);
        }

        $this->Redisdata();
        $menuApicheck = $this->MeneApiCheck($request);
        $excludeEndPointTest = $this->checkExludeEndPoint($request);
        // $roleApiCheck = $this->RoleApiCheck($request);
        if (!$excludeEndPointTest && $this->_tableName == "users") {
            $this->Anouthicated();
        }
        if (!$menuApicheck && $this->_tableName == "users") {
            $this->Anouthicated();
        }
        $request->merge(["apiPermissionMiddelware" => ((microtime(true) - LARAVEL_START) * 1000)]);
        return $next($request);
    }

    private function Redisdata()
    {

        $this->_MENU_API_MAP_MODEL = json_decode(Redis::get("MENU_API_MAP"), true) ?? null;
        $this->_USER_API_EXCLUDE_MODEL = json_decode(Redis::get("USER_API_EXCLUDE"), true) ?? null;
        $this->_ROLE_API_MODEL = json_decode(Redis::get("ROLE_API_MAP"), true) ?? null;
        if (!$this->_MENU_API_MAP_MODEL) {
            Redis::del('MENU_API_MAP');
            $this->_MENU_API_MAP_MODEL = MenuApiMap::join("api_masters", "api_masters.id", "menu_api_maps.api_mstr_id")->select("menu_api_maps.*", "api_masters.end_point")->get();
            Redis::set("MENU_API_MAP", $this->_MENU_API_MAP_MODEL);
            $this->_MENU_API_MAP_MODEL = objToArray($this->_MENU_API_MAP_MODEL);
        }
        if (!$this->_USER_API_EXCLUDE_MODEL) {
            Redis::del('USER_API_EXCLUDE');
            $this->_USER_API_EXCLUDE_MODEL = UserApiExclude::select("user_api_excludes.*", "end_point")
                ->join("api_masters", "api_masters.id", "user_api_excludes.api_mstr_id")
                ->get();
            Redis::set("USER_API_EXCLUDE", $this->_USER_API_EXCLUDE_MODEL);
            $this->_USER_API_EXCLUDE_MODEL = objToArray($this->_USER_API_EXCLUDE_MODEL);
        }
        if (!$this->_ROLE_API_MODEL) {
            Redis::del('ROLE_API_MAP');
            $this->_ROLE_API_MODEL = RoleApiMap::select("role_api_maps.*", "end_point")
                ->join("api_masters", "api_masters.id", "role_api_maps.api_mstr_id")
                ->get();
            Redis::set("ROLE_API_MAP", $this->_ROLE_API_MODEL);
            $this->_ROLE_API_MODEL = objToArray($this->_ROLE_API_MODEL);
        }
        $this->_MENU_API_MAP_MODEL = collect($this->_MENU_API_MAP_MODEL);
        $this->_USER_API_EXCLUDE_MODEL = collect($this->_USER_API_EXCLUDE_MODEL);
        $this->_ROLE_API_MODEL = collect($this->_ROLE_API_MODEL);
        // dd(DB::getQueryLog());
    }

    private function MeneApiCheck(Request $request)
    {
        $url = trim(request()->getRequestUri(), "/") ?? "";
        $methos = ($request->getMethod());
        $this->_MENU_CONTROLLER = App::makeWith(MenuController::class, []);
        $permitedMenu = $this->_MENU_CONTROLLER->getMenuByModuleId($request);
        $roles = (new WfRoleusermap())->getRoleDetailsByUserId($this->_user ? $this->_user->id : 0)->pluck("roleId")->unique();
        if ($roles->isEmpty()) {
            $roles->push(0);
        }
        if ($permitedMenu->original['status'] && $this->_tableName == 'users' && strtoupper($methos) != "GET" && $request->coll != 'server') {
            $permitedMenu = collect($permitedMenu->original['data']["permission"]);
            $parentMenuIds = $permitedMenu->pluck("id")->toArray();
            $chiledMenus    = $permitedMenu->pluck("children")->map(function ($val) {
                if ($val) {
                    return $val;
                }
            });
            $chiledMenus    = (($chiledMenus->whereNotNull()->values()));
            $chiledMenus2 = collect();
            $subChiled = collect();
            $chiledMenus->map(function ($val) use ($chiledMenus2, $subChiled) {
                $chiledMenus2->push(collect($val)->implode("id", ","));
                collect($val)->map(function ($val2, $key) use ($subChiled) {
                    if ($val2["children"]) {

                        $subChiled->push($val2["children"]);
                    }
                });
            });
            $subChiled2 = collect();
            $subChiled->map(function ($val) use ($subChiled2) {
                $subChiled2->push(collect($val)->implode("id", ","));
            });
            $chiledMenusId = (explode(",", implode(",", ($chiledMenus2->toArray()))));
            $subChiledId = (explode(",", implode(",", ($subChiled2->toArray()))));
            $ids = (collect($parentMenuIds)->merge(collect($chiledMenusId))->merge(collect($subChiledId)));
            $ids = $ids->map(function ($val) {
                if ($val) {
                    return $val;
                }
            });
            $ids = $ids->whereNotNull();
            if ($ids->isEmpty()) {
                $ids->push(0);
            }
            #testEndPoint          
            $testEndPoint = $this->_MENU_API_MAP_MODEL->where("status", 1)
                ->whereIn("menu_mstr_id", $ids->toArray())
                ->whereIn("role_id", ($roles->toArray()))
                ->count();

            if ($testEndPoint == 0) {
                return false;
            }
        }
        return true;
    }

    private function checkExludeEndPoint(Request $request)
    {
        $url = trim(request()->getRequestUri(), "/") ?? "";
        $methos = ($request->getMethod());
        $excludeEndPointTest = $this->_USER_API_EXCLUDE_MODEL->where("status", 1)
            ->where("user_id", $this->_user->id ?? 0)
            ->where("end_point", $url);
        if ($request->moduleId) {
            $excludeEndPointTest = $excludeEndPointTest->where('module_id', $request->moduleId);
        }
        $excludeEndPointTest = $excludeEndPointTest->count();
        if ($this->_tableName == 'users' && strtoupper($methos) != "GET" && $request->coll != 'server' && $excludeEndPointTest != 0) {
            return false;
        }
        return true;
    }



    // public function RoleApiCheck(Request $request)
    // {
    //     $url = trim(request()->getRequestUri(),"/")??"";
    //     $methos = ($request->getMethod());
    //     $segments = explode('/', $request->path());
    //     $service = $segments[1];   

    //     $roles = (new WfRoleusermap())->getRoleDetailsByUserId($this->_user?$this->_user->id:0)->pluck("roleId")->unique();

    //     if($roles->isEmpty())
    //     {
    //         $roles->push(0);
    //     }
    //     $roleApiTest = $this->_ROLE_API_MODEL->where("status",1)
    //         ->whereIn("role_id",($roles->toArray()))
    //         ->where("end_point",$url)->count();
    //     if($this->_tableName=='users' && strtoupper($methos)!="GET" && $request->coll!='server' && $roleApiTest!=0)
    //     {  
    //         return false; 
    //     }
    //     return true;
    // }

    private function Anouthicated()
    {
        abort(response()->json(
            [
                'status' => false,
                'authenticated' => false,
                'massage' => "You Are Not Authorized For This Api",
            ]
        ));
    }
}
