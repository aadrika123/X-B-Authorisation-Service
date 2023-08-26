<?php

namespace App\BLL;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * | Calculate Price On Advertisement & Market
 * | Created By- Bikash Kumar
 * | Created On 04 Aug 2023
 * | Status - Open
 */


class AuthorizationBll
{
    protected $_baseUrl;
    public function __construct()
    {
        $this->_baseUrl = Config::get('constants.BASE_URL');
    }

    /**
     * | Add Files if File is exist in request
     */
    public function addFiles($files,$response)
    {
        // Generate Application No
        $fileName = [];
        foreach ($files as $index => $val) {
            array_push($fileName, $index);
        }
        $dotIndexes = $this->generateDotIndexes($files);
        foreach ($dotIndexes as $val) {
            $patern = "/\.name/i";
            if (!preg_match($patern, $val)) {
                continue;
            }
            $name = "";
            $test = collect(explode(".", preg_replace($patern, "", $val)));
            $t = $test->filter(function ($val, $index) {
                return $index > 0 ? true : "";
            });
            $t = $t->map(function ($val) {
                return "[" . $val . "]";
            });
            $name = (($test[0]) . implode("", $t->toArray()));
            $response = $response->attach(
                $name,
                file_get_contents($this->getArrayValueByDotNotation($_FILES, preg_replace($patern, ".tmp_name", $val))),
                $this->getArrayValueByDotNotation($_FILES, $val)
            );
        }
        return $response;
    }


    /**
     * | Add All Text fields
     */
    public function addTextFields($req,$response){
        $new = [];
        foreach (collect($req->all())->toArray() as $key => $val) {
            $new[$key] = $val;
        }
        $textIndex = $this->generateDotIndexes($new);
        $new2 = [];
        foreach ($textIndex as $val) {
            $name = "";
            $test = collect(explode(".", $val));
            $t = $test->filter(function ($val, $index) {
                return $index > 0 ? true : "";
            });
            $t = $t->map(function ($val) {
                return "[" . $val . "]";
            });
            $name = (($test[0]) . implode("", $t->toArray()));
            $new2[] = [
                "contents" => $this->getArrayValueByDotNotation($new, $val),
                "name" => $name
            ];
        }
        return $new2;
    }



    public function generateDotIndexes(array $array, $prefix = '', $result = [])
    {
        foreach ($array as $key => $value) {
            $newKey = $prefix . $key;
            if (is_array($value)) {
                $result = $this->generateDotIndexes($value, $newKey . '.', $result);
            } else {
                $result[] = $newKey;
            }
        }
        return $result;
    }

    public function getArrayValueByDotNotation(array $array, string $key)
    {
        $keys = explode('.', $key);

        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $array = $array[$key];
            } else {
                return null; // Key doesn't exist in the array
            }
        }

        return $array;
    }

}
