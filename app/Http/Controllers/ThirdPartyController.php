<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestChangePssByToken;
use App\Http\Requests\RequestOtpVerify;
use App\Http\Requests\RequestSendOtpUpdate;
use App\MicroServices\IdGeneration;
use App\Models\Auth\ActiveCitizen;
use App\Models\Auth\User as AuthUser;
use App\Models\OtpMaster;
use App\Models\OtpRequest;
use App\Models\PasswordResetOtpToken;
use App\Models\User;
use App\Pipelines\Citizen\SearchByEmail as CitizenSearchByEmail;
use App\Pipelines\Citizen\SearchByMobile as CitizenSearchByMobile;
use App\Pipelines\Otp\SearchByEmail as OtpSearchByEmail;
use App\Pipelines\Otp\SearchByMobile as OtpSearchByMobile;
use App\Pipelines\Otp\SearchByOtpType as OtpSearchByOtpType;
use App\Pipelines\Otp\SearchByUserType as OtpSearchByUserType;
use App\Pipelines\Otp\SearchByOtp as OtpSearchByOtp;
use App\Pipelines\User\SearchByEmail;
use App\Pipelines\User\SearchByMobile;
use Carbon\Carbon;
use Seshac\Otp\Otp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use WpOrg\Requests\Auth;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Hash;

class ThirdPartyController extends Controller
{
    // OTP related Operations


