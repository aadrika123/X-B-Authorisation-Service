<?php

namespace App\Http\Requests\MobiMenu;

use App\Http\Requests\ParentRequest;

class AddUserMenuExclude extends ParentRequest
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
            "userId"    => "required|digits_between:0,9999999999",
            "menuId"    => "required|digits_between:0,9999999999",
        ];
        return $rules;
    }
}
