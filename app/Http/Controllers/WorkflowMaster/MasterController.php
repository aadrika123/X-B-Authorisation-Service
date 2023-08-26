<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfMaster;
use Illuminate\Http\Request;
use Exception;

class MasterController extends Controller
{
    /**
     * Controller for Add, Update, View , Delete of Workflow Master Table
     * -------------------------------------------------------------------------------------------------
     * Created On-07-07-2023
     * Created By-Mrinal Kumar
     * Status : Closed
     * -------------------------------------------------------------------------------------------------
     */


    //create master
    public function createMaster(Request $req)
    {
        try {
            $req->validate([
                'workflowName' => 'required',
                'moduleId'     => 'required|integer'
            ]);

            $create = new WfMaster();
            $create->addMaster($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //update master
    public function updateMaster(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required',
                'workflowName' => 'required',
            ]);
            $update = new WfMaster();
            $list  = $update->updateMaster($req);

            return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //master list by id
    public function masterbyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new WfMaster();
            $list  = $listById->listbyId($req);

            return responseMsgs(true, "Master List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    //all master list
    public function getAllMaster(Request $req)
    {
        try {
            $list = new WfMaster();
            $masters = $list->listMaster();

            return responseMsgs(true, "All Master List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    //delete master
    public function deleteMaster(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $delete = new WfMaster();
            $delete->deleteMaster($req);

            return responseMsgs(true, "Data Deleted", '', "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
