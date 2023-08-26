<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiRole;
use App\Models\Api\ApiRoleusermap;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiRoleUserMapController extends Controller
{
    /**
     * | Create api Role Mapping
     */
    public function createRoleUser(Request $req)
    {
        try {
            $req->validate([
                'userId'     => 'required',
                'apiRoleId' => 'required',
                'permissionStatus' => 'required'
            ]);
            $mApiRoleusermap = new ApiRoleusermap();
            $checkExisting = $mApiRoleusermap->where('user_id', $req->userId)
                ->where('api_role_id', $req->apiRoleId)
                ->first();

            if ($req->permissionStatus == 0)
                $isSuspended = true;

            if ($req->permissionStatus == 1)
                $isSuspended = false;

            if ($checkExisting) {
                $req->merge([
                    'id' => $checkExisting->id,
                    'isSuspended' => $isSuspended
                ]);
                $mApiRoleusermap->updateRoleUser($req);
            } else {
                $req->merge([
                    'isSuspended' => $isSuspended,
                ]);
                $mApiRoleusermap->addRoleUser($req);
            }

            return responseMsgs(true, "Data Saved", "", "121301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "121301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Update api Role Mapping
     */
    public function updateRoleUser(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $mApiRoleusermap = new ApiRoleusermap();
            $mApiRoleusermap->updateRoleUser($req);

            return responseMsgs(true, "Data Updated", [], "121302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "121302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | api Role Mapping By id
     */
    public function roleUserbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);

            $mApiRoleusermap = new ApiRoleusermap();
            $list  = $mApiRoleusermap->listRoleUser($req)
                ->where('api_roleusermaps.id', $req->id)
                ->first();

            return responseMsgs(true, "Api Role Map", remove_null($list), "121303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "121303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * | api Role Mapping List
     */
    public function getAllRoleUser(Request $req)
    {
        try {
            $mApiRoleusermap = new ApiRoleusermap();
            $menuRole = $mApiRoleusermap->listRoleUser()->get();

            return responseMsgs(true, "Api Role Map List", $menuRole, "121304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "121304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Delete api Role Mapping
     */
    public function deleteRoleUser(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $delete = new ApiRoleusermap();
            $delete->deleteRoleUser($req);

            return responseMsgs(true, "Data Deleted", '', "121305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "121305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * created by : Ashutosh Kumar
     * created at : 14-07-23
     */

    // Roles by User Id
    public function roleByUserId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId' => 'required|integer'
        ]);
        if ($validator->fails())
            return validationError($validator);

        try {
            $apiRoleUserMap = new ApiRoleusermap;
            $data = $apiRoleUserMap->getRoleByUserId()
                ->where('api_roleusermaps.user_id', $req->userId)
                ->get();

            $query = "select 
                            a.id,
                            a.api_role_name,
                            ar.user_id,
                            case 
                                when ar.user_id is null then false
                                else
                                    true  
                            end as permission_status
                    
                        from api_roles as a
                        left join (select * from api_roleusermaps where user_id=$req->userId and is_suspended=false) as ar on ar.api_role_id=a.id
                        order by a.id";

            $data = DB::select($query);

            return responseMsgs(true, 'API Role Map By User Id', $data, "121306", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "121306", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    //Roles Except Given user id
    public function roleExcludingUserId(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId' => 'required|integer'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $apiRoleUserMap = new ApiRoleusermap;
            $mApiRole = new ApiRole();
            $apiRole = $apiRoleUserMap->getRoleByUserId()
                ->where('api_roleusermaps.user_id', $req->userId)
                ->get();

            $apiRoleId = $apiRole->pluck('api_role_id');

            $roleList = $mApiRole->listApiRole()
                ->whereNotIn('api_roles.id', $apiRoleId)
                ->get();

            return responseMsgs(true, 'API Role Map Except User Id', $roleList, "121307", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "121307", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}
