<?php

namespace App\Http\Controllers;

use App\MicroServices\IdGeneration;
use App\Models\Auth\ActiveCitizen;
use App\Models\OtpMaster;
use App\Models\OtpRequest;
use App\Models\User;
use Carbon\Carbon;
use Seshac\Otp\Otp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use WpOrg\Requests\Auth;

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
            $generateOtp = $this->generateOtp();

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
            $sms = "OTP for " . $otpType . " at Akola Municipal Corporation's portal is " . $generateOtp . ". This OTP is valid for 10 minutes.";

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
}
