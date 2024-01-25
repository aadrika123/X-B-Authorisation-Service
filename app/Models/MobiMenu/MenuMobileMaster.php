<?php

namespace App\Models\MobiMenu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMobileMaster extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    public function store($data)
    {
        $inputs = [
            "parent_id"     =>  $data->parentId ?? 0,
            "module_id"     =>  $data->moduleId ?? null,
            "serial"        =>  $data->serial ?? null,
            "menu_string"   =>  $data->menuName ?? null,
            "route"         =>  $data->path ?? null,
            "icon"          =>  $data->icon ?? null,
        ];
        return MenuMobileMaster::create($inputs)->id;
    }

    public function edit($data)
    {
        $inputs = [
            "parent_id"     =>  $data->parentId ?? 0,
            "module_id"     =>  $data->moduleId ?? null,
            "serial"        =>  $data->serial ?? null,
            "menu_string"   =>  $data->menuName ?? null,
            "route"         =>  $data->path ?? null,
            "icon"          =>  $data->icon ?? null,
        ];
        if (isset($data->status)) {
            $inputs["is_active"] = $data->status;
        }
        return self::where("id", $data->id)->update($inputs);
    }

    public function metaDtls()
    {
        return self::select(
            "menu_mobile_masters.*",
            "menu_mobile_role_maps.id AS role_menu_map_id",
            "menu_mobile_role_maps.is_sidebar",
            "menu_mobile_role_maps.is_menu",
            "menu_mobile_role_maps.create",
            "menu_mobile_role_maps.read",
            "menu_mobile_role_maps.update",
            "menu_mobile_role_maps.delete",
            "menu_mobile_role_maps.role_id",
            "wf_roles.role_name",
            "module_masters.module_name",
            "parents.menu_string AS parent_menu"
        )
            ->leftjoin("menu_mobile_role_maps", "menu_mobile_role_maps.menu_id", "menu_mobile_masters.id")
            ->leftjoin("wf_roles", "wf_roles.id", "menu_mobile_role_maps.role_id")
            ->leftjoin("module_masters", "module_masters.id", "menu_mobile_masters.module_id")
            ->leftjoin("menu_mobile_masters AS parents", "parents.id", "menu_mobile_masters.parent_id");
    }

    public function dtls($id, $roleId)
    {
        return $this->metaDtls()->where("menu_mobile_masters.id", $id)
            ->where("menu_mobile_role_maps.role_id", $roleId)
            ->first();
    }
}
