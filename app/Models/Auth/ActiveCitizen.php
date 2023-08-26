<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class ActiveCitizen extends Model
{
    use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * | Get Active Citizens by Moble No
     */
    public function getCitizenByMobile($mobile)
    {
        return ActiveCitizen::where('mobile', $mobile)
            ->first();
    }

    /**
     * | Citizen Registration
     */
    public function citizenRegister($mCitizen, $request)
    {
        $mCitizen->user_name = $request->name;
        $mCitizen->email = $request->email;
        $mCitizen->mobile = $request->mobile;
        $mCitizen->password = Hash::make($request->password);
        $mCitizen->gender = $request->gender;
        $mCitizen->dob    = $request->dob;
        $mCitizen->aadhar = $request->aadhar;
        $mCitizen->is_specially_abled = $request->isSpeciallyAbled;
        $mCitizen->is_armed_force = $request->isArmedForce;
        $mCitizen->ip_address = getClientIpAddress();
        $mCitizen->save();

        return $mCitizen->id;
    }

    public function changeToken($request)
    {
        $citizenInfo = ActiveCitizen::where('mobile', $request->mobileNo)
            ->first();

        if (isset($citizenInfo)) {
            $token['token'] = $citizenInfo->createToken('my-app-token')->plainTextToken;
            $citizenInfo->remember_token = $token['token'];
            $citizenInfo->save();
            return $token;
        }
    }
}