    /**
     * | Send OTP for Use
     * | OTP for Changing PassWord using the mobile no 
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 01
        | Working
        | Dont share otp 
     */
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'mobileNo' => "required|digits:10|regex:/[0-9]{10}/", #exists:active_citizens,mobile|
                'type' => "nullable|in:Register,Forgot,Update Mobile",
            ]);
            $mOtpRequest = new OtpRequest();
            $mobileNo    =  $request->mobileNo;
            if ($request->type == "Register") {
                $userDetails = ActiveCitizen::where('mobile', $mobileNo)
                    ->first();
                if ($userDetails) {
                    throw new Exception("Mobile no $mobileNo is registered to An existing account!");
                }
            }
            if ($request->type == "Forgot") {
                $userDetails = ActiveCitizen::where('mobile', $mobileNo)
                    ->first();
                if (!$userDetails) {
                    throw new Exception("Please Check Your Mobile No!");
                }
            }

            switch ($request->type) {
                case ('Register'):
                    $otpType = 'Citizen Registration';
                    break;

                case ('Forgot'):
                    $otpType = 'Forgot Password';
                    break;

                case ('Update Mobile'):
                    $otpType = 'Update Mobile';
                    break;
            }

            $generateOtp = $this->generateOtp();
            $sms         = "OTP for " . $otpType . " at Akola Municipal Corporation's portal is " . $generateOtp . ". This OTP is valid for 10 minutes.";

            $response = SMSAKGOVT($mobileNo, $sms, 1707170367857263583);
            $mOtpRequest->saveOtp($request, $generateOtp);

            return responseMsgs(true, "OTP send to your mobile No!", "", "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0101", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Verify OTP 
     * | Check OTP and Create a Token
     * | @param request
        | Serial No : 02
        | Working
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'otp' => "required|digits:6",
                'mobileNo' => "required|digits:10|regex:/[0-9]{10}/|exists:otp_requests,mobile_no"
            ]);
            # model
            $mOtpMaster     = new OtpRequest();
            $mActiveCitizen = new ActiveCitizen();

            # logi 
            DB::beginTransaction();
            $checkOtp = $mOtpMaster->checkOtp($request);
            if (!$checkOtp) {
                $msg = "OTP not match!";
                return responseMsgs(false, $msg, "", "", "01", ".ms", "POST", "");
            }

            $otpLog = $checkOtp->replicate();
            $otpLog->setTable('log_otp_requests');
            $otpLog->id = $checkOtp->id;
            $otpLog->save();

            // $token = $mActiveCitizen->changeToken($request);
            $checkOtp->delete();
            DB::commit();
            return responseMsgs(true, "OTP Validated!", "", "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Generate Random OTP 
     */
    public function generateOtp()
    {
        $otp = str_pad(Carbon::createFromDate()->milli . random_int(100, 999), 6, 0);
        // $otp = 123123;
        return $otp;
    }

    /**
     * ||================✍Send Otp On Register Email Or Mobile ✍=============================
     * ||                   Created By: Sandeep 
     * ||                   Date      : 13-01-2024
     */
    public function ForgatePasswordSendOtp(RequestSendOtpUpdate $request)
    {
        try {
            $mOtpRequest = new OtpRequest();
            $email = $request->email;
            $mobileNo  = $request->mobile;
            $userType = $request->userType;
            $otpType = 'Forgot Password';
            if(!$email && !$mobileNo)
            {
                throw new Exception("Invalid Data Given");
            }
            $userData = AuthUser::where('suspended', false)
                ->orderBy('id','DESC');
            $userData = app(Pipeline::class)
                ->send(
                    $userData
                )
                ->through([
                    SearchByEmail::class,
                    SearchByMobile::class
                ])
                ->thenReturn()
                ->first();
            if($userType=="Citizen")
            {
                $userData = ActiveCitizen::orderBy('id','DESC');
                $userData = app(Pipeline::class)
                ->send(
                    $userData
                )
                ->through([
                    CitizenSearchByEmail::class,
                    CitizenSearchByMobile::class
                ])
                ->thenReturn()
                ->first();
            }            
            if(!$userData){
                throw new Exception("Data Not Find");
            }
            $generateOtp = $this->generateOtp();
            $request->merge([
                "mobileNo"=>$request->mobile,
                "type"=>$otpType,
                "otpType"=>$otpType,
                "Otp" => $generateOtp,
                "userId"=>$userData->id,
                "userType"=>$userData->gettable(),
            ]);
            $smsDta = OTP($request->all());
            if($mobileNo &&  !$smsDta["status"])
            {
                throw new Exception("Some Error Occures Server On Otp Sending");
            }
            $sms = $smsDta["sms"]??"";
            $temp_id = $smsDta["temp_id"]??"";            
            $sendsOn = [];
            if($mobileNo){
                $response = send_sms($mobileNo, $sms, $temp_id);         
                $sendsOn[]="mobile No.";
            }
            if($email)
            {
                $sendsOn[]="Email";
            }
            $responseSms = "";
            foreach($sendsOn as $val)
            {
                $responseSms .= ($val." & ");
            }
            $responseSms = trim($responseSms,"& ");
            $responseSms = "OTP send to your ".$responseSms;
            $mOtpRequest->saveOtp($request, $generateOtp);            
            
            return responseMsgs(true, $responseSms, "", "", "01", ".ms", "POST", "");
            
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    public function verifyOtpV2(RequestOtpVerify $request)
    {
        try {            
            # model
            $mOtpMaster     = new OtpRequest();
            $mActiveCitizen = new ActiveCitizen();
            $mUsers         = new AuthUser();
            $mPasswordResetOtpToken         = new PasswordResetOtpToken();
            # logi 
            
            $checkOtp = $mOtpMaster::orderBy("id","DESC");
            $checkOtp = app(Pipeline::class)
                ->send(
                    $checkOtp
                )
                ->through([
                    OtpSearchByEmail::class,
                    OtpSearchByMobile::class,
                    OtpSearchByOtpType::class,
                    OtpSearchByUserType::class,
                    OtpSearchByOtp::class,
                ])
                ->thenReturn()
                ->first();
            
            if (!$checkOtp) {
                throw new Exception("OTP not match!");
            }
            if ($checkOtp->expires_at < Carbon::now()) {
                $this->transerLog($checkOtp);
                throw new Exception("OTP is expired");
            }
            $checkOtp->use_date_time = Carbon::now();
            $request->merge([
                "tokenableType"  => $checkOtp->gettable(),
                "tokenableId"  => $checkOtp->id,
                "userType"     => $checkOtp->user_type,
                "userId"     => $checkOtp->user_id,
            ]);
            
            DB::beginTransaction();
            $checkOtp->update();
            $this->transerLog($checkOtp);
            
            $sms = "OTP Validated!";
            $response=[];
            if($checkOtp->otp_type =="Forgot Password")
            {
                $sms = "OTP Varify Proccied For Password Update. Token Is Valide Only 10 minutes";
                $response["token"] = $mPasswordResetOtpToken->store($request);
            }
            DB::commit();            
            
            return responseMsgs(true, $sms, $response, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    private function transerLog(OtpRequest $checkOtp)
    {
        $OldOtps =  OtpRequest::where("expires_at",Carbon::now())
                                ->whereNotNull("expires_at")
                                ->where(DB::raw("CAST(created_at AS Date)"),Carbon::now()->format("Y-m-d"))
                                ->get();
        foreach($OldOtps as $val)
        {
            $otpLog = $val->replicate();
            $otpLog->setTable('log_otp_requests');
            $otpLog->id = $val->id;
            $otpLog->save();
            $checkOtp->delete();
        }
        if($checkOtp)
        {
            $otpLog = $checkOtp->replicate();
            $otpLog->setTable('log_otp_requests');
            $otpLog->id = $checkOtp->id;
            $otpLog->save();
            $checkOtp->delete();
        }
        
    }

    public function changePasswordV2(RequestChangePssByToken $request)
    {
        try {            
            # model
            $mActiveCitizen = new ActiveCitizen();
            $mUsers         = new AuthUser();
            $mPasswordResetOtpToken         = new PasswordResetOtpToken();
            # logi 
            $requestToken = $mPasswordResetOtpToken
                            ->where("token",$request->token)
                            ->where("status",0)
                            ->whereNotNull("user_type")
                            ->whereNotNull("user_id")
                            ->first();
            if (!$requestToken) {
                throw new Exception("Invalid Token");
            }    
            if ($requestToken->expires_at < Carbon::now() ) {
                throw new Exception("Token Is Expired");
            }        
            $users = $requestToken->user_type == $mActiveCitizen->gettable() ? $mActiveCitizen->find($requestToken->user_id) : $mUsers->find($requestToken->user_id);
            if(!$users || (!in_array($requestToken->user_type,[$mActiveCitizen->gettable(),$mUsers->gettable()])))
            {
                throw new Exception("Invalid Password Update Request Apply");
            }
            $requestToken->status = 1;
            $users->password = Hash::make($request->password);

            DB::beginTransaction();
            $users->tokens->each(function ($token, $key){
                $token->expires_at = Carbon::now();
                $token->update();
                $token->delete();
            });
            $requestToken->update();            
            $users->update();
            DB::commit();            
            
            return responseMsgs(true, "Password Updated Successfully", "", "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }
}
