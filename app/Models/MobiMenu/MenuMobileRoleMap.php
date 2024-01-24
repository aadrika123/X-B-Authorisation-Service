<?php

namespace App\Models\MobiMenu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMobileRoleMap extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    public function store($data)
    {
        $inputs = [
            "menu_id"       =>  $data->menuId ?? null,
            "role_id"       =>  $data->roleId ?? null,
            "is_sidebar"    =>  $data->isSidebar ?? false,
            "is_menu"       =>  $data->isMenu ?? false,
            "create"        =>  $data->create ?? false,
            "read"          =>  $data->read ?? false,
            "update"        =>  $data->update ?? false,
            "delete"        =>  $data->delete ?? false,
        ];
        return MenuMobileRoleMap::create($inputs)->id;
    }

    public function edit($data)
    {
        $inputs = [
            "menu_id"       =>  $data->menuId ?? null,
            "role_id"       =>  $data->roleId ?? null,
            "is_sidebar"    =>  $data->isSidebar ?? false,
            "is_menu"       =>  $data->isMenu ?? false,
            "create"        =>  $data->create ?? false,
            "read"          =>  $data->read ?? false,
            "update"        =>  $data->update ?? false,
            "delete"        =>  $data->delete ?? false,
        ];
        if (isset($data->status)) {
            $inputs["is_active"] = $data->status;
        }
        return self::where("id", $data->roleMenuId)->update($inputs);
    }
}
