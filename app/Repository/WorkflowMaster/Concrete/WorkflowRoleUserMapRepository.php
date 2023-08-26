<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use Illuminate\Http\Request;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\WorkflowRoleUserMapController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */



class WorkflowRoleUserMapRepository implements iWorkflowRoleUserMapRepository
{
    private $_redis;
    public function __construct()
    {
        // $this->_redis = Redis::connection();
    }


    /**
     * | Enable or Disable the User Role Permission
     * | @param req
     * | Status-closed
     * | RunTime Complexity-353 ms
     * | Rating-2
     */
    public function updateUserRoles($req)
    {
        try {
            // Redis::del('roles-user-u-' . $req->userId);                                 // Flush Key of the User Role Permission

            $userRoles = WfRoleusermap::where('wf_role_id', $req->roleId)
                ->where('user_id', $req->userId)
                ->first();

            if ($userRoles) {                                                           // If Data Already Existing
                switch ($req->status) {
                    case 1:
                        $userRoles->is_suspended = 0;
                        $userRoles->save();
                        return responseMsg(true, "Successfully Enabled the Role Permission for User", "");
                        break;
                    case 0:
                        $userRoles->is_suspended = 1;
                        $userRoles->save();
                        return responseMsg(true, "Successfully Disabled the Role Permission", "");
                        break;
                }
            }

            $userRoles = new WfRoleusermap();
            $userRoles->wf_role_id = $req->roleId;
            $userRoles->user_id = $req->userId;
            $userRoles->created_by = authUser()->id;
            $userRoles->save();

            return responseMsg(true, "Successfully Enabled the Role Permission for the User", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //role of logged in user
    public function roleUser()
    {
        $userId = authUser()->id;
        $role = WfRoleusermap::select('wf_roleusermaps.*')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $role;
    }
}
