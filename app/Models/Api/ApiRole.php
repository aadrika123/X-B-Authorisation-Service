<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRole extends Model
{
    use HasFactory;

    /**
     * | Save Api
     */
    public function store($request)
    {
        $newApis = new ApiRole();
        $newApis->api_role_name  =  $request->apiRoleName;
        $newApis->created_by     =  authUser()->id;
        $newApis->save();
        return $newApis;
    }

    /**
     * | Update the Api master details
     */
    public function edit($request)
    {
        $refValues = ApiRole::where('id', $request->id)->first();
        ApiRole::where('id', $request->id)
            ->update(
                [
                    'api_role_name' => $request->apiRoleName ?? $refValues->api_role_name,
                ]
            );
    }

    /**
     * | List of Api Role
     */
    public function listApiRole()
    {
        return  ApiRole::select('api_roles.id', 'api_role_name', 'is_suspended', 'users.name as created_by')
            ->where('api_roles.is_suspended', false)
            ->join('users', 'users.id', 'api_roles.created_by')
            ->orderbydesc('id');
    }
}
