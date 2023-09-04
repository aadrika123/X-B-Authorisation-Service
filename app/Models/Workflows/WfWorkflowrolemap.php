<?php

namespace App\Models\Workflows;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfWorkflowrolemap extends Model
{
    use HasFactory;


    /**
     * Create Role Map
     */
    public function addRoleMap($req)
    {
        $createdBy = Auth()->user()->id;
        $data = new WfWorkflowrolemap;
        $data->workflow_id                    = $req->workflowId;
        $data->wf_role_id                     = $req->wfRoleId;
        $data->is_suspended                   = $req->isSuspended ?? false;
        $data->forward_role_id                = $req->forwardRoleId;
        $data->backward_role_id               = $req->backwardRoleId;
        $data->is_initiator                   = $req->isInitiator;
        $data->is_finisher                    = $req->isFinisher;
        $data->allow_full_list                = $req->allowFullList ?? false;
        $data->can_escalate                   = $req->canEscalate ?? false;
        $data->serial_no                      = $req->serialNo;
        $data->is_btc                         = $req->isBtc ?? false;
        $data->is_enabled                     = $req->isEnabled ?? false;
        $data->can_view_document              = $req->canViewDocument ?? true;
        $data->can_upload_document            = $req->canUploadDocument ?? false;
        $data->can_verify_document            = $req->canVerifyDocument ?? false;
        $data->allow_free_communication       = $req->allowFreeCommunication ?? true;
        $data->can_forward                    = $req->canForward ?? false;
        $data->can_backward                   = $req->canBackward ?? false;
        $data->is_pseudo                      = $req->isPseudo ?? false;
        $data->show_field_verification        = $req->showFieldVerification ?? false;
        $data->can_view_form                  = $req->canViewForm ?? false;
        $data->can_see_tc_verification        = $req->canSeeTcVerification ?? false;
        $data->can_edit                       = $req->canEdit ?? false;
        $data->can_send_sms                   = $req->canSendSms ?? false;
        $data->can_comment                    = $req->canComment ?? true;
        $data->is_custom_enabled              = $req->isCustomEnabled ?? false;
        $data->je_comparison                  = $req->jeComparison ?? false;
        $data->technical_comparison           = $req->technicalComparison ?? false;
        $data->can_view_technical_comparison  = $req->canViewTechnicalComparison ?? false;
        $data->associated_workflow_id         = $req->associatedWorkflowId;
        $data->created_by               = $createdBy;
        $data->stamp_date_time          = Carbon::now();
        $data->save();
    }

    /**
     * Update Role Map
     */
    public function updateRoleMap($req)
    {
        $data = WfWorkflowrolemap::find($req->id);
        $data->workflow_id                    = $req->workflowId                 ?? $data->workflow_id;
        $data->wf_role_id                     = $req->wfRoleId                   ?? $data->wf_role_id;
        $data->is_suspended                   = $req->isSuspended                ?? $data->is_suspended;
        $data->forward_role_id                = $req->forwardRoleId              ?? $data->forward_role_id;
        $data->backward_role_id               = $req->backwardRoleId             ?? $data->backward_role_id;
        $data->is_initiator                   = $req->isInitiator                ?? $data->is_initiator;
        $data->is_finisher                    = $req->isFinisher                 ?? $data->is_finisher;
        $data->allow_full_list                = $req->allowFullList              ?? $data->allow_full_list;
        $data->can_escalate                   = $req->canEscalate                ?? $data->can_escalate;
        $data->serial_no                      = $req->serialNo                   ?? $data->serial_no;
        $data->is_btc                         = $req->isBtc                      ?? $data->is_btc;
        $data->is_enabled                     = $req->isEnabled                  ?? $data->is_enabled;
        $data->can_view_document              = $req->canViewDocument            ?? $data->can_view_document;
        $data->can_upload_document            = $req->canUploadDocument          ?? $data->can_upload_document;
        $data->can_verify_document            = $req->canVerifyDocument          ?? $data->can_verify_document;
        $data->allow_free_communication       = $req->allowFreeCommunication     ?? $data->allow_free_communication;
        $data->can_forward                    = $req->canForward                 ?? $data->can_forward;
        $data->can_backward                   = $req->canBackward                ?? $data->can_backward;
        $data->is_pseudo                      = $req->isPseudo                   ?? $data->is_pseudo;
        $data->show_field_verification        = $req->showFieldVerification      ?? $data->show_field_verification;
        $data->can_view_form                  = $req->canViewForm                ?? $data->can_view_form;
        $data->can_see_tc_verification        = $req->canSeeTcVerification       ?? $data->can_see_tc_verification;
        $data->can_edit                       = $req->canEdit                    ?? $data->can_edit;
        $data->can_send_sms                   = $req->canSendSms                 ?? $data->can_send_sms;
        $data->can_comment                    = $req->canComment                 ?? $data->can_comment;
        $data->is_custom_enabled              = $req->isCustomEnabled            ?? $data->is_custom_enabled;
        $data->je_comparison                  = $req->jeComparison               ?? $data->je_comparison;
        $data->technical_comparison           = $req->technicalComparison        ?? $data->technical_comparison;
        $data->can_view_technical_comparison  = $req->canViewTechnicalComparison ?? $data->can_view_technical_comparison;
        $data->associated_workflow_id         = $req->associatedWorkflowId       ?? $data->associated_workflow_id;
        $data->save();
    }

