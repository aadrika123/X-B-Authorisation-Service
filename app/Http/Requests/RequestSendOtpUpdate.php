<?php

namespace App\Http\Requests;


class RequestSendOtpUpdate extends ParentRequest
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
        $rules = [
            'email' => 'required_without:mobile|email',
            'mobile' => 'required_without:email|digits:10',
            "userType"=>"nullable|string|in:Citizen",
            "otpType" => "nullable|string|in:Forgot Password,Register,Attach Holding,Update Mobile",
        ];
        return $rules;
    }
}
