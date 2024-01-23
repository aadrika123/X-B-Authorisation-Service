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
        ];
        if(isset($data->status))
        {
            $inputs["is_active"] = $data->status;
        }
        return self::where("id",$data->id)->update($inputs);
    }

    public function metaDtls()
    {
        return self::select(self::getTable().".*","wf_roles.role_name","module_masters.module_name","parents.menu_string AS parent_menu")
                ->leftjoin("wf_roles","wf_roles.id",self::getTable().".role_id")
                ->leftjoin("module_masters","module_masters.id",self::getTable().".module_id")
                ->leftjoin(self::getTable()." AS parents","parents.id",self::getTable().".parent_id");
    }

    public function dtls ($id)
    {
        return $this->metaDtls()->where(self::getTable().".id",$id)
                ->first();
    }

}
