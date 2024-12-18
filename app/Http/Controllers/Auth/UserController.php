<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdPartyController;
use App\Http\Requests\Auth\AuthorizeRequestUser;
use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Http\Requests\Auth\OtpChangePass;
use App\Models\Auth\User;
use App\Models\Constant;
use App\Models\Notification\MirrorUserNotification;
use App\Models\Notification\UserNotification;
use App\Models\Workflows\WfRoleusermap;
use App\Pipelines\User\SearchByEmail;
use App\Pipelines\User\SearchByMobile;
use App\Pipelines\User\SearchByName;
use App\Pipelines\User\SearchByRole;
use App\Traits\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pipeline\Pipeline;
use App\Models\MobiMenu\MenuMobileMaster;
use App\Models\MobiMenu\UserMenuMobileExclude;
use App\Models\MobiMenu\UserMenuMobileInclude;
use App\Models\ModuleMaster;
use App\Models\OtpRequest;
use App\Models\TblSmsLog;
use App\Models\UlbWardMaster;
use App\Models\Workflows\WfWardUser;
use Illuminate\Support\Facades\Redis;
use PDOException;

use function PHPUnit\Framework\throwException;

class UserController extends Controller
{
    use Auth;
    private $_mUser;
    private $_MenuMobileMaster;
    private $_UserMenuMobileExclude;
    private $_UserMenuMobileInclude;
    private $_ModuleMaster;
    private $_FrontConstains;
    public function __construct()
    {
        $this->_mUser = new User();
        $this->_MenuMobileMaster = new MenuMobileMaster();
        $this->_UserMenuMobileExclude   = new UserMenuMobileExclude();
        $this->_UserMenuMobileInclude   = new UserMenuMobileInclude();
        $this->_ModuleMaster = new ModuleMaster();
        $this->_FrontConstains = new Constant();
    }

