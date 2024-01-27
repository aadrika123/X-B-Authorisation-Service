<?php

namespace App\Http\Controllers\MobiMenu;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobiMenu\AddMenu;
use App\Http\Requests\MobiMenu\AddUserMenuExclude;
use App\Http\Requests\MobiMenu\UpdateMenu;
use App\Http\Requests\MobiMenu\UpdateUserMenuExclude;
use App\Models\Auth\User;
use App\Models\MobiMenu\MenuMobileMaster;
use App\Models\MobiMenu\MenuMobileRoleMap;
use App\Models\MobiMenu\UserMenuMobileExclude;
use App\Models\MobiMenu\UserMenuMobileInclude;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
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
    private $_MenuMobileRoleMap;
    private $_WfRoleusermap;
    public function __construct()
    {
        // DB::enableQueryLog();
        $this->_MenuMobileMaster        = new MenuMobileMaster();
        $this->_MenuMobileRoleMap         = new MenuMobileRoleMap();
        $this->_UserMenuMobileExclude   = new UserMenuMobileExclude();
        $this->_UserMenuMobileInclude   = new UserMenuMobileInclude();
        $this->_WfRoleusermap = new WfRoleusermap();
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
        try {
            $this->begin();
            $menuId = null;
            $menuTest = $this->_MenuMobileMaster->where(["module_id" => $request->moduleId, "menu_string" => $request->menuName, "route" => $request->path])->first();
            $menuId = $menuTest->id ?? null;
            if (!$menuTest) {
                $menuId = $this->_MenuMobileMaster->store($request);
            }
            if (!$menuId) {
                throw new Exception("Something Went Wrong");
            }
            if ($roleTest = $this->_MenuMobileRoleMap->where(["menu_id" => $menuId, "role_id" => $request->roleId])->first()) {
                throw new Exception("This Menu Is Already Added For This Role");
            }
            $request->merge(["menuId" => $menuId]);
            if (!$this->_MenuMobileRoleMap->store($request)) {
                throw new Exception("Some Error Occurs On Storing Data");
            }
            $this->commit();
            return responseMsg(true, "Menu Added", "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍Add new Menu ✍===================
     */
    public function editMenu(UpdateMenu $request)
    {
        try {
            $this->begin();

            $childeTest = $this->_MenuMobileRoleMap->where(["menu_id" => $request->menuId, "is_active" => true])->get()->count("id");

            if (isset($request->menuStatus) && !$request->menuStatus && $childeTest <= 1) {
                $request->merge(["menuStatus" => $request->status]);
            }
            if (!$this->_MenuMobileMaster->edit($request)) {
                throw new Exception("Some Error Occurs On Editing Data");
            }

            $roleMenuTest = $this->_MenuMobileRoleMap->where(["menu_id" => $request->menuId, "role_id" => $request->roleId])->first();
            $request->merge((["roleMenuId" => $roleMenuTest->id ?? null]));
            if ($roleMenuTest) {
                $roleMapId = $this->_MenuMobileRoleMap->edit($request);
            } else {
                $roleMapId = $this->_MenuMobileRoleMap->store($request);
            }
            if (!$roleMapId) {
                throw new Exception("Something Went Wrong");
            }
            $this->commit();
            return responseMsg(true, "Menu updated", "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, [$e->getMessage(), $e->getFile(), $e->getLine()], "",);
        }
    }


    /**
     * =================📖 List of Parent Menu📖==================
     */
    public function getParentMenuList(Request $request)
    {
        try {
            $data = $this->_MenuMobileMaster->metaDtls()
                ->where("menu_mobile_masters.parent_id", 0);
            if ($request->moduleId) {
                $data->where("menu_mobile_masters.module_id", $request->moduleId);
            }
            if (isset($request->status)) {
                $data->where("menu_mobile_masters.is_active", $request->status);
            } else {
                $data->where("menu_mobile_masters.is_active", true);
            }
            if ($request->key) {
                $key = trim($request->key);
                $data->where(function ($query) use ($key) {
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%');
                });
            }

            $data = $data->Orderby("menu_mobile_masters.id", "ASC")
                ->OrderBy("menu_mobile_masters.module_id", "ASC")
                ->get();

            return responseMsg(true, "All Parent Menu", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================📑 List Of All Menu 📑===================
     */
    public function getMenuList(Request $request)
    {
        try {

            $data = $this->_MenuMobileMaster->metaDtls();
            if ($request->moduleId) {
                $data->where("menu_mobile_masters.module_id", $request->moduleId);
            }
            if (isset($request->status)) {
                $data->where("menu_mobile_masters.is_active", $request->status);
            }
            if ($request->key) {
                $key = trim($request->key);
                $data->where(function ($query) use ($key) {
                    $query->orwhere('menu_mobile_masters.menu_string', 'ILIKE', '%' . $key . '%')
                        ->orwhere('menu_mobile_masters.route', 'ILIKE', '%' . $key . '%')
                        ->orwhere("menu_mobile_masters.icon", 'ILIKE', '%' . $key . '%');
                });
            }
            $data->orderBy("menu_mobile_masters.id", "ASC")
                ->orderBy("menu_mobile_masters.module_id", "ASC")
                ->orderBy("wf_roles.id", "ASC");
            $perPage = $request->perPage ? $request->perPage : 10;
            $paginator = $data->paginate($perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
            return responseMsg(true, "Menu List", remove_null($list));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================📑 Dtl Of Menu 📑===================
     */
    public function menuDtl(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "id" => "required|digits_between:0,9999999999",
                "roleId" => "nullable|digits_between:0,9999999999"
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $dtls = $this->_MenuMobileMaster->dtls($request->id, $request->roleId);
            if (!$dtls) {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        } catch (Exception $e) {
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
        try {
            $this->begin();
            if (!$this->_UserMenuMobileExclude->store($request)) {
                throw new Exception("Some Error Occurs On Add Data");
            }
            $user = User::find($request->userId);
            $menu = $this->_MenuMobileMaster->find($request->menuId);
            $this->commit();
            return responseMsg(true, ($menu->menu_string ?? "") . " Menu Is Exclude For " . ($user->name ?? ""), "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍ Edit Exclude Menu ✍===================
     */

    public function editUserExcludeMenu(UpdateUserMenuExclude $request)
    {
        try {
            $this->begin();
            if (!$this->_UserMenuMobileExclude->edit($request)) {
                throw new Exception("Some Error Occurs On Editing Data");
            }
            $this->commit();
            return responseMsg(true, "Update Menu Exclude", "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userExcludeMenuList(Request $request)
    {
        try {

            $data = $this->_UserMenuMobileExclude->metaDtls();
            if ($request->moduleId) {
                $data->where("menu_mobile_masters.module_id", $request->moduleId);
            }
            if (isset($request->status)) {
                $data->where("user_menu_mobile_excludes.is_active", $request->status);
            }
            if ($request->key) {
                $key = trim($request->key);
                $data->where(function ($query) use ($key) {
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
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userExcludeMenuDtl(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "id" => "required|digits_between:0,9999999999",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $dtls = $this->_UserMenuMobileExclude->dtls($request->id);
            if (!$dtls) {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }


    #===================================Crud Of User Menu Include============

    /**
     * ==================✍ Add new Include Menu ✍===================
     */
    public function addUserIncludeMenu(AddUserMenuExclude $request)
    {
        try {
            $this->begin();
            if (!$this->_UserMenuMobileInclude->store($request)) {
                throw new Exception("Some Error Occurs On Add Data");
            }
            $user = User::find($request->userId);
            $menu = $this->_MenuMobileMaster->find($request->menuId);
            $this->commit();
            return responseMsg(true, ($menu->menu_string ?? "") . " Menu Is Include For " . ($user->name ?? ""), "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    /**
     * ==================✍ Edit Include Menu ✍===================
     */

    public function editUserIncludeMenu(UpdateUserMenuExclude $request)
    {
        try {
            $this->begin();
            if (!$this->_UserMenuMobileInclude->edit($request)) {
                throw new Exception("Some Error Occurs On Editing Data");
            }
            $this->commit();
            return responseMsg(true, "Update Menu Include", "");
        } catch (Exception $e) {
            $this->rollback();
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userIncludeMenuList(Request $request)
    {
        try {

            $data = $this->_UserMenuMobileInclude->metaDtls();
            if ($request->moduleId) {
                $data->where("menu_mobile_masters.module_id", $request->moduleId);
            }
            if (isset($request->status)) {
                $data->where("user_menu_mobile_includes.is_active", $request->status);
            }
            if ($request->key) {
                $key = trim($request->key);
                $data->where(function ($query) use ($key) {
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
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function userIncludeMenuDtl(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "id" => "required|digits_between:0,9999999999",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $dtls = $this->_UserMenuMobileInclude->dtls($request->id);
            if (!$dtls) {
                throw new Exception("Invalid Id Pass");
            }
            return responseMsg(true, "Menu Dtls", remove_null($dtls));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",);
        }
    }

    public function UserMenuListForExcludeInclude(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "userId" => "required|digits_between:0,99999999999",
                "excludeIncludeType" => "required|string|in:Exclude,Include",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $user = Auth()->user();
            $userId = $request->userId;
            $menuRoleDetails = $this->_WfRoleusermap->getRoleDetailsByUserId($userId);

            $includeMenu = $this->_UserMenuMobileInclude->metaDtls()
                ->where("user_menu_mobile_includes.user_id", $userId)
                ->where("user_menu_mobile_includes.is_active", true)
                ->get();
            $excludeMenu = $this->_UserMenuMobileExclude->metaDtls()
                ->where("user_menu_mobile_excludes.user_id", $userId)
                ->where("user_menu_mobile_excludes.is_active", true)
                ->get();

            $menuList = $this->_MenuMobileMaster->metaDtls();
            $userIncludeMenu = $this->_UserMenuMobileInclude->unionDataWithRoleMenu()
                ->where("user_menu_mobile_includes.user_id", $userId)
                ->where("user_menu_mobile_includes.is_active", true);

            $userExcludeMenu = $this->_UserMenuMobileExclude->unionDataWithRoleMenu()
                ->where("user_menu_mobile_excludes.user_id", $userId)
                ->where("user_menu_mobile_excludes.is_active", true);

            if ($request->excludeIncludeType == "Exclude") {
                $menuList = $menuList->WhereIn("menu_mobile_role_maps.role_id", ($menuRoleDetails)->pluck("roleId"));
                $menuList = $menuList->union($userIncludeMenu);
                if ($excludeMenu->isNotEmpty()) {
                    $menuList = $menuList->WhereNotIn("menu_mobile_masters.id", ($excludeMenu)->pluck("menu_id"));
                }
            }
            // dd($menuRoleDetails);
            if ($request->excludeIncludeType == "Include") {
                $sql = "(
                    select menu_mobile_masters.id as menu_id
                    from menu_mobile_masters
                    join menu_mobile_role_maps on menu_mobile_role_maps.menu_id= menu_mobile_masters.id
                    where menu_mobile_role_maps.role_id in(" . (($menuRoleDetails)->implode("roleId", ",")) . ")
                    order by menu_mobile_masters.id ASC
                )
                union(
                    select menu_id
                    from user_menu_mobile_includes
                    where is_active = true
                    and user_id =" . $userId . "
                )";
                $givenMenu = collect(DB::select($sql))->pluck("menu_id");
                $menuList = $menuList->whereNotIn("menu_mobile_masters.id", $givenMenu);
                $menuList = $menuList->union($userExcludeMenu);
            }
            DB::enableQueryLog();
            $menuList = $menuList->get()->map(function ($val) {
                return $val->only(
                    [
                        "id",
                        "role_id",
                        "role_name",
                        "parent_id",
                        "module_id",
                        "serial",
                        "menu_string",
                        "route",
                        "icon",
                        "is_sidebar",
                        "is_menu",
                        "create",
                        "read",
                        "update",
                        "delete",
                        "module_name",
                    ]
                );
            });
            // dd(DB::getQueryLog(), ($menuRoleDetails)->pluck("roleId"));
            return responseMsgs(true, $request->excludeIncludeType . " Menu List", $menuList, 010101, "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, [$e->getMessage(), $e->getFile(), $e->getLine()], "", 010101, "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    public function UserMenuListForExcludeInclude3(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "userId" => "required|digits_between:0,99999999999",
                "excludeIncludeType" => "required|string|in:Exclude,Include",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $user = Auth()->user();
            $menuRoleDetails = $this->_WfRoleusermap->getRoleDetailsByUserId($request->userId);

            $includeMenu = $this->_UserMenuMobileInclude->metaDtls()
                ->where("user_menu_mobile_includes.user_id", $request->userId)
                ->where("user_menu_mobile_includes.is_active", true)
                ->get();
            $excludeMenu = $this->_UserMenuMobileExclude->metaDtls()
                ->where("user_menu_mobile_excludes.user_id", $request->userId)
                ->where("user_menu_mobile_excludes.is_active", true)
                ->get();

            $menuList = $this->_MenuMobileMaster->metaDtls();
            $userIncludeMenu = $this->_UserMenuMobileInclude->unionDataWithRoleMenu()
                ->where("user_menu_mobile_includes.user_id", $request->userId)
                ->where("user_menu_mobile_includes.is_active", true);
            if ($request->excludeIncludeType == "Exclude") {
                $menuList = $menuList->where(function ($query) use ($menuRoleDetails, $excludeMenu) {
                    $query->OrWhereIn("menu_mobile_role_maps.role_id", ($menuRoleDetails)->pluck("roleId"));
                    // if ($excludeMenu->isNotEmpty()) {
                    //     $query->OrWhereIn("menu_mobile_masters.id", ($includeMenu)->pluck("menu_id"));
                    // }
                });
                $menuList = $menuList->union($userIncludeMenu);
                if ($excludeMenu->isNotEmpty()) {
                    $menuList = $menuList->WhereNotIn("menu_mobile_masters.id", ($excludeMenu)->pluck("menu_id"));
                }
            }
            // dd($menuRoleDetails);
            if ($request->excludeIncludeType == "Include") {
                if ($excludeMenu->isNotEmpty()) {
                    $menuList = $menuList->OrWhereIn("menu_mobile_masters.id", ($excludeMenu)->pluck("menu_id"));
                }
                $menuList = $menuList->whereNot(function ($query) use ($menuRoleDetails, $includeMenu) {
                    $roleMenuId = $this->_MenuMobileRoleMap->select(DB::raw("DISTINCT(menu_id) AS menu_id"))
                        ->whereIn("role_id", ($menuRoleDetails)->pluck("roleId"))
                        ->get();
                    $query->WhereIN("menu_mobile_masters.id", $roleMenuId->pluck("menu_id"));
                    if ($includeMenu->isNotEmpty()) {
                        $query->OrWhereIn("menu_mobile_masters.id", ($includeMenu)->pluck("menu_id"));
                    }
                });
            }

            $menuList = $menuList->get()->map(function ($val) {
                return $val->only(
                    [
                        "id",
                        "role_id",
                        "role_name",
                        "parent_id",
                        "module_id",
                        "serial",
                        "menu_string",
                        "route",
                        "icon",
                        "is_sidebar",
                        "is_menu",
                        "create",
                        "read",
                        "update",
                        "delete",
                        "module_name",
                    ]
                );
            });
            // dd(DB::getQueryLog());
            return responseMsgs(true, $request->excludeIncludeType . " Menu List", $menuList, 010101, "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, [$e->getMessage(), $e->getFile(), $e->getLine()], "", 010101, "1.0", responseTime(), "POST", $request->deviceId);
        }
    }

    public function UserMenuListForExcludeInclude2(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "userId" => "required|digits_between:0,99999999999",
                "excludeIncludeType" => "required|string|in:Exclude,Include",
            ]
        );
        if ($validator->fails()) {
            return responseMsg(false, $validator->errors(), "");
        }
        try {
            $user = Auth()->user();
            $menuRoleDetails = $this->_WfRoleusermap->getRoleDetailsByUserId($request->userId);

            $includeMenu = $this->_UserMenuMobileInclude->metaDtls()
                ->where("user_menu_mobile_includes.user_id", $request->userId)
                ->where("user_menu_mobile_includes.is_active", true)
                ->get();
            $excludeMenu = $this->_UserMenuMobileExclude->metaDtls()
                ->where("user_menu_mobile_excludes.user_id", $request->userId)
                ->where("user_menu_mobile_excludes.is_active", true)
                ->get();

            $menuList = $this->_MenuMobileMaster->metaDtls();

            $userIncludeMenu = $this->_UserMenuMobileInclude->unionDataWithRoleMenu()
                ->where("user_menu_mobile_includes.user_id", $request->userId)
                ->where("user_menu_mobile_includes.is_active", true);
            $menuList = $menuList->union($userIncludeMenu);
            if ($request->excludeIncludeType == "Exclude") {
                $menuList = $menuList->where(function ($query) use ($menuRoleDetails, $includeMenu) {
                    $roleMenuId = $this->_MenuMobileRoleMap->select(DB::raw("DISTINCT(menu_id) AS menu_id"))
                        ->whereIn("role_id", ($menuRoleDetails)->pluck("roleId"))
                        ->get();
                    $query->WhereIN("menu_mobile_masters.id", $roleMenuId->pluck("menu_id"));
                });
            }

            if ($request->excludeIncludeType == "Include") {
                if ($excludeMenu->isNotEmpty()) {
                    $menuList = $menuList->OrWhereIn("menu_mobile_masters.id", ($excludeMenu)->pluck("menu_id"));
                }
                $menuList = $menuList->whereNot(function ($query) use ($menuRoleDetails, $includeMenu) {
                    $roleMenuId = $this->_MenuMobileRoleMap->select(DB::raw("DISTINCT(menu_id) AS menu_id"))
                        ->whereIn("role_id", ($menuRoleDetails)->pluck("roleId"))
                        ->get();
                    $query->WhereIN("menu_mobile_masters.id", $roleMenuId->pluck("menu_id"));
                    if ($includeMenu->isNotEmpty()) {
                        $query->OrWhereIn("menu_mobile_masters.id", ($includeMenu)->pluck("menu_id"));
                    }
                });
            }

            $menuList = $menuList->get()->map(function ($val) {
                return $val->only(
                    [
                        "id",
                        "role_id",
                        "role_name",
                        "parent_id",
                        "module_id",
                        "serial",
                        "menu_string",
                        "route",
                        "icon",
                        "is_sidebar",
                        "is_menu",
                        "create",
                        "read",
                        "update",
                        "delete",
                        "module_name",
                    ]
                );
            });
            dd(DB::getQueryLog());

            return responseMsgs(true, $request->excludeIncludeType . " Menu List", $menuList, 010101, "1.0", responseTime(), "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, [$e->getMessage(), $e->getFile(), $e->getLine()], "", 010101, "1.0", responseTime(), "POST", $request->deviceId);
        }
    }
}
