<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{

    /**
     * | Create Workflow Role
     */
    public function createRole(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['roleName' => 'required']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $ulbId = authUser()->ulb_id;
            $mWfRole = new WfRole();
            $mWfWorkflowrolemaps = new WfWorkflowrolemap();
            $mWfWorkflow = new WfWorkflow();
            $role = $mWfRole->addRole($req);
            $roleId = $role->id;
            $roleMapRequest = collect();

            // if ($ulbId) {
            //     $workflows = $mWfWorkflow->getWorklowByUlbId($ulbId);
            //     foreach ($workflows as $workflow) {
            //         $data->workflowId  = $workflow->id;
            //         $data->wfRoleId    = $roleId;
            //         $data->isSuspended = true;
            //         // $roleMapRequest->push($data);
            //         // Workflow Role Mapping at the time of Role Creation.
            //         $mWfWorkflowrolemaps->addRoleMap($data);
            //     }
            // }
            // dd($req);

            return responseMsgs(true, "Successfully Saved", "", "120301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Update Workflow Role
     */
    public function editRole(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id' => 'required',
                'roleName' => 'required',
                'isSuspended' => 'required|bool'
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $mWfRole = new WfRole();
            $role    = $mWfRole->updateRole($req);

            return responseMsgs(true, "Successfully Updated", $role, "120302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get Role by Id
     */
    public function getRole(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['id' => 'required|numeric']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $mWfRole = new WfRole();
            $list  = $mWfRole->roleList()
                ->where('wf_roles.id', $req->id)
                ->first();

            if (!$list)
                throw new Exception("No data Found");

            return responseMsgs(true, "Role List", $list, "120303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Role List
     */
    public function getAllRoles(Request $req)
    {
        try {
            $mWfRole = new WfRole();
            $roles = $mWfRole->roleList()
                ->get();

            if ($roles->isEmpty())
                throw new Exception("No data Found");

            return responseMsgs(true, "All Role List", $roles, "120304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Delete Role
     */
    public function deleteRole(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['id' => 'required|numeric']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $mWfRole = new WfRole();
            $mWfRole->deleteRole($req);

            return responseMsgs(true, "Data Deleted", "", "120305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Role List
     */
    public function selectedRole(Request $req)
    {
        try {
            $mWfRole = new WfRole();
            $roles = $mWfRole->roleList()
                ->where('can_view', true)
                ->get();

            if ($roles->isEmpty())
                throw new Exception("No data Found");

            return responseMsgs(true, "Selected Role List", $roles, "120306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
