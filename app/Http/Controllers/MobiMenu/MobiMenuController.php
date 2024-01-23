<?php

namespace App\Http\Controllers\MobiMenu;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobiMenu\AddMenu;
use App\Http\Requests\MobiMenu\AddUserMenuExclude;
use App\Http\Requests\MobiMenu\UpdateMenu;
use App\Http\Requests\MobiMenu\UpdateUserMenuExclude;
use App\Models\Auth\User;
use App\Models\MobiMenu\MenuMobileMaster;
use App\Models\MobiMenu\UserMenuMobileExclude;
use App\Models\MobiMenu\UserMenuMobileInclude;
use App\Models\Workflows\WfRole;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MobiMenuController extends Controller
{
    /**
     * =======================Controller For Mobile App Menu Managament==============================================
     *          Create By   : Sandeep Bara
     *          Date        : 2024-01-20
     * 
    */

    private $_MenuMobileMaster;
    private $_UserMenuMobileExclude;
    private $_UserMenuMobileInclude;

    public function __construct()
    {
        $this->_MenuMobileMaster        = new MenuMobileMaster();
        $this->_UserMenuMobileExclude   = new UserMenuMobileExclude();
        $this->_UserMenuMobileInclude   = new UserMenuMobileInclude();
    }

    private function begin()
    {
        DB::beginTransaction();
    }

    private function commit()
    {
        DB::commit();
    }
    private function rollback()
    {
        DB::rollback();
    }

    #==========================Crude Menu Masters=======================

    /**
     * ==================✍Add new Menu ✍===================
    */
    public function addMenu(AddMenu $request)
    {
        try{  
            $this->begin();          
            if(!$this->_MenuMobileMaster->store($request))
            {
                throw new Exception("Some Error Occurse On Storing Data");
            }
            $this->commit();
            return responseMsg(true, "Menu Added", "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍Add new Menu ✍===================
    */
    public function editMenu(UpdateMenu $request)
    {
        try{  
            $this->begin();  
            if(!$this->_MenuMobileMaster->edit($request))
            {
                throw new Exception("Some Error Occurse On Editing Data");
            }
            $this->commit();
            return responseMsg(true, "Menu updated", "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }


    /**
     * =================📖 List of Parent Menu📖==================
    */
    public function getParentMenuList(Request $request)
    {
        try{
            $data = $this->_MenuMobileMaster->metaDtls()
                    ->where("menu_mobile_masters.parent_id",0);
            if($request->moduleId)
            {
                $data->where("menu_mobile_masters.module_id",$request->moduleId) ;
            }
            if(isset($request->status))
            {
                $data->where("menu_mobile_masters.is_active",$request->status) ;
            }
            else{
                $data->where("menu_mobile_masters.is_active",true) ;
            }
            if($request->key)
            {
                $key = trim($request->key);
                $data->where(function($query)use($key){
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%');
                }) ;
            }
            
            $data = $data->Orderby("menu_mobile_masters.id","ASC")
                    ->OrderBy("menu_mobile_masters.module_id","ASC")
                    ->get();
            
            return responseMsg(true, "All Parent Menu", $data);
        }
        catch(Exception $e)
        {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================📑 List Of All Menu 📑===================
    */
    public function getMenuList(Request $request)
    {
        try{

            $data = $this->_MenuMobileMaster->metaDtls();
            if($request->moduleId)
            {
                $data->where("menu_mobile_masters.module_id",$request->moduleId) ;
            }
            if(isset($request->status))
            {
                $data->where("menu_mobile_masters.is_active",$request->status) ;
            }
            if($request->key)
            {
                $key = trim($request->key);
                $data->where(function($query)use($key){
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%');
                }) ;
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $paginator = $data->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),            
            ];
            return responseMsg(true, "Menu List", remove_null($list));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================📑 Dtl Of Menu 📑===================
    */
    public function menuDtl(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                "id"=>"required|digits_between:0,9999999999",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try{  
            $dtls = $this->_MenuMobileMaster->dtls($request->id); 
            if(!$dtls)
            {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    #==========================End Crude Menu Masters=======================

    #===================================Crud Of User Menu Exclude============

    /**
     * ==================✍ Add new Exclude Menu ✍===================
    */
    public function addUserExcludeMenu(AddUserMenuExclude $request)
    {
        try{  
            $this->begin();              
            if(!$this->_UserMenuMobileExclude->store($request))
            {
                throw new Exception("Some Error Occurs On Add Data");
            }
            $user = User::find($request->userId);
            $menu = $this->_MenuMobileMaster->find($request->menuId);
            $this->commit();
            return responseMsg(true, ($menu->menu_string??"")." Menu Is Exclude For ".($user->name??""), "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍ Edit Exclude Menu ✍===================
    */

    public function editUserExcludeMenu(UpdateUserMenuExclude $request)
    {
        try{  
            $this->begin();              
            if(!$this->_UserMenuMobileExclude->edit($request))
            {
                throw new Exception("Some Error Occurs On Editing Data");
            }
            $this->commit();
            return responseMsg(true, "Update Menu Exclude", "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userExcludeMenuList(Request $request)
    {
        try{

            $data = $this->_UserMenuMobileExclude->metaDtls();
            if($request->moduleId)
            {
                $data->where("menu_mobile_masters.module_id",$request->moduleId) ;
            }
            if(isset($request->status))
            {
                $data->where("user_menu_mobile_excludes.is_active",$request->status) ;
            }
            if($request->key)
            {
                $key = trim($request->key);
                $data->where(function($query)use($key){
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.name", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.email", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.mobile", 'ILIKE', '%' . $key . '%');
                });
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $paginator = $data->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),            
            ];
            return responseMsg(true, "User Menu Exclude List", remove_null($list));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userExcludeMenuDtl(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                "id"=>"required|digits_between:0,9999999999",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try{  
            $dtls = $this->_UserMenuMobileExclude->dtls($request->id); 
            if(!$dtls)
            {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }


    #===================================Crud Of User Menu Include============

    /**
     * ==================✍ Add new Include Menu ✍===================
    */
    public function addUserIncludeMenu(AddUserMenuExclude $request)
    {
        try{  
            $this->begin();              
            if(!$this->_UserMenuMobileExclude->store($request))
            {
                throw new Exception("Some Error Occurs On Add Data");
            }
            $user = User::find($request->userId);
            $menu = $this->_MenuMobileMaster->find($request->menuId);
            $this->commit();
            return responseMsg(true, ($menu->menu_string??"")." Menu Is Include For ".($user->name??""), "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍ Edit Include Menu ✍===================
    */

    public function editUserIncludeMenu(UpdateUserMenuExclude $request)
    {
        try{  
            $this->begin();              
            if(!$this->_UserMenuMobileExclude->edit($request))
            {
                throw new Exception("Some Error Occurs On Editing Data");
            }
            $this->commit();
            return responseMsg(true, "Update Menu Include", "");
        }
        catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userIncludeMenuList(Request $request)
    {
        try{

            $data = $this->_UserMenuMobileExclude->metaDtls();
            if($request->moduleId)
            {
                $data->where("menu_mobile_masters.module_id",$request->moduleId) ;
            }
            if(isset($request->status))
            {
                $data->where("user_menu_mobile_includes.is_active",$request->status) ;
            }
            if($request->key)
            {
                $key = trim($request->key);
                $data->where(function($query)use($key){
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.name", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.email", 'ILIKE', '%' . $key . '%')
                        ->orwhere("users.mobile", 'ILIKE', '%' . $key . '%');
                });
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $paginator = $data->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),            
            ];
            return responseMsg(true, "User Menu Include List", remove_null($list));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userIncludeMenuDtl(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                "id"=>"required|digits_between:0,9999999999",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try{  
            $dtls = $this->_UserMenuMobileExclude->dtls($request->id); 
            if(!$dtls)
            {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        }
        catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

}
