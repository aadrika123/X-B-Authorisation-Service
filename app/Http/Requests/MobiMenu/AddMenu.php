<?php

namespace App\Http\Requests\MobiMenu;

use App\Http\Requests\ParentRequest;

class AddMenu extends ParentRequest
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
            "roleId"    =>  "nullable|digits_between:0,9999999999",
            "parentId"  =>  "nullable|digits_between:0,9999999999",
            "moduleId"  =>  "required|digits_between:0,9999999999",
            "serial"    =>  "nullable|required_if:parentId,==,0|digits_between:0,9999999999",
            "menuName"  =>  "required|regex:$this->_REX_ALPHA_NUM_DOT_SPACE",
            "path"      =>  "required|string",
            "icon"      =>  "nullable|string",
        ];
        return $rules;
    }
}
