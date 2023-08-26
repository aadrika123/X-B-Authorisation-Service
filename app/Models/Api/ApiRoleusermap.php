<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApiRoleusermap extends Model
{
    use HasFactory;


    /**
     * | Create Role Map
     */
    public function addRoleUser($req)
    {
        $data = new ApiRoleusermap;
        $data->api_role_id  = $req->apiRoleId;
        $data->user_id      = $req->userId;
        $data->is_suspended = $req->isSuspended ?? false;
        $data->save();
    }

    /**
     * | Update Role Map
     */
    public function updateRoleUser($req)
    {
        $data = ApiRoleusermap::find($req->id);
        $data->api_role_id  = $req->apiRoleId   ?? $data->api_role_id;
        $data->user_id      = $req->userId      ?? $data->user_id;
        $data->is_suspended = $req->isSuspended ?? $data->is_suspended;
        $data->save();
    }

    /**
     * | Menu Role Map list
     */
    public function listRoleUser()
    {
        $data = DB::table('api_roleusermaps')
            ->select('api_roleusermaps.id', 'api_roles.api_role_name', 'users.name as user_name')
            ->join('api_roles', 'api_roles.id', 'api_roleusermaps.api_role_id')
            ->join('users', 'users.id', 'api_roleusermaps.user_id')
            ->where('api_roleusermaps.is_suspended', false)
            ->orderBy('api_roleusermaps.id');
        return $data;
    }

    /**
     * Delete Role Map
     */
    public function deleteRoleUser($req)
    {
        $data = ApiRoleusermap::find($req->id);
        $data->is_suspended = true;
        $data->save();
    }

    /**
     * created by : Ashutosh Kumar
     * created at : 14-07-23
     */
    public function getRoleByUserId()
    {
        return ApiRoleusermap::select('api_roleusermaps.id', 'api_roleusermaps.api_role_id', 'api_roles.api_role_name')
            ->join('api_roles', 'api_roles.id', 'api_roleusermaps.api_role_id')
            ->where('api_roleusermaps.is_suspended', false)
            ->orderBy('api_roleusermaps.id');
    }
}
