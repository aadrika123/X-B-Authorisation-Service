<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\Workflow\Workflow;

/**
 * | Created On-02-06-2023
 * | Created By-Mrinal Kumar
 */

class WcController extends Controller
{
    use Workflow;
    /*
    * Get Workflow Current User
    */
    public function workflowCurrentUser(Request $req)
    {
        $currentUser = $this->getWorkflowCurrentUser($req->workflowId);

        if (isset($currentUser)) {
            return responseMsg(true, 'Current User', $currentUser);
        }
    }

    /*
    * Get Workflow Initiator Data
    */
    public function workflowInitiatorData(Request $req)
    {
        $userId = authUser()->id;
        $initiatorData = $this->getWorkflowInitiatorData($userId, $req->workflowId);

        return responseMsg(true, 'Initiator Data', $initiatorData);
    }

    /*
    * Get Role id by User Id
    */
    public function roleIdByUserId(Request $req)
    {
        $userId = auth()->User();
        $roleId = $this->getRoleIdByUserId($userId['id']);

        return responseMsg(true, 'Workflow Role Id', $roleId);
    }

    /*
    * Get Wards by User Id
    */
    public function wardByUserId(Request $req)
    {
        $userId = auth()->User();
        $wardId = $this->getWardByUserId($userId['id']);

        return responseMsg(true, 'Workflow Wards', $wardId);
    }

    /*
    * Get Initiator
    */
    public function initiatorId(Request $req)
    {
        $initiatorId = $this->getInitiatorId($req->workflowId);

        return responseMsg(true, 'Initiator', $initiatorId);
    }

    /*
    * Get Initiator
    */
    public function finisherId(Request $req)
    {
        $finisherId = $this->getFinisherId($req->workflowId);

        return responseMsg(true, 'Finisher', $finisherId);
    }
}
