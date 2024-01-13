<?php

namespace App\Http\Requests;

class RequestOtpVerify extends RequestSendOtpUpdate
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules["Otp"]= "required|digits_between:0,9999999999";
        if($this->userType=='Citizen')
        {
            $this->merge(["userType"=>"active_citizens"]);
            $rules["userType"]= "nullable|string|in:active_citizens";
        }
        return $rules;
    }
}
