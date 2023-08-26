<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-14-11-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in WorkflowMappingepository
 * @return ChildRepository App\Repository\WorkflowMaster\WorkflowMapRepository
 */


interface iWorkflowMapRepository
{
    //public function getRoleByWorkflow(Request $request);
    //for mapping
    //public function getRoleDetails(Request $req);
    public function getUserById(Request $request);
    public function getWorkflowNameByUlb(Request $request);
    public function getRoleByUlb(Request $request);
    public function getWardByUlb(Request $request);
    public function getUserByRole(Request $request);
    
}