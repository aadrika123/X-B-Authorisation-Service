<?php

namespace App\Models\Workflows;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfWardUser extends Model
{
    use HasFactory;

    /**
     * | Get Wards by user id
     * | @var userId
     */
    public function getWardsByUserId($userId)
    {
        return WfWardUser::select('id', 'ward_id')
            ->where('user_id', $userId)
            ->orderBy('ward_id')
            ->get();
    }


    //create warduser
    public function addWardUser($req)
    {
        $createdBy = Auth()->user()->id;
        $mWfWardUser = new WfWardUser;
        $mWfWardUser->user_id = $req->userId;
        $mWfWardUser->ward_id = $req->wardId;
        $mWfWardUser->created_by = $createdBy;
        $mWfWardUser->save();
    }

    //update ward user
    public function updateWardUser($req)
    {
        $mWfWardUser = WfWardUser::find($req->id);
        $mWfWardUser->user_id      = $req->userId      ?? $mWfWardUser->user_id;
        $mWfWardUser->ward_id      = $req->wardId      ?? $mWfWardUser->ward_id;
        $mWfWardUser->is_admin     = $req->isAdmin     ?? $mWfWardUser->is_admin;
        $mWfWardUser->is_suspended = $req->isSuspended ?? $mWfWardUser->is_suspended;
        $mWfWardUser->save();
    }


    //list ward user by id
    public function listbyId($req)
    {
        $data = WfWardUser::select(
            'wf_ward_users.id',
            'user_id',
            'ward_id',
            'is_admin',
            'name',
            'ward_name',
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->join('users', 'users.id', 'wf_ward_users.user_id')
            ->where('wf_ward_users.id', $req->id)
            ->where('is_suspended', 'false')
            ->get();
        return $data;
    }

    //list ward user
    public function listWardUser()
    {
        $ulbId = authUser()->ulb_id;
        $data = WfWardUser::select(
            'wf_ward_users.id',
            'user_id',
            'ward_id',
            'is_admin',
            'name',
            'ward_name'
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'wf_ward_users.ward_id')
            ->join('users', 'users.id', 'wf_ward_users.user_id')
            ->where('ulb_ward_masters.ulb_id', $ulbId)
            ->where('is_suspended', 'false')
            ->orderByDesc('wf_ward_users.id');
        return $data;
    }

    //delete ward user
    public function deleteWardUser($req)
    {
        $data = WfWardUser::find($req->id);
        $data->is_suspended = "true";
        $data->save();
    }
}
