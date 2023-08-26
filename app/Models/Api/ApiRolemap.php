<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApiRolemap extends Model
{
    use HasFactory;

    /**
     * Create Role Map
     */
    public function addRoleMap($req)
    {
        $data = new ApiRolemap;
        $data->api_id       = $req->apiId;
        $data->api_role_id  = $req->apiRoleId;
        $data->is_suspended = $req->isSuspended ?? false;
        $data->save();
    }

    /**
     * Update Role Map
     */
    public function updateRoleMap($req)
    {
        $data = ApiRolemap::find($req->id);
        $data->api_id       = $req->apiId ?? $data->api_id;
        $data->api_role_id  = $req->apiRoleId ?? $data->api_role_id;
        $data->is_suspended = $req->isSuspended ?? $data->is_suspended;
        $data->save();
    }

    /**
     * | Menu Role Map list
     */
    public function roleMaps()
    {
        $data = DB::table('api_rolemaps')
            ->select(
                'api_rolemaps.id',
                'api_rolemaps.api_id',
                'api_rolemaps.api_role_id',
                'api_role_name',
                'api_masters.description',
                'api_masters.category',
                'api_masters.end_point',
                'api_masters.tags',
                'api_rolemaps.is_suspended'

            )
            ->leftjoin('api_masters', 'api_masters.id', 'api_rolemaps.api_id')
            ->join('api_roles', 'api_roles.id', 'api_rolemaps.api_role_id')
            ->orderByDesc('api_rolemaps.id');
        return $data;
    }

    /**
     * Delete Role Map
     */
    public function deleteRoleMap($req)
    {
        $data = ApiRolemap::find($req->id);
        $data->is_suspended = true;
        $data->save();
    }
}
