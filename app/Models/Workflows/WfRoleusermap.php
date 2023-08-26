<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfRoleusermap extends Model
{
    use HasFactory;

    /**
     * | Get Role details by User Id
     */
    public function getRoleDetailsByUserId($userId)
    {
        return WfRoleusermap::select(
            'wf_roles.role_name AS roles',
            'wf_roles.id AS roleId'
        )
            ->join('wf_roles', 'wf_roles.id', '=', 'wf_roleusermaps.wf_role_id')
            ->where('wf_roleusermaps.user_id', $userId)
            ->where('wf_roleusermaps.is_suspended', false)
            ->orderByDesc('wf_roles.id')
            ->get();
    }

    public function getUserByRoleId($request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('user_name', 'mobile', 'email', 'user_type')
            ->join('users', 'users.id', '=', 'wf_roleusermaps.user_id')
            ->get();
        return $users;
    }

    public function getWardByRole($request)
    {
        $users = WfRoleusermap::where('wf_role_id', $request->roleId)
            ->select('ulb_masters.*')
            ->join('wf_ward_users', 'wf_ward_users.user_id', '=', 'wf_roleusermaps.user_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'wf_ward_users.ward_id')
            ->get();
        return $users;
    }

    public function getRoleUser()
    {
        return WfRoleusermap::select(
            'wf_roleusermaps.id',
            'wf_roles.role_name',
            'users.name'
        )
            ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            ->join('users', 'users.id', 'wf_roleusermaps.user_id')
            ->where('wf_roleusermaps.is_suspended', false)
            ->orderByDesc('wf_roles.id');
    }


    /**
     * created by : Ashutosh Kumar
     * created at : 14-07-23
     */
    public function getRoleByUserId()
    {
        return WfRoleusermap::select('wf_roleusermaps.id', 'wf_roleusermaps.wf_role_id', 'wf_roles.role_name')
            ->join('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            ->where('wf_roleusermaps.is_suspended', false)
            ->orderBy('wf_roleusermaps.id');
    }

    /**
     * | get Role By User Id
     */
    public function getRoleIdByUserId($userId)
    {
        return WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
    }

    /**
     * | Create Role Map
     */
    public function addRoleUser($req)
    {
        $data = new WfRoleusermap;
        $data->wf_role_id   = $req->wfRoleId;
        $data->user_id      = $req->userId;
        $data->is_suspended = $req->isSuspended ?? false;
        $data->created_by   = $req->createdBy;
        $data->save();
    }

    /**
     * | Update Role Map
     */
    public function updateRoleUser($req)
    {
        $data = WfRoleusermap::find($req->id);
        $data->wf_role_id   = $req->wfRoleId    ?? $data->wf_role_id;
        $data->user_id      = $req->userId      ?? $data->user_id;
        $data->is_suspended = $req->isSuspended ?? $data->is_suspended;
        $data->save();
    }
}
