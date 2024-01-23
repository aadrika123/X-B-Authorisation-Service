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

}
