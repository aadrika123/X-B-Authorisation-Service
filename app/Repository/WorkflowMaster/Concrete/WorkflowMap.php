<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\Interface\iWorkflowMapRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfWardUser;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Models\UlbWardMaster;
use App\Models\User;
use App\Repository\WorkflowMaster\Interface\iWorkflowRoleUserMapRepository;
use Exception;


//============================================================================================
//=============================       NEW MAPPING          ===================================
//============================================================================================

class WorkflowMap implements iWorkflowMapRepository
{
    // //role in a workflow
    // public function getRoleByWorkflow(Request $request)
    // {
    //     $ulbId = authUser()->ulb_id;
    //     $request->validate([
    //         'workflowId' => 'required|int'
    //     ]);
    //     $roles = WfWorkflowrolemap::select('wf_roles.id as role_id', 'wf_roles.role_name')
    //         ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
    //         ->join('wf_workflows', 'wf_workflows.id', 'wf_workflowrolemaps.workflow_id')
    //         ->where('wf_workflows.ulb_id', $ulbId)
    //         ->where('workflow_id', $request->workflowId)
    //         ->where(function ($where) {
    //             $where->orWhereNotNull("wf_workflowrolemaps.forward_role_id")
    //                 ->orWhereNotNull("wf_workflowrolemaps.backward_role_id")
    //                 ->orWhereNotNull("wf_workflowrolemaps.serial_no");
    //         })
    //         ->orderBy('serial_no')
    //         ->get();

    //     return responseMsg(true, "Data Retrived", $roles);
    // }


    //get role details by 
    // public function getRoleDetails(Request $request)
    // {
    //     $ulbId = auth()->user()->ulb_id;
    //     $request->validate([
    //         'workflowId' => 'required|int',
    //         'wfRoleId' => 'required|int'

    //     ]);
    //     $roleDetails = DB::table('wf_workflowrolemaps')
    //         ->select(
    //             'wf_workflowrolemaps.id',
    //             'wf_workflowrolemaps.workflow_id',
    //             'wf_workflowrolemaps.wf_role_id',
    //             'wf_workflowrolemaps.forward_role_id',
    //             'wf_workflowrolemaps.backward_role_id',
    //             'wf_workflowrolemaps.is_initiator',
    //             'wf_workflowrolemaps.is_finisher',
    //             'r.role_name as forward_role_name',
    //             'rr.role_name as backward_role_name'
    //         )
    //         ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
    //         ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
    //         ->where('workflow_id', $request->workflowId)
    //         ->where('wf_role_id', $request->wfRoleId)
    //         ->first();
    //     return responseMsg(true, "Data Retrived", remove_null($roleDetails));
    // }



    //getting data of user & ulb  by selecting  ward user id
    //m_users && m_ulb_wards  && wf_ward_users

    public function getUserById(Request $request)
    {
        $request->validate([
            'wardUserId' => 'required|int'
        ]);
        $users = WfWardUser::where('wf_ward_users.id', $request->wardUserId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_ward_users.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->get(['users.*', 'ulb_ward_masters.*']);
        return responseMsg(true, "Data Retrived", $users);
    }



    // tables = wf_workflows + wf_masters
    // ulbId -> workflow name
    // workflows in a ulb
    public function getWorkflowNameByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'required|int'
        ]);

        $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)
            ->select('wf_masters.id', 'wf_masters.workflow_name')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return responseMsg(true, "Data Retrived", $workkFlow);
    }


    // tables = wf_workflows + wf_workflowrolemap + wf_roles
    // ulbId -> rolename
    // roles in a ulb 
    public function getRoleByUlb(Request $request)
    {
        //validating

        $request->validate([
            'ulbId' => 'required|int'
        ]);
        try {
            $workkFlow = WfWorkflow::where('ulb_id', $request->ulbId)

                ->join('wf_workflowrolemaps', 'wf_workflowrolemaps.workflow_id', '=', 'wf_workflows.id')
                ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
                ->get('wf_roles.role_name');
            return responseMsg(true, "Data Retrived", $workkFlow);
        } catch (Exception $e) {
            return $e;
        }
    }

    //working
    //table = ulb_ward_master
    //ulbId->WardName
    //wards in ulb
    public function getWardByUlb(Request $request)
    {
        //validating
        $request->validate([
            'ulbId' => 'nullable'
        ]);
        $ulbId = $request->ulbId ?? authUser()->ulb_id;
        $wards = collect();
        $workkFlow = UlbWardMaster::select(
            'id',
            'ulb_id',
            'ward_name',
            'old_ward_name'
        )
            ->where('ulb_id', $ulbId)
            ->where('status', 1)
            ->orderby('id')
            ->get();

        $groupByWards = $workkFlow->groupBy('ward_name');
        foreach ($groupByWards as $ward) {
            $wards->push(collect($ward)->first());
        }
        $wards->sortBy('ward_name')->values();
        return responseMsg(true, "Data Retrived", remove_null($wards));
    }

    //role_id -> users
    //users in a role
    public function getUserByRole(Request $request)
    {
        $workkFlow = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get('users.user_name');
        return responseMsg(true, "Data Retrived", $workkFlow);
    }
}
