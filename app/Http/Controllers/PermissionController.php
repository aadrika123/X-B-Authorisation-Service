<?php

namespace App\Http\Controllers;

use App\Models\ActionMaster;
use App\Models\Workflows\WfRoleusermap;
use Exception;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * | Get Permission by User
     */
    public function getUserPermission(Request $req)
    {
        // $req->validate([
        //     'module' => 'required'
        // ]);
        try {
            $req->validate([
                'module' => 'required'
            ]);
            // Variable Assignments
            $userId = authUser($req)->id;
            $mWfRoleUserMap = new WfRoleusermap();
            $mActionMaster = new ActionMaster();

            // Derivative Assignments
            $wfRoles = $mWfRoleUserMap->getRoleIdByUserId($userId);
            $roleIds = collect($wfRoles)->map(function ($item) {
                return $item->wf_role_id;
            });
            $mActionMaster->_roleIds = $roleIds;
            $permissions = $mActionMaster->getPermissionsByRoleId()
                ->where('action_masters.module_id', $req->module)
                ->get();
            return responseMsgs(true, "Permissions", remove_null($permissions), '100101', '1.0', '', 'POST', $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), '', '100101', '1.0', '', 'POST', $req->deviceId ?? "");
        }
    }
}