    /**
     * | User Login
     */
    public function loginAuth(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'email' => 'required|email',
                'password' => 'required',
                'type' => "nullable|in:mobile"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $currentTime = Carbon::now();
            $mWfRoleusermap = new WfRoleusermap();
            if ($req->module == 'dashboard') {
                if ($req->email <> 'stateadmin@gmail.com')
                    throw new Exception("You are not Authorised");
            }

            if ($req->module == 'userControl') {
                if ($req->email <> 'praveenkumar@gmail.com')
                    throw new Exception("You are not Authorised");
            }

            $user = $this->_mUser->getUserByEmail($req->email);
            if (!$user)
                throw new Exception("Oops! Given email does not exist");
            if ($user->suspended == true)
                throw new Exception("You are not authorized to log in!");
            if (Hash::check($req->password, $user->password)) {
                $users = $this->_mUser->find($user->id);
                $maAllow = $users->max_login_allow;
                $remain = ($users->tokens->count("id")) - $maAllow;
                $c = 0;
                // foreach($users->tokens->sortBy("id")->values() as  $key =>$token){                  
                //     if($remain<$key)
                //     {
                //         break;
                //     }
                //     $c+=1;
                //     $token->expires_at = Carbon::now();
                //     $token->update();
                //     $token->delete();
                // }

                $tockenDtl = $user->createToken('my-app-token');
                $ipAddress = getClientIpAddress(); #$req->userAgent()
                $bousuerInfo = [
                    "latitude" => $req->browserInfo["latitude"] ?? "",
                    "longitude" => $req->browserInfo["longitude"] ?? "",
                    "machine" => $req->browserInfo["machine"] ?? "",
                    "browser_name" => $req->browserInfo["browserName"] ?? $req->userAgent(),
                    "ip" => $ipAddress ?? "",
                ];
                DB::table('personal_access_tokens')
                    ->where('id', $tockenDtl->accessToken->id)
                    ->update($bousuerInfo);

                $token = $tockenDtl->plainTextToken;
                $menuRoleDetails = $mWfRoleusermap->getRoleDetailsByUserId($user->id);
                // if (empty(collect($menuRoleDetails)->first())) {
                //     throw new Exception('User has No Roles!');
                // }
                $role = collect($menuRoleDetails)->map(function ($value, $key) {
                    $values = $value['roles'];
                    return $values;
                });
                $GEO_MAX_AGE = (object)collect($this->_FrontConstains->getConnectionByName("GEO_MAX_AGE")->values())->first();
                $IS_GEO_ENABLE = (object)collect($this->_FrontConstains->getConnectionByName("IS_GEO_ENABLE")->values())->first();
                $data['isGeoEnable'] = $IS_GEO_ENABLE ? $IS_GEO_ENABLE->convert_values : false;
                $data['geoMaxAge'] = $GEO_MAX_AGE ? $GEO_MAX_AGE->convert_values : false;
                $data['token'] = $token;
                $data['userDetails'] = $user;
                $data['userDetails']['role'] = $role;

                $key = 'last_activity_' . $user->id;
                Redis::set($key, $currentTime);            // Caching

                return responseMsgs(true, "You have Logged In Successfully", $data, 010101, "1.0", responseTime(), "POST", $req->deviceId);
            }

            throw new Exception("Password Not Matched");
        } catch (PDOException $e) {
            return responseMsg(false, "Oops! It's rush hour and traffic is piling up on this page. Please try again in a short while.", "");
        } catch (Exception  $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | UserLogin Send Otp
     */
    public function userloginSendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => "required|email",
                'type'  => "nullable|in:User",
            ]);
            $mOtpRequest = new OtpRequest();
            $mTblSmsLog  = new TblSmsLog();
            $thirdPartController = new ThirdPartyController;
            $email       =  $request->email;

            $user = $this->_mUser->getUserByEmail($email);
            if (!$user)
                throw new Exception("Oops! the given email does not exist");
            if ($user->suspended == true)
                throw new Exception("You are not authorized to log in!");
            if (strlen($user->mobile) != 10)
                throw new Exception("Mobile no. is invalid.");

            switch ($request->type) {
                case ('Forgot'):
                    $otpType = 'Forgot Password';
                    break;

                case ('Update Mobile'):
                    $otpType = 'Update Mobile';
                    break;

                default:
                    $otpType = 'User Login';
                    break;
            }

            $generateOtp = $thirdPartController->generateOtp();
            $sms         = "OTP for " . $otpType . " at Akola Municipal Corporation's portal is " . $generateOtp . ". This OTP is valid for 10 minutes.";

            $response = send_sms($user->mobile, $sms, 1707170367857263583);
            $request->merge([   
                "type"     => $otpType,
                "mobileNo" => $user->mobile,
            ]);
            $mOtpRequest->saveOtp($request, $generateOtp);

            $smsReqs = [
                "emp_id" => $user->id ?? 0,
                "ref_id" => $user->id ?? 0,
                "ref_type" => 'User',
                "mobile_no" => $user->mobile,
                "purpose" => "OTP for " . $otpType,
                "template_id" => 1707170367857263583,
                "message" => $sms,
                "response" => $response['status'],
                "smgid" => $response['msg'],
                "stampdate" => Carbon::now(),
            ];
            $mTblSmsLog->create($smsReqs);

            $last_three_digits = "xxxxxxx". substr($user->mobile, -3);
            // $last_three_digits = "xxxxxxx". $user->mobile % 10000;

            return responseMsgs(true, "OTP send to your mobile no.", $last_three_digits, "", "01", responseTime(), $request->getMethod(), "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0101", "01", responseTime(), $request->getMethod(), "");
        }
    }

    /**
     * | User Login Verify Otp
     */
    public function userloginVerifyOtp(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'email'    => 'required|email',
                'type'     => "nullable|in:mobile",
                'otp'      => "required|digits:6",
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {

            # model
            $mOtpMaster     = new OtpRequest();
            $currentTime    = Carbon::now();
            $mWfRoleusermap = new WfRoleusermap();

            DB::beginTransaction();
            
            $checkOtp = $mOtpMaster->checkOtpViaEmail($req);
            if (!$checkOtp)
                throw new Exception("OTP not match!");

            $otpLog = $checkOtp->replicate();
            $otpLog->setTable('log_otp_requests');
            $otpLog->id = $checkOtp->id;
            $otpLog->save();

            $checkOtp->delete();
            
            DB::commit();

            $user = $this->_mUser->getUserByEmail($req->email);
            if (!$user)
                throw new Exception("Oops! Given email does not exist");
            if ($user->suspended == true)
                throw new Exception("You are not authorized to log in!");

            if ($req->module == 'dashboard') {
                if ($req->email <> 'stateadmin@gmail.com')
                    throw new Exception("You are not Authorised");
            }

            if ($req->module == 'userControl') {
                if ($req->email <> 'praveenkumar@gmail.com')
                    throw new Exception("You are not Authorised");
            }

            $users = $this->_mUser->find($user->id);
            $maAllow = $users->max_login_allow;
            $remain = ($users->tokens->count("id")) - $maAllow;
            $c = 0;
            // foreach($users->tokens->sortBy("id")->values() as  $key =>$token){                  
            //     if($remain<$key)
            //     {
            //         break;
            //     }
            //     $c+=1;
            //     $token->expires_at = Carbon::now();
            //     $token->update();
            //     $token->delete();
            // }

            $tockenDtl = $user->createToken('my-app-token');
            $ipAddress = getClientIpAddress(); #$req->userAgent()
            $bousuerInfo = [
                "latitude" => $req->browserInfo["latitude"] ?? "",
                "longitude" => $req->browserInfo["longitude"] ?? "",
                "machine" => $req->browserInfo["machine"] ?? "",
                "browser_name" => $req->browserInfo["browserName"] ?? $req->userAgent(),
                "ip" => $ipAddress ?? "",
            ];
            DB::table('personal_access_tokens')
                ->where('id', $tockenDtl->accessToken->id)
                ->update($bousuerInfo);

            $token = $tockenDtl->plainTextToken;
            $menuRoleDetails = $mWfRoleusermap->getRoleDetailsByUserId($user->id);
            // if (empty(collect($menuRoleDetails)->first())) {
            //     throw new Exception('User has No Roles!');
            // }
            $role = collect($menuRoleDetails)->map(function ($value, $key) {
                $values = $value['roles'];
                return $values;
            });
            $GEO_MAX_AGE = (object)collect($this->_FrontConstains->getConnectionByName("GEO_MAX_AGE")->values())->first();
            $IS_GEO_ENABLE = (object)collect($this->_FrontConstains->getConnectionByName("IS_GEO_ENABLE")->values())->first();
            $data['isGeoEnable'] = $IS_GEO_ENABLE ? $IS_GEO_ENABLE->convert_values : false;
            $data['geoMaxAge'] = $GEO_MAX_AGE ? $GEO_MAX_AGE->convert_values : false;
            $data['token'] = $token;
            $data['userDetails'] = $user;
            $data['userDetails']['role'] = $role;

            $key = 'last_activity_' . $user->id;
            Redis::set($key, $currentTime);            // Caching

            return responseMsgs(true, "You have Logged In Successfully", $data, 010101, "1.0", responseTime(), "POST", $req->deviceId);

            throw new Exception("Password Not Matched");
        } catch (PDOException $e) {
            return responseMsg(false, "Oops! It's rush hour and traffic is piling up on this page. Please try again in a short while.", "");
        } catch (Exception  $e) {
            DB::rollBack();
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | logout
     */
    public function logout(Request $req)
    {
        try {
            $req->user()->currentAccessToken()->delete();                               // Delete the Current Accessable Token
            return responseMsgs(true, "You have Logged Out", [], "", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | User Creation
     */
    public function createUser(AuthUserRequest $request)
    {
        try {
            // Validation---@source-App\Http\Requests\AuthUserRequest
            $user = new User;
            $checkEmail = User::where('email', $request->email)->first();
            if ($checkEmail)
                throw new Exception('The email has already been taken.');
            $this->saving($user, $request);                     #_Storing data using Auth trait
            $firstname = explode(" ", $request->name);
            $user->user_name = $firstname[0] . '.' . substr($request->mobile, 0, 3);
            $user->password = Hash::make($firstname[0] . '@' . substr($request->mobile, 7, 3));
            
            $user->save();

            $data['userName'] = $user->user_name;
            return responseMsgs(true, "User Registered Successfully !! Please Continue to Login.
            Your Password is Your first name @ Your last 3 digit of your Mobile No", $data);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | Update User Details
     */
    public function updateUser(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => 'required'
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $id = $request->id;
            $user = User::find($id);
            if (!$user)
                throw new Exception("User Not Exist");
            $stmt = $user->email == $request->email;
            if ($stmt) {
                $this->saving($user, $request);
                $this->savingExtras($user, $request);
                $user->save();
            }
            if (!$stmt) {
                $check = User::where('email', $request->email)->first();
                if ($check) {
                    throw new Exception('Email Is Already Existing');
                }
                if (!$check) {
                    $this->saving($user, $request);
                    $this->savingExtras($user, $request);
                    $user->save();
                }
            }
            return responseMsgs(true, "Successfully Updated", "", "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | List User
     */
    public function listUser(Request $req)
    {
        try {
            $docUrl = Config::get('constants.DOC_URL');
            $perPage = $req->perPage ?? 10;
            $ulbId = authUser()->ulb_id;
            $data = User::select(
                '*',
                'users.id as id',
                DB::raw("CONCAT('$docUrl','/',photo_relative_path, '/', photo) AS photo"),
                DB::raw("CONCAT('$docUrl','/',sign_relative_path, '/', signature) AS signature")
            )
                ->where('users.ulb_id', $ulbId)
                ->orderBy('users.name');

            $userList = app(Pipeline::class)
                ->send(
                    $data
                )
                ->through([
                    SearchByName::class,
                    SearchByEmail::class,
                    SearchByMobile::class,
                    SearchByRole::class
                ])
                ->thenReturn()
                ->paginate($perPage);
            // ->get();
            // ->paginate(500);

            return responseMsgs(true, "User List", $userList, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Multiple List User
     */
    public function multipleUserList(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                "ids" => 'required|array'
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $perPage = $req->perPage ?? 10;
            $ulbId = authUser()->ulb_id;
            $data = User::select(
                '*',
                DB::raw("CONCAT(photo_relative_path, '/', photo) AS photo"),
                DB::raw("CONCAT(sign_relative_path, '/', signature) AS signature")
            )
                ->where('ulb_id', $ulbId)
                ->whereIn('id', $req->ids)
                // ->orderByDesc('id')
                ->get();

            return responseMsgs(true, "User List", $data, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | List User
     */
    public function userById(Request $req)
    {
        try {
            $req->validate(
                ["id" => 'required']
            );
            $data = User::find($req->id);

            return responseMsgs(true, "User Data", $data, "", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * | Delete User
     */
    public function deleteUser(Request $request)
    {
        try {
            $request->validate(
                [
                    'id' => 'required',
                    'isSuspended' => 'required|boolean'
                ]
            );

            $data = User::find($request->id);
            $data->suspended = $request->isSuspended;
            $data->save();
            #expire all token            
            $count = (collect($data->tokens)->count("id"));
            if ($data->suspended && $data && $count < 0) {
                $data->tokens->each(function ($token, $key) {
                    $token->expires_at = Carbon::now();
                    $token->update();
                    $token->delete();
                });
            }
            if ($data->suspended && $data && $count >= 0) {
                $nameSpace = (((new \ReflectionClass(new User())))->getNamespaceName() . "\User");
                $sql = "delete from personal_access_tokens
                        where tokenable_id = " . $data->id . " AND tokenable_type = '$nameSpace'
                ";
                DB::select($sql);
            }

            if ($request->isSuspended == true)
                $msg = "You have Deactivated the User";

            if ($request->isSuspended == false)
                $msg = "You have Activated the User";


            return responseMsgs(true, $msg, '', "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }


    /**
     * |
     */
    // Changing Password
    public function changePass(ChangePassRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $user = User::find($id);
            $validPassword = Hash::check($request->password, $user->password);
            if ($validPassword) {

                $user->password = Hash::make($request->newPassword);
                $user->save();

                return responseMsgs(true, 'Successfully Changed the Password', "", "", "02", ".ms", "POST", $request->deviceId);
            }
            throw new Exception("Old Password dosen't Match!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "02", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Change Password by OTP 
     * | Api Used after the OTP Validation
     */
    public function changePasswordByOtp(OtpChangePass $request)
    {
        try {
            $id = auth()->user()->id;
            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->save();

            return responseMsgs(true, 'Successfully Changed the Password', "", "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | For Showing Logged In User Details 
     * | #user_id= Get the id of current user 
     * | if $redis available then get the value from redis key
     * | if $redis not available then get the value from sql database
     */
    public function myProfileDetails()
    {
        try {
            $userId = auth()->user()->id;
            $mUser = new User();
            $details = $mUser->getUserById($userId);
            $usersDetails = [
                'id'        => $details->id,
                'NAME'      => $details->name,
                'USER_NAME' => $details->user_name,
                'mobile'    => $details->mobile,
                'email'     => $details->email,
                'ulb_id'    => $details->ulb_id,
                'ulb_name'  => $details->ulb_name,
            ];

            return responseMsgs(true, "Data Fetched", $usersDetails, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }



    /**
     * | Get Users Details by Id
     */
    public function getUser(Request $request, $id)
    {
        try {
            $mUser = new User();
            $data = $mUser->getUserRoleDtls()
                ->where('users.id', $id)
                ->first();
            if (!$data)
                throw new Exception('No Role For the User');

            return responseMsgs(true, "User Details", $data, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Get All User Details
     */
    public function getAllUsers(Request $request)
    {
        try {
            $mUser = new User();
            $ulbId = authUser()->ulb_id;
            $data = $mUser->getUserRoleDtls()
                ->where('users.ulb_id', $ulbId)
                ->orderbyDesc('users.id')
                ->get();
            if ($data->isEmpty())
                throw new Exception('No User Found');

            return responseMsgs(true, "User Details", $data, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * |Employee Lis
     */
    public function employeeList()
    {
        try {
            $ulbId = authUser()->ulb_id;
            $data = User::select('name as user_name', 'id')
                ->whereIn('user_type', ['Employee', 'JSK'])
                ->where('ulb_id', $ulbId)
                ->where('suspended', false)
                ->orderBy('id')
                ->get();

            return responseMsgs(true, "List Employee", $data, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Active Employee List
     */
    public function activeEmployeeList()
    {
        try {
            // DB::enableQueryLog();
            $ulbId = authUser()->ulb_id;
            $data = User::select('name as user_name', 'id')
                ->where(function ($where) {
                    $where->where('user_type', '=', 'TC')
                        ->orWhere('user_type', '=', 'NSK');
                })
                ->where('suspended', false)
                ->where('ulb_id', $ulbId)
                ->orderBy('name')
                ->get();
            // dd(DB::getQueryLog());
            return responseMsgs(true, "List Employee", $data, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Get user Notification
     */
    public function userNotification()
    {
        $user = authUser();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        $userType = $user->user_type;
        $mMirrorUserNotification = new MirrorUserNotification();
        if ($userType == 'Citizen') {
            $data = $mMirrorUserNotification->notificationByUserId()
                ->where('citizen_id', $userId)
                ->get();
            $notification = collect($data)->groupBy('category');
        } else
            $notification =  $mMirrorUserNotification->notificationByUserId($userId)
                ->where('user_id', $userId)
                ->where('ulb_id', $ulbId)
                ->get();

        if (collect($notification)->isEmpty())
            return responseMsgs(true, "No Current Notification", '', "010108", "1.0", "", "POST", "");

        return responseMsgs(true, "Your Notificationn", remove_null($notification), "010108", "1.0", "", "POST", "");
    }

    /**
     * | Add Notification
     */
    public function addNotification($req)
    {
        $user = authUser();
        $userId = $user->id;
        $ulbId = $user->ulb_id;
        $muserNotification = new UserNotification();

        $mreq = new Request([
            "user_id" => $req->userId,
            "citizen_id" => $req->citizenId,
            "notification" => $req->notification,
            "send_by" => $req->sender,
            "category" => $req->category,
            "sender_id" => $userId,
            "ulb_id" => $ulbId,
            "module_id" => $req->moduleId,
            "event_id" => $req->eventId,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->requireAcknowledgment,
            "expected_delivery_time" => null,
            "created_at" => Carbon::now(),
        ]);
        $id = $muserNotification->addNotification($mreq);

        if ($req->citizenId) {
            $data = $muserNotification->notificationByUserId($userId)
                ->where('citizen_id', $req->citizenId)
                ->get();
        } else
            $data = $muserNotification->notificationByUserId($userId)
                ->where('user_id', $req->userId)
                ->take(10);

        $this->addMirrorNotification($mreq, $id, $user);

        return responseMsgs(true, "Notificationn Addedd", '', "010108", "1.0", "", "POST", "");
    }

    /**
     * | Add Mirror Notification
     */
    public function addMirrorNotification($req, $id, $user)
    {
        $mMirrorUserNotification = new MirrorUserNotification();
        $mreq = new Request([
            "user_id" => $req->user_id,
            "citizen_id" => $req->citizen_id,
            "notification" => $req->notification,
            "send_by" => $req->send_by,
            "category" => $req->category,
            "sender_id" => $user->id,
            "ulb_id" => $user->ulb_id,
            "module_id" => $req->module_id,
            "event_id" => $req->event_id,
            "generation_time" => Carbon::now(),
            "ephameral" => $req->ephameral,
            "require_acknowledgment" => $req->require_acknowledgment,
            "expected_delivery_time" => $req->expected_delivery_time,
            "created_at" => Carbon::now(),
            "notification_id" => $id,
        ]);
        $mMirrorUserNotification->addNotification($mreq);
    }

    /**
     * | Get user Notification
     */
    public function deactivateNotification($req)
    {
        $muserNotification = new UserNotification();
        $muserNotification->deactivateNotification($req);

        return responseMsgs(true, "Notificationn Deactivated", '', "010108", "1.0", "", "POST", "");
    }

    /**
     * | For Hashing Password
     */
    public function hashPassword()
    {
        $datas =  User::select('id', 'password', "old_password")
            ->where('password', '121')
            ->orderby('id')
            ->get();

        foreach ($datas as $data) {
            $user = User::find($data->id);
            if (!$user || $user->password != '121') {
                continue;
            }
            DB::beginTransaction();
            $user->password = Hash::make($data->old_password);
            $user->update();
            DB::commit();
        }
    }

    /**
     * | List User Type
     */
    public function listUserType(Request $req)
    {
        $userType = Config::get('constants.USER_TYPE');
        return responseMsgs(true, "User Type", $userType);
    }

    public function userDtls(Request $req)
    {
        try {
            $docUrl = Config::get('constants.DOC_URL');
            $mWfRoleusermap = new WfRoleusermap();
            $user = Auth()->user();
            $menuRoleDetails = $mWfRoleusermap->getRoleDetailsByUserId($user->id);
            // if (empty(collect($menuRoleDetails)->first())) {
            //     throw new Exception('User has No Roles!');
            // }
            $role = collect($menuRoleDetails)->map(function ($value, $key) {
                $values = $value['roles'];
                return $values;
            });
            $roleWithId = collect($menuRoleDetails)->map(function ($value, $key) {
                return ["role_name" => $value['roles'], "role_id" => $value["roleId"]];
            });
            $permittedWards = UlbWardMaster::select("ulb_ward_masters.id", "ulb_ward_masters.ward_name")
                ->join("wf_ward_users", "wf_ward_users.ward_id", "ulb_ward_masters.id")
                ->where("wf_ward_users.is_suspended", false)
                ->where("ulb_ward_masters.status", 1)
                ->where("wf_ward_users.user_id", $user->id)
                ->get()->map(function ($val) {
                    preg_match_all('/([0-9]+|[a-zA-Z]+)/', $val->ward_name, $matches);
                    $val->char = $matches[0][0] ?? "";
                    $val->ints = $matches[0][1] ?? null;
                    return $val;
                });
            // $permittedWards = collect($permittedWards)->sortBy(function ($item) {
            //     // Extract the numeric part from the "ward_name"
            //     preg_match('/\d+/', $item->ward_name, $matches);
            //     return (int) ($matches[0] ?? "");
            // })->values();
            $permittedWards = collect($permittedWards->sortBy(["char", "ints"]))->values();
            $includeMenu = $this->_UserMenuMobileInclude->metaDtls()
                ->where("user_menu_mobile_includes.user_id", $user->id)
                ->where("user_menu_mobile_includes.is_active", true)
                ->get();
            $excludeMenu = $this->_UserMenuMobileExclude->metaDtls()
                ->where("user_menu_mobile_excludes.user_id", $user->id)
                ->where("user_menu_mobile_excludes.is_active", true)
                ->get();
            $userIncludeMenu = $this->_UserMenuMobileInclude->unionDataWithRoleMenu()
                ->where("user_menu_mobile_includes.user_id", $user->id)
                ->where("user_menu_mobile_includes.is_active", true)
                ->get();
            DB::enablequerylog();
            $menuList = $this->_MenuMobileMaster->metaDtls()
                ->where("menu_mobile_masters.is_active", true)
                ->where(function ($query) {
                    $query->OrWhere("menu_mobile_role_maps.is_active", true)
                        ->orWhereNull("menu_mobile_role_maps.is_active");
                })
                ->WhereIn("menu_mobile_role_maps.role_id", ($menuRoleDetails)->pluck("roleId"));
            if ($includeMenu->isNotEmpty()) {
                $menuList = $menuList->WhereNotIn("menu_mobile_masters.id", ($includeMenu)->pluck("menu_id"));
            }

            DB::enableQueryLog();
            $menuList = collect(($menuList->get())->toArray());
            foreach ($userIncludeMenu->toArray() as $val) {
                $menuList->push($val);
            }
            $menuList = collect($menuList->whereNotIn("id", ($excludeMenu)->pluck("menu_id"))->toArray());
            $menuList = $menuList->map(function ($val) {

                return
                    [
                        "id"        =>  $val["id"],
                        "role_id"   =>  $val["role_id"],
                        "role_name" =>  $val["role_name"],
                        "parent_id" =>  $val["parent_id"],
                        "module_id" =>  $val["module_id"],
                        "serial"    =>  $val["serial"],
                        "menu_string" =>  $val["menu_string"],
                        "route"      =>  $val["route"],
                        "icon"       =>  $val["icon"],
                        "is_sidebar" =>  $val["is_sidebar"],
                        "is_menu"    =>  $val["is_menu"],
                        "create"     =>  $val["create"],
                        "read"       =>  $val["read"],
                        "update"     =>  $val["update"],
                        "delete"     =>  $val["delete"],
                        "module_name" =>  $val["module_name"],
                    ];
            });

            $module = $this->_ModuleMaster->select("id", "module_name")->where("is_suspended", false)->OrderBy("id", "ASC")->get();
            $routList = collect();
            foreach ($module as $val) {
                $rout["layout"] = $val->module_name;
                $rout["pages"] = $menuList->where("module_id", $val->id)->sortBy("serial")->values();
                $routList->push($rout);
            }

            $data['userDetails'] = $user;
            $data['userDetails']["imgFullPath"] = trim($docUrl . "/" . $user->photo_relative_path . "/" . $user->photo, "/");
            $data['userDetails']['role'] = $role;
            $data['userDetails']['roleWithId'] = $roleWithId;
            $data["routes"] = $routList;
            $data["permittedWard"] = $permittedWards;
            $GEO_MAX_AGE = (object)collect($this->_FrontConstains->getConnectionByName("GEO_MAX_AGE")->values())->first();
            $IS_GEO_ENABLE = (object)collect($this->_FrontConstains->getConnectionByName("IS_GEO_ENABLE")->values())->first();
            $data['isGeoEnable'] = $IS_GEO_ENABLE ? $IS_GEO_ENABLE->convert_values : false;
            $data['geoMaxAge'] = $GEO_MAX_AGE ? $GEO_MAX_AGE->convert_values : false;
            return responseMsgs(true, "You have Logged In Successfully", $data, 010101, "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 010101, "1.0", responseTime(), "POST", $req->deviceId);
        }
    }
}
