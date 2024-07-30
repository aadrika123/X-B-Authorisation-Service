<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Http\Request;


class WorkflowRoleMapController extends Controller
{

    /**
     * Created On-13-06-2022 
     * Created By-Tannu Verma
     */

    use Workflow;

    public function createRoleMap(Request $req)
    {
        try {
            $req->validate([
                'workflowId'      => 'required',
                'wfRoleId'        => 'required',
                'forwardRoleId'   => 'nullable|integer',
                'backwardRoleId'  => 'nullable|integer',
                'isInitiator'     => 'nullable|in:true,false',
                'isFinisher'      => 'nullable|in:true,false',
                'allowFullList'   => 'nullable|in:true,false',
                'canEscalate'     => 'nullable|in:true,false',
                'serialNo'        => 'nullable',
                'isBtc'           => 'nullable|in:true,false',
                'isEnabled'       => 'nullable|in:true,false',
                'canViewDocument' => 'nullable|in:true,false',
                'canUploadDocument'          => 'nullable|in:true,false',
                'canVerifyDocument'          => 'nullable|in:true,false',
                'allowFreeCommunication'     => 'nullable|in:true,false',
                'canForward'                 => 'nullable|in:true,false',
                'canBackward'                => 'nullable|in:true,false',
                'isPseudo'                   => 'nullable|in:true,false',
                'showFieldVerification'      => 'nullable|in:true,false',
                'canViewForm'                => 'nullable|in:true,false',
                'canSeeTcVerification'       => 'nullable|in:true,false',
                'canEdit'                    => 'nullable|in:true,false',
                'canSendSms'                 => 'nullable|in:true,false',
                'canComment'                 => 'nullable|in:true,false',
                'isCustomEnabled'            => 'nullable|in:true,false',
                'jeComparison'               => 'nullable|in:true,false',
                'technicalComparison'        => 'nullable|in:true,false',
                'canViewTechnicalComparison' => 'nullable|in:true,false',
                'associatedWorkflowId'     => 'nullable',
            ]);
            $create = new WfWorkflowrolemap();
            $create->addRoleMap($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //update master
    public function updateRoleMap(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $update = new WfWorkflowrolemap();
            $list  = $update->updateRoleMap($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //master list by id
    public function roleMapbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);

            $listById = new WfWorkflowrolemap();
            $list  = $listById->roleMaps($req)
                ->where('wf_workflowrolemaps.id', $req->id)
                ->first();

            return responseMsg(true, "Role Map List", remove_null($list));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    /**
     * | All Role Maps
     */
    public function getAllRoleMap()
    {
        try {

            $list = new WfWorkflowrolemap();
            $masters = $list->roleMaps()->get();

            return responseMsg(true, "All Role Map List", $masters);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //delete master
    public function deleteRoleMap(Request $req)
    {
        try {
            $delete = new WfWorkflowrolemap();
            $delete->deleteRoleMap($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //Workflow Info
    public function workflowInfo(Request $req)
    {
        try {
            //workflow members
            $mWfWorkflowrolemap = new WfWorkflowrolemap();
            $ulbId = authUser()->ulb_id;
            $workflowId = $req->workflowId;

            $mreqs = new Request(["workflowId" => $workflowId]);
            $role = $mWfWorkflowrolemap->getRoleByWorkflow($mreqs, $ulbId);
            // Change role name if workflowId is 15 and role_id is 10
            $role = collect($role)->map(function ($item) use ($workflowId) {
                if ($workflowId == 15 && $item->role_id == 10) {
                    $item->role_name = "EXECUTIVE ENGINEER";
                }
                return $item;
            });

            $data['members'] = collect($role);

            //logged in user role
            $role = $this->getRole($mreqs);
            if ($role->isEmpty())
                throw new Exception("You are not authorised");
            $roleId  = collect($role)['wf_role_id'];

            //members permission
            $data['permissions'] = $this->permission($workflowId, $roleId);

            // pseudo users
            $data['pseudoUsers'] = $this->pseudoUser($ulbId);

            return responseMsgs(true, "Workflow Information", remove_null($data));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // tabs permission
    public function permission($workflowId, $roleId)
    {
        $permission = WfWorkflowrolemap::select('wf_workflowrolemaps.*')
            ->where('wf_workflowrolemaps.workflow_id', $workflowId)
            ->where('wf_workflowrolemaps.wf_role_id', $roleId)
            ->first();

        $data = [
            'allow_full_list' => $permission->allow_full_list,
            'can_escalate' => $permission->can_escalate,
            'can_btc' => $permission->is_btc,
            'is_enabled' => $permission->is_enabled,
            'can_view_document' => $permission->can_view_document,
            'can_upload_document' => $permission->can_upload_document,
            'can_verify_document' => $permission->can_verify_document,
            'allow_free_communication' => $permission->allow_free_communication,
            'can_forward' => $permission->can_forward,
            'can_backward' => $permission->can_backward,
            'can_approve' => $permission->is_finisher,
            'can_reject' => $permission->is_finisher,
            'is_pseudo' => $permission->is_pseudo,
            'show_field_verification' => $permission->show_field_verification,
            'can_view_form' => $permission->can_view_form,
            'can_see_tc_verification' => $permission->can_see_tc_verification,
            'can_edit' => $permission->can_edit,
            'can_send_sms' => $permission->can_send_sms,
            'can_comment' => $permission->can_comment,
            'is_custom_enabled' => $permission->is_custom_enabled,
            'je_comparison' => $permission->je_comparison,
            'technical_comparison' => $permission->technical_comparison,
            'can_view_technical_comparison' => $permission->can_view_technical_comparison,
            'can_bt_da' => $permission->can_bt_da,
            'can_assing_propety_no' => $permission->can_assing_propety_no ?? false,
            'can_genrate_jahirnama' => $permission->can_genrate_jahirnama ?? false,
            'can_update_jahirnama_obj' => $permission->can_update_jahirnama_obj ?? false,
        ];

        return $data;
    }

    public function pseudoUser($ulbId)
    {
        $pseudo = User::select(
            'id',
            'user_name'
        )
            ->where('user_type', 'Pseudo')
            ->where('ulb_id', $ulbId)
            ->where('suspended', false)
            ->get();
        return $pseudo;
    }
}
