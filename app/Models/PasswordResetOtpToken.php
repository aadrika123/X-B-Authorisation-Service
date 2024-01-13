<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtpToken extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $ciphering = "AES-128-CTR";
    protected $encryption_iv = '1234567891011121';
    protected $decryption_iv = '1234567891011121';
    protected $options = 0;
    protected $encryption_key = "Akola";
    protected $decryption_key = "Akola";

    public function encrypt($plainText){
        return openssl_encrypt($plainText, $this->ciphering,
                    $this->encryption_key, $this->options, $this->encryption_iv);
    }

    public function decrypt($encryptext)
    {
        return openssl_decrypt ($encryptext, $this->ciphering, 
                    $this->decryption_key, $this->options, $this->decryption_iv);
    }

    public function store($request)
    {
        $PasswordResetOtpToken = new PasswordResetOtpToken();

        $PasswordResetOtpToken->tokenable_type = $request->tokenableType;
        $PasswordResetOtpToken->tokenable_id = $request->tokenableId;
        $PasswordResetOtpToken->token      = $this->encrypt(Carbon::now()->parse());
        $PasswordResetOtpToken->created_at = Carbon::now();
        $PasswordResetOtpToken->expires_at = Carbon::now()->addMinutes(10);
        $PasswordResetOtpToken->user_type  = $request->userType;
        $PasswordResetOtpToken->user_id    = $request->userId;
        $PasswordResetOtpToken->save();

        return $PasswordResetOtpToken->token;
    }



}
