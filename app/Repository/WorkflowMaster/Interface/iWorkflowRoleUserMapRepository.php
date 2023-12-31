<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Tannu Verma
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentWorkflowRoleUserMapRepository
 * @return ChildRepository App\Repository\WorkflowMaster\EloquentWorkflowRoleUserMapRepository
 */


interface iWorkflowRoleUserMapRepository
{
    public function updateUserRoles($req);              // Enable or Disable the User Roles
}