    /**
     * All Role Map list
     */
    public function roleMaps()
    {
        $data = DB::table('wf_workflowrolemaps')
            ->select(
                'wf_workflowrolemaps.*',
                'r.role_name as forward_role_name',
                'rr.role_name as backward_role_name',
                'wf_roles.role_name',
                'wf_masters.workflow_name',
                'ulb_name'
            )
            ->join('wf_workflows', 'wf_workflows.id', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', 'wf_workflows.wf_master_id')
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->join('wf_roles', 'wf_roles.id', 'wf_workflowrolemaps.wf_role_id')
            ->join('ulb_masters', 'ulb_masters.id', 'wf_workflows.ulb_id')
            ->where('wf_workflowrolemaps.is_suspended', false)
            ->orderBy('workflow_id');
        // ->get();
        return $data;
    }

    /**
     * Delete Role Map
     */
    public function deleteRoleMap($req)
    {
        $data = WfWorkflowrolemap::find($req->id);
        $data->is_suspended = true;
        $data->save();
    }

    /**
     * | Get Workflow Forward and Backward Ids
     */
    public function getWfBackForwardIds($req)
    {
        return WfWorkflowrolemap::select('forward_role_id', 'backward_role_id')
            ->where('workflow_id', $req->workflowId)
            ->where('wf_role_id', $req->roleId)
            ->where('is_suspended', false)
            ->firstOrFail();
    }

    /**
     * | Get Ulb Workflows By Role Ids
     */
    public function getWfByRoleId($roleIds)
    {
        return WfWorkflowrolemap::select('workflow_id')
            ->whereIn('wf_role_id', $roleIds)
            ->get();
    }


    public function getRoleDetails($request)
    {
        $roleDetails = DB::table('wf_workflowrolemaps')
            ->select(
                'wf_workflowrolemaps.id',
                'wf_workflowrolemaps.workflow_id',
                'wf_workflowrolemaps.wf_role_id',
                'wf_workflowrolemaps.forward_role_id',
                'wf_workflowrolemaps.backward_role_id',
                'wf_workflowrolemaps.is_initiator',
                'wf_workflowrolemaps.is_finisher',
                'r.role_name as forward_role_name',
                'rr.role_name as backward_role_name'
            )
            ->leftJoin('wf_roles as r', 'wf_workflowrolemaps.forward_role_id', '=', 'r.id')
            ->leftJoin('wf_roles as rr', 'wf_workflowrolemaps.backward_role_id', '=', 'rr.id')
            ->where('workflow_id', $request->workflowId)
            ->where('wf_role_id', $request->wfRoleId)
            ->where('wf_workflowrolemaps.is_suspended', false)
            ->orderBy('role_id')
            ->first();

        return $roleDetails;
    }


    //Role by Workflow
    public function getRoleByWorkflow($request, $ulbId)
    {
        return WfWorkflowrolemap::select('wf_roles.id as role_id', 'wf_roles.role_name')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_workflows', 'wf_workflows.id', 'wf_workflowrolemaps.workflow_id')
            ->where('wf_workflows.ulb_id', $ulbId)
            ->where('workflow_id', $request->workflowId)
            ->where(function ($where) {
                $where->orWhereNotNull("wf_workflowrolemaps.forward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.backward_role_id")
                    ->orWhereNotNull("wf_workflowrolemaps.serial_no");
            })
            ->orderBy('serial_no')
            ->where('wf_workflowrolemaps.is_suspended', false)
            ->get();
    }

    public function getUserByWorkflow($request)
    {
        $users = WfWorkflowrolemap::where('workflow_id', $request->workflowId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();

        return $users;
    }


    public function getWardsInWorkflow($request)
    {
        $users = WfWorkflowrolemap::select('ulb_ward_masters.ward_name', 'ulb_ward_masters.id')
            ->where('workflow_id', $request->workflowId)
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_workflowrolemaps.wf_role_id')
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', '=', 'wf_roles.id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->orderBy('id')
            ->get();

        return $users;
    }


    public function getWorkflowByRole($request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->select('workflow_name')
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('wf_masters', 'wf_masters.id', '=', 'wf_workflows.wf_master_id')
            ->get();
        return $users;
    }

    public function getUlbByRole($request)
    {
        $users = WfWorkflowrolemap::where('wf_role_id', $request->roleId)
            ->join('wf_workflows', 'wf_workflows.id', '=', 'wf_workflowrolemaps.workflow_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_workflows.ulb_id')
            ->get('ulb_masters.*');
        return $users;
    }
}
