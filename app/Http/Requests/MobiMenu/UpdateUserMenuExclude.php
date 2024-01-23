<?php

namespace App\Http\Requests\MobiMenu;

class UpdateUserMenuExclude extends AddUserMenuExclude
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
        $rules["id"] ="required|digits_between:0,9999999999";
        $rules["status"] ="nullable|boolean";
        return $rules;
    }
}
