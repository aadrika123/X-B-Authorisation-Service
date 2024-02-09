<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Models\Workflows\WfWardUser;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WardUserController extends Controller
{
    //create WardUser
    public function createWardUser(Request $req)
    {

        $validated = Validator::make(
            $req->all(),
            [
                'userId' => 'required',
                'wardList' => 'required|array',
                // 'permissionStatus'=>'required
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $userId           = $req->userId;
            $wardList         = $req->wardList;

            collect($wardList)->map(function ($item) use ($userId) {

                $mWfWardUser = new WfWardUser();
                $checkExisting = $mWfWardUser::where('user_id', $userId)
                    ->where('ward_id', $item['wardId'])
                    ->first();

                if ($item['permissionStatus'] == 0)
                    $isSuspended = true;

                if ($item['permissionStatus'] == 1)
                    $isSuspended = false;

                if ($checkExisting) {

                    $req = new Request([
                        'id' => $checkExisting->id,
                        'userId' => $userId,
                        'wardId' => $item['wardId'],
                        'isSuspended' => $isSuspended,
                    ]);

                    $mWfWardUser->updateWardUser($req);
                } else {
                    $req = new Request([
                        'userId' => $userId,
                        'wardId' => $item['wardId'],
                        'isSuspended' => $isSuspended,
                    ]);
                    $mWfWardUser->addWardUser($req);
                }
            });

            // $checkExisting = WfWardUser::where('user_id', $req->userId)
            //     ->where('ward_id', $req->wardId)
            //     ->first();

            // if ($checkExisting)
            //     throw new Exception("User Exist");

            // $mWfWardUser = new WfWardUser();
            // $mWfWardUser->addWardUser($req);

            return responseMsg(true, "Successfully Saved", "");
        } catch (Exception $e) {
            return responseMsg(true,  $e->getMessage(), "");
        }
    }

    //update WardUser
    public function updateWardUser(Request $req)
    {
        try {
            $update = new WfWardUser();
            $list  = $update->updateWardUser($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //WardUser list by id
    public function WardUserbyId(Request $req)
    {
        try {

            $mWfWardUser = new WfWardUser();
            $list  = $mWfWardUser->listbyId($req);

            return responseMsg(true, "WardUser List", $list);
        } catch (Exception $e) {
            return response()->json(false, $e->getMessage());
        }
    }

    //all WardUser list
    public function getAllWardUser(Request $req)
    {
        try {
            $perPage =  $req->perPage ?? 10;
            $mWfWardUser = new WfWardUser();
            $WardUsers = $mWfWardUser->listWardUser()->paginate(10);

            return responseMsg(true, "All WardUser List", $WardUsers);
        } catch (Exception $e) {
            return responseMsg(true,  $e->getMessage(), '');
        }
    }

    //delete WardUser
    public function deleteWardUser(Request $req)
    {
        try {
            $delete = new WfWardUser();
            $delete->deleteWardUser($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }


    // TC List
    public function tcList(Request $req)
    {
        $req->validate([
            'wardId' => 'nullable',
        ]);
        try {
            $ulbId =  authUser()->ulb_id;
            $TC = ['TC', 'TL'];

            $data = User::select(
                'users.id',
                'name as user_name',
                'user_type',
            )
                ->where('id', '<>', 76)
                ->where('ulb_id', $ulbId)
                ->where('users.suspended', false)
                ->whereIN('user_type', $TC)
                ->orderBy('name')
                ->get();

            if ($req->wardId) {
                $data = User::select(
                    'users.id',
                    'name as user_name',
                    'user_type',
                )
                    ->join('wf_ward_users', 'wf_ward_users.user_id', 'users.id')
                    ->where('id', '<>', 76)
                    ->where('users.suspended', false)
                    ->where('ulb_id', $ulbId)
                    ->where('ward_id', $req->wardId)
                    ->whereIN('user_type', $TC)
                    ->orderBy('name')
                    ->get();
            }

            return responseMsgs(true, "TC List", remove_null($data), "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | 
     */
    public function wardByUserId(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            ['userId'     => 'required|int',]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $mWfWardUser = new WfWardUser();
            $user = authUser();

            $query = "select 
                            ward.id,
                            ward.ward_name,
                            ward.old_ward_name,
                            wu.user_id,
                            zone_name,
                            case 
                                when wu.user_id is null then false
                                else
                                    true  
                            end as permission_status
                    
                        from ulb_ward_masters as ward
                        left join (select * from wf_ward_users where user_id=$req->userId and is_suspended = false) as wu on wu.ward_id=ward.id
                        join zone_masters on zone_masters.id = ward.zone
                        where ward.ulb_id = $user->ulb_id
                        and ward.status=1
                        order by ward.ward_name";

            $data = DB::select($query);
            $data = collect($data)->groupBy('zone_name');

            // $WardUsers = $mWfWardUser->listWardUser()
            //     ->where('users.id', $req->userId)
            //     ->get();

            return responseMsg(true, "Ward List of User", $data);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    public function getTcTlJSKList(Request $req)
    {
        try {
            $query = "SELECT DISTINCT users.id, users.name AS user_name, users.user_type, wf_roles.role_name
            FROM users
            JOIN wf_roleusermaps ON wf_roleusermaps.user_id = users.id
            JOIN wf_roles ON wf_roles.id = wf_roleusermaps.wf_role_id
            WHERE wf_role_id IN (8, 7, 5, 4) AND users.suspended != true AND wf_roleusermaps.is_suspended != true
            ORDER BY wf_roles.role_name DESC, user_name ASC
                ";

            $data = DB::select($query);
            return responseMsg(true, "Ward List of User", $data);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }
}
