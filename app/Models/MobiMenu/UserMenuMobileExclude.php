<?php

namespace App\Models\MobiMenu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMenuMobileExclude extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $guarded = [];

    public function store($data)
    {
        $inputs = [
            "user_id"       =>  $data->userId,
            "menu_id"       =>  $data->menuId
        ];
        $test = self::where($inputs)->first();
        if($test)  
        {
            $inputs["is_active"] = true;
        }      
        return $test ? $test->update($inputs) : UserMenuMobileExclude::create($inputs)->id;
    }

    public function edit($data)
    {
        $inputs = [
            "user_id"       =>  $data->userId,
            "menu_id"       =>  $data->menuId
        ];
        if(isset($data->status))
        {
            $inputs["is_active"] = $data->status;
        }
        return self::where("id",$data->id)->update($inputs);
    }

    public function metaDtls()
    {
        return self::select("user_menu_mobile_excludes.*","users.name",
                            "menu_mobile_masters.role_id","menu_mobile_masters.parent_id","menu_mobile_masters.route",
                            "menu_mobile_masters.menu_string",
                            "wf_roles.role_name","module_masters.module_name","parents.menu_string AS parent_menu"
                            )
                ->leftjoin("users","users.id","user_menu_mobile_excludes.user_id")
                ->leftjoin("menu_mobile_masters","menu_mobile_masters.id","user_menu_mobile_excludes.menu_id")
                ->leftjoin("wf_roles","wf_roles.id","menu_mobile_masters.role_id")                
                ->leftjoin("module_masters","module_masters.id","menu_mobile_masters.module_id")
                ->leftjoin("menu_mobile_masters AS parents","parents.id","menu_mobile_masters.parent_id");
    }

    public function dtls ($id)
    {
        return $this->metaDtls()->where("user_menu_mobile_excludes.id",$id)
                ->first();
    }
}
