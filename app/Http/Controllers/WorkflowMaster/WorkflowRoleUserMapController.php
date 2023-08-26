<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use App\Models\Workflows\WfRole;
use App\Models\Workflows\WfRoleusermap;
use Carbon\Carbon;
use Dotenv\Validator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator as FacadesValidator;

class WorkflowRoleUserMapController extends Controller
{
    protected $eloquentRoleUserMap;

    // Initializing Construct function
    // public function __construct(iWorkflowRoleUserMapRepository $eloquentRoleUserMap)
    // {
    //     $this->EloquentRoleUserMap = $eloquentRoleUserMap;
    // }


    /**
     * | Create ROle User Mapping 
     */
    public function createRoleUser(Request $request)
    {
        $validated = FacadesValidator::make(
            $request->all(),
            [
                'userId'     => 'required|int',
                'wfRoleId'   => 'required|int',
                'permissionStatus' => 'required'
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $mWfRoleUserMap = new WfRoleusermap();
            $checkExisting = WfRoleusermap::where('wf_role_id', $request->wfRoleId)
                ->where('user_id', $request->userId)
                ->first();

            if ($request->permissionStatus == 1)
                $isSuspended = false;

            if ($request->permissionStatus == 0)
                $isSuspended = true;

            if ($checkExisting) {
                $request->merge([
                    'id' => $checkExisting->id,
                    'isSuspended' => $isSuspended
                ]);
                $mWfRoleUserMap->updateRoleUser($request);
            } else {
                $request->merge([
                    'isSuspended' => $isSuspended,
                    'createdBy' => Auth()->user()->id
                ]);
                $mWfRoleUserMap->addRoleUser($request);
            }

            // create

            return responseMsgs(true, "Successfully Saved", "", "120501", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120501", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Update data
     */
    public function updateRoleUser(Request $request)
    {
        $validated = FacadesValidator::make(
            $request->all(),
            ['id'     => 'required|int']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $device = WfRoleusermap::findorfail($request->id);
            $device->wf_role_id = $request->wfRoleId ?? $device->wf_role_id;
            $device->user_id = $request->userId ?? $device->user_id;
            $device->save();

            return responseMsgs(true, "Data Updated", "", "120502", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120502", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | List view by IDs
     */
    public function roleUserbyId(Request $request)
    {
        $validated = FacadesValidator::make(
            $request->all(),
            ['id'     => 'required|int']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $mWfRoleusermap = new WfRoleusermap();
            $data = $mWfRoleusermap->getRoleUser()
                ->where('wf_roleusermaps.id', $request->id)
                ->first();

            return responseMsgs(true, "Data Retrieved", $data, "120503", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120503", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Get All data
     */
    public function getAllRoleUser(Request $request)
    {
        try {
            $ulbId = authUser()->ulb_id;
            $mWfRoleusermap = new WfRoleusermap();
            $data = $mWfRoleusermap->getRoleUser()
                ->where('users.ulb_id', $ulbId)
                ->get();

            return responseMsgs(true, "Successfully Saved", $data, "120504", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120504", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Delete data
     */
    public function deleteRoleUser(Request $request)
    {
        $validated = FacadesValidator::make(
            $request->all(),
            ['id'     => 'required|int']
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $data = WfRoleusermap::findorfail($request->id);
            $data->is_suspended = true;
            $data->save();

            return responseMsgs(true, "Data Deleted", $data, "120505", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120505", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    // Enable or Disable User Roles
    public function updateUserRoles(Request $req)
    {
        $validated = FacadesValidator::make(
            $req->all(),
            [
                'roleId' => 'required|int',
                'is_suspended' => 'required|bool',
                'userId' => 'required|int'
            ]
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ], 422);
        }
        return $this->EloquentRoleUserMap->updateUserRoles($req);
    }


    /**
     * created by : Ashutosh Kumar
     * created at : 14-07-23
     */

    // Roles by User Id
    public function roleByUserId(Request $req)
    {
        $validator = FacadesValidator::make($req->all(),  [
            'userId' => 'required|integer'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $WfRoleUserMap = new WfRoleusermap;
            // $data = $WfRoleUserMap->getRoleByUserId()
            //     ->where('wf_roleusermaps.user_id', '=', $req->userId)
            //     ->get();

            $query = "select 
                        r.id,
                        r.role_name,
                        wr.user_id,
                        case 
                            when wr.user_id is null then false
                            else
                                true
                        end as permission_status
                
                    from wf_roles as r
                    left join (select * from wf_roleusermaps where user_id=$req->userId and is_suspended=false) as wr on wr.wf_role_id=r.id
                    order by r.id";

            $data = DB::select($query);

            return responseMsgs(true, 'Work Flow Role Map By User Id', $data, "", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }

    //Roles Except Given user id
    public function roleExcludingUserId(Request $req)
    {
        $validator = FacadesValidator::make($req->all(),  [
            'userId' => 'required|integer'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $mWfRoleUserMap = new WfRoleusermap;
            $mWfRole = new WfRole();

            $rolebyUserId = $mWfRoleUserMap->getRoleByUserId()
                ->where('wf_roleusermaps.user_id', $req->userId)
                ->get();
            $wfRoleId = $rolebyUserId->pluck('wf_role_id');

            $roleList = $mWfRole->roleList()
                ->whereNotIn('wf_roles.id',  $wfRoleId)
                ->get();

            return responseMsgs(true, 'Work Flow Role Map Except User Id', $roleList, "", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}
