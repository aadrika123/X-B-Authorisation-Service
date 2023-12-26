<?php

namespace App\Http\Controllers;

use App\MicroServices\IdGeneration;
use App\Models\Auth\ActiveCitizen;
use App\Models\OtpMaster;
use App\Models\OtpRequest;
use App\Models\User;
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
                'type' => "nullable|in:Register,Forgot",
            ]);
            $mOtpRequest = new OtpRequest();
            if ($request->type == "Register") {
                $userDetails = ActiveCitizen::where('mobile', $request->mobileNo)
                    ->first();
                if ($userDetails) {
                    throw new Exception("Mobile no $request->mobileNo is registered to An existing account!");
                }
            }
            if ($request->type == "Forgot") {
                $userDetails = ActiveCitizen::where('mobile', $request->mobileNo)
                    ->first();
                if (!$userDetails) {
                    throw new Exception("Please check your mobile.no!");
                }
            }
            $generateOtp = $this->generateOtp();

            // $data =
            //     [
            //         "owner_name" => 'customerName',
            //         "saf_no"     => 'transactionNo'
            //     ];

            // // $type = "Forget Password";
            // $type = $request->type == "Register" ? "Register" : "Forgot Password";

            // $sms = "Dear " . $data["owner_name"] . ", congratulations on submitting your Assessment application! Your Ref No. is " . $data["saf_no"] . ". For details visit www.akolamc.org/call us at:18008907909 SWATI INDUSTRIES";
            // // $sms = $generateOtp . " is your One time password (OTP) for " . $type . " to Akola Municipal Corporation. Don't Share Otp with anyone. Please enter the OTP to proceed." . "For details visit www.akolamc.org/call us at:18008907909 SWATI INDUSTRIES";
            // $response = SMSAKGOVT(8797770238, $sms, 1707169564185074869);

            DB::beginTransaction();
            $mOtpRequest->saveOtp($request, $generateOtp);
            DB::commit();
            return responseMsgs(true, "OTP send to your mobile No!", $generateOtp, "", "01", ".ms", "POST", "");
        } catch (Exception $e) {
            DB::rollBack();
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
            $token = $mActiveCitizen->changeToken($request);
            $checkOtp->delete();
            DB::commit();
            return responseMsgs(true, "OTP Validated!", remove_null($token), "", "01", ".ms", "POST", "");
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
        // $otp = Carbon::createFromDate()->milli . random_int(100, 999);
        $otp = 123123;
        return $otp;
    }
}
