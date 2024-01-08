<?php

namespace App\Models\Workflows;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRole extends Model
{
    use HasFactory;
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    //add role
    public function addRole($req)
    {
        $createdBy = Auth()->user()->id;
        $role = new WfRole;
        $role->role_name = $req->roleName;
        $role->created_by = $createdBy;
        $role->stamp_date_time = Carbon::now();
        $role->save();
        return $role;
    }

    //update role
    public function updateRole($req)
    {
        $role = WfRole::find($req->id);
        $role->role_name = $req->roleName;
        $role->is_suspended = $req->isSuspended;
        $role->updated_at = Carbon::now();
        $role->save();
    }

    //role by id
    public function rolebyId($req)
    {
        return  WfRole::where('id', $req->id)
            ->where('is_suspended', false)
            ->get();
    }

    //role list
    public function roleList()
    {
        return  WfRole::select('wf_roles.id', 'role_name', 'wf_roles.is_suspended', 'users.name as created_by')
            ->join('users', 'users.id', 'wf_roles.created_by')
            ->where('is_suspended', false)
            ->orderBy('role_name');
    }

    //delete role

    public function deleteRole($req)
    {
        $data = WfRole::find($req->id);
        $data->is_suspended = true;
        $data->save();
    }

    public function getRoleByUserUlbId($request)
    {
        $users = WfRole::select('wf_roles.*')
            ->where('ulb_ward_masters.ulb_id', $request->ulbId)
            ->where('wf_roleusermaps.user_id', $request->userId)
            ->join('wf_roleusermaps', 'wf_roleusermaps.wf_role_id', 'wf_roles.id')
            ->join('wf_ward_users', 'wf_ward_users.user_id', 'wf_roleusermaps.user_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'wf_ward_users.ward_id')
            ->first();
        if ($users) {
            return $users;
        }
        return responseMsg(false, "No Data Available", "");
    }
}
