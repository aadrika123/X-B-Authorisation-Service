<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiRolemap;
use Exception;
use Illuminate\Http\Request;
use Spatie\FlareClient\Api;

class ApiRoleMapController extends Controller
{
    /**
     * |  Create Api Role Mapping
     */
    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'apiId'     => 'required',
                'apiRoleId' => 'required',
                'isSuspended' => 'nullable|boolean'
            ]);
            $mApiRolemap = new ApiRolemap();
            $checkExisting = $mApiRolemap->where('api_id', $req->apiId)
                ->where('api_role_id', $req->apiRoleId)
                ->first();

            if ($checkExisting) {
                $req->merge([
                    'id' => $checkExisting->id,
                    'isSuspended' => $req->isSuspended
                ]);
                $mApiRolemap->updateRoleMap($req);
            } else {
                $mApiRolemap->addRoleMap($req);
            }


            // if ($checkExisting)
            //     throw new Exception('Api Already Maps to Api Role');
            // $mreqs = [
            //     'apiId'     => $req->apiId,
            //     'apiRoleId' => $req->apiRoleId
            // ];
            // $mApiRolemap->addRoleMap($mreqs);

            return responseMsg(true, "Data Saved", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Update Api Role Mapping
     */
    public function updateRoleMap(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $mApiRolemap = new ApiRolemap();
            $list  = $mApiRolemap->updateRoleMap($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Api Role Mapping By id
     */
    public function roleMapbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);

            $mApiRolemap = new ApiRolemap();
            $list  = $mApiRolemap->roleMaps($req)
                ->where('api_rolemaps.id', $req->id)
                ->where('api_rolemaps.is_suspended', false)
                ->first();

            return responseMsg(true, "Api Role Map", remove_null($list));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | Api Role Mapping List
     */
    public function getAllRoleMap()
    {
        try {
            $mApiRolemap = new ApiRolemap();
            $menuRole = $mApiRolemap->roleMaps()
                ->where('api_rolemaps.is_suspended', false)
                ->get();

            return responseMsg(true, "Api Role Map List", $menuRole);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Delete Api Role Mapping
     */
    public function deleteRoleMap(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $delete = new ApiRolemap();
            $delete->deleteRoleMap($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
