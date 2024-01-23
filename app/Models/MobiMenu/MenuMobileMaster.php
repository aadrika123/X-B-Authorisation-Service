<?php

namespace App\Models\MobiMenu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMobileMaster extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $guarded = [];

    public function store($data)
    {
        $inputs = [
            "role_id"       =>  $data->roleId??null,
            "parent_id"     =>  $data->parentId??0,
            "module_id"     =>  $data->moduleId??null,
            "serial"        =>  $data->serial??null,
            "menu_string"   =>  $data->menuName??null,
            "route"         =>  $data->path??null,
            "icon"          =>  $data->icon??null,
            "is_sidebar"    =>  $data->isSidebar??false,
            "is_menu"       =>  $data->isMnu??false,
            "create"        =>  $data->create??false,
            "read"          =>  $data->read??false,
            "update"        =>  $data->update??false,
            "delete"        =>  $data->delete??false,
        ];
        return MenuMobileMaster::create($inputs)->id;
    }

    public function edit($data)
    {
        $inputs = [
            "role_id"       =>  $data->roleId??null,
            "parent_id"     =>  $data->parentId??0,
            "module_id"     =>  $data->moduleId??null,
            "serial"        =>  $data->serial??null,
            "menu_string"   =>  $data->menuName??null,
            "route"         =>  $data->path??null,
            "icon"          =>  $data->icon??null,
            "is_sidebar"    =>  $data->isSidebar??false,
            "is_menu"       =>  $data->iseMnu??false,
            "create"        =>  $data->create??false,
            "read"          =>  $data->read??false,
            "update"        =>  $data->update??false,
            "delete"        =>  $data->delete??false,
        ];
        if(isset($data->status))
        {
            $inputs["is_active"] = $data->status;
        }
        return self::where("id",$data->id)->update($inputs);
    }

    public function metaDtls()
    {
        return self::select("menu_mobile_masters.*","wf_roles.role_name","module_masters.module_name","parents.menu_string AS parent_menu")
                ->leftjoin("wf_roles","wf_roles.id","menu_mobile_masters.role_id")
                ->leftjoin("module_masters","module_masters.id","menu_mobile_masters.module_id")
                ->leftjoin("menu_mobile_masters AS parents","parents.id","menu_mobile_masters.parent_id");
    }

    public function dtls ($id)
    {
        return $this->metaDtls()->where("menu_mobile_masters.id",$id)
                ->first();
    }

}
