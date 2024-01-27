<?php

namespace App\Models\MobiMenu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserMenuMobileExclude extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    public function store($data)
    {
        $inputs = [
            "user_id"       =>  $data->userId,
            "menu_id"       =>  $data->menuId,
            "is_sidebar"    =>  $data->isSidebar ?? null,
            "is_menu"       =>  $data->isMenu ?? null,
            "create"        =>  $data->create ?? null,
            "read"          =>  $data->read ?? null,
            "update"        =>  $data->update ?? null,
            "delete"        =>  $data->delete ?? null,
        ];
        $test = self::where($inputs)->first();
        if ($test) {
            $inputs["is_active"] = true;
        }
        UserMenuMobileInclude::where($inputs)->update(["is_active" => false]);
        return $test ? $test->update($inputs) : UserMenuMobileExclude::create($inputs)->id;
    }

    public function edit($data)
    {
        $inputs = [
            "user_id"       =>  $data->userId,
            "menu_id"       =>  $data->menuId,
            "is_sidebar"    =>  $data->isSidebar ?? null,
            "is_menu"       =>  $data->isMenu ?? null,
            "create"        =>  $data->create ?? null,
            "read"          =>  $data->read ?? null,
            "update"        =>  $data->update ?? null,
            "delete"        =>  $data->delete ?? null,
        ];
        if (isset($data->status)) {
            $inputs["is_active"] = $data->status;
        }
        UserMenuMobileInclude::where($inputs)->update(["is_active" => false]);
        return self::where("id", $data->id)->update($inputs);
    }

    public function metaDtls()
    {
        return self::select(
            "user_menu_mobile_excludes.*",
            "users.name",
            "menu_mobile_role_maps.role_id",
            "menu_mobile_masters.parent_id",
            "menu_mobile_masters.route",
            "menu_mobile_masters.menu_string",
            "wf_roles.role_name",
            "module_masters.module_name",
            "parents.menu_string AS parent_menu",
            DB::raw("
                CASE when user_menu_mobile_excludes.is_sidebar is null THEN menu_mobile_role_maps.is_sidebar ELSE user_menu_mobile_excludes.is_sidebar END AS is_sidebar,
                CASE when user_menu_mobile_excludes.is_menu is null THEN menu_mobile_role_maps.is_menu ELSE user_menu_mobile_excludes.is_menu END AS is_menu,
                CASE when user_menu_mobile_excludes.create is null THEN menu_mobile_role_maps.create ELSE user_menu_mobile_excludes.create END AS create,
                CASE when user_menu_mobile_excludes.read is null THEN menu_mobile_role_maps.read ELSE user_menu_mobile_excludes.read END AS read,
                CASE when user_menu_mobile_excludes.update is null THEN menu_mobile_role_maps.update ELSE user_menu_mobile_excludes.update END AS update,
                CASE when user_menu_mobile_excludes.delete is null THEN menu_mobile_role_maps.delete ELSE user_menu_mobile_excludes.delete END AS delete
            ")
        )
            ->leftjoin("users", "users.id", "user_menu_mobile_excludes.user_id")
            ->leftjoin("menu_mobile_masters", "menu_mobile_masters.id", "user_menu_mobile_excludes.menu_id")
            ->leftjoin("menu_mobile_role_maps", "menu_mobile_role_maps.menu_id", "menu_mobile_masters.id")
            ->leftjoin("wf_roles", "wf_roles.id", "menu_mobile_role_maps.role_id")
            ->leftjoin("module_masters", "module_masters.id", "menu_mobile_masters.module_id")
            ->leftjoin("menu_mobile_masters AS parents", "parents.id", "menu_mobile_masters.parent_id");
    }

    public function dtls($id)
    {
        return $this->metaDtls()->where("user_menu_mobile_excludes.id", $id)
            ->first();
    }

    public function unionDataWithRoleMenu()
    {
        return self::select(DB::Raw(
            "menu_mobile_masters.*,
            null AS role_menu_map_id,
            user_menu_mobile_excludes.is_sidebar,
            user_menu_mobile_excludes.is_menu,
            user_menu_mobile_excludes.create,
            user_menu_mobile_excludes.read,
            user_menu_mobile_excludes.update,
            user_menu_mobile_excludes.delete,
            null as role_id,
            user_menu_mobile_excludes.is_active,
            null as role_name,
            module_masters.module_name,
            parents.menu_string AS parent_menu"
        ))
            ->join("menu_mobile_masters", "menu_mobile_masters.id", "user_menu_mobile_excludes.menu_id")
            ->leftjoin("module_masters", "module_masters.id", "menu_mobile_masters.module_id")
            ->leftjoin("menu_mobile_masters as parents", "parents.id", "menu_mobile_masters.parent_id");
    }
}
