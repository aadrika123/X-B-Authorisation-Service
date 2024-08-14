<?php

namespace App\Http\Requests\Auth;

use App\Traits\Validate\ValidateTrait;
use Illuminate\Foundation\Http\FormRequest;

class AuthUserRequest extends FormRequest
{
    use ValidateTrait;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->a();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            // 'password' => [
            //     'required',
            //     'min:6',
            //     'max:255',
            //     'regex:/[a-z]/',      // must contain at least one lowercase letter
            //     'regex:/[A-Z]/',      // must contain at least one uppercase letter
            //     'regex:/[0-9]/',      // must contain at least one digit
            //     'regex:/[@$!%*#?&]/'  // must contain a special character
            // ],
            'mobile'    => ['required', 'min:10', 'max:10'],
            'altMobile' => ['required', 'min:10', 'max:10'],
            // 'moduleType' =>['nullable','integer']
            // 'ulb' => ['required', 'integer'],
            // 'userType' => ['required']
        ];
    }
}
