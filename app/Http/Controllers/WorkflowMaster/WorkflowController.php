<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfWorkflow;
use Illuminate\Http\Request;
use Exception;

class WorkflowController extends Controller
{
    //create master
    public function createWorkflow(Request $req)
    {
        try {
            $req->validate([
                'wfMasterId' => 'required',
                'ulbId' => 'required',
                'altName' => 'required',
                'isDocRequired' => 'required',
            ]);

            $create = new WfWorkflow();
            $create->addWorkflow($req);

            return responseMsgs(true, "Workflow Saved", "", "120201", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120201", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //update master
    public function updateWorkflow(Request $req)
    {
        try {
            $req->validate([
                'wfMasterId' => 'required',
                'ulbId' => 'required',
            ]);
            $update = new WfWorkflow();
            $list  = $update->updateWorkflow($req);

            return responseMsgs(true, "Data Updated", $list, "120202", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120202", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //master list by id
    public function workflowbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new WfWorkflow();
            $list  = $listById->listbyId($req);

            return responseMsgs(true, "Workflow List", $list, "120203", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120203", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //all master list
    public function getAllWorkflow(Request $req)
    {
        try {
            $ulbId = authUser()->ulb_id;
            $list = new WfWorkflow();
            $workflow = $list->listUlbWorkflow($ulbId);

            return responseMsgs(true, "All Workflow List", $workflow, "120204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    //delete master
    public function deleteWorkflow(Request $req)
    {
        try {
            $delete = new WfWorkflow();
            $delete->deleteWorkflow($req);

            return responseMsgs(true, "Data Deleted", "", "120205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
