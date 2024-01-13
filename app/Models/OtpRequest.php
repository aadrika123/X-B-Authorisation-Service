<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;

    /**
     * | Save the Otp for Checking Validatin
     * | @param 
     */
    public function saveOtp($request, $generateOtp)
    {
        // $refData = OtpRequest::where('mobile_no', $request->mobileNo)
        //     ->first();
        // if ($refData) {
        //     $refData->otp_time  = Carbon::now();
        //     $refData->otp       = $generateOtp;
        //     $refData->hit_count = $refData->hit_count + 1;
        //     $refData->update();
        // } else {
        $mOtpMaster = new OtpRequest();
        $mOtpMaster->mobile_no  = $request->mobileNo;
        $mOtpMaster->otp        = $generateOtp;
        $mOtpMaster->otp_time   = Carbon::now();
        $mOtpMaster->otp_type   = $request->type;
        $mOtpMaster->hit_count  = 1;
        $mOtpMaster->email      = $request->email;
        $mOtpMaster->user_id    = $request->userId;
        $mOtpMaster->user_type  = $request->userType;
        $mOtpMaster->expires_at = $request->expiresAt ? $request->expiresAt : Carbon::now()->addMinutes(10);
        $mOtpMaster->save();
        // }
    }

    /**
     * | Check the OTP in the data base 
     * | @param 
     */
    public function checkOtp($request)
    {
        return OtpRequest::where('otp', $request->otp)
            ->where('mobile_no', $request->mobileNo)
            ->orderByDesc('id')
            ->first();
    }
}
