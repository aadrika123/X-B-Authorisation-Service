<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiMaster;
use App\Models\ApiCategory;
use App\Models\DeveloperList;
use Exception;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    //create master
    public function createApi(Request $req)
    {
        try {
            $req->validate([
                'description' => 'required',
                'category'    => 'required',
                'endPoint'    => 'required',
                'tags'        => 'required|array',
            ]);

            $create = new ApiMaster();
            $create->addApi($req);

            return responseMsgs(true, "Api Saved", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    //update master
    public function updateApi(Request $req)
    {
        try {
            $req->validate([
                'id'          => 'required'
            ]);
            $update = new ApiMaster();
            $list  = $update->updateApi($req);

            return responseMsg(true, "Successfully Updated", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //master list by id
    public function ApibyId(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $listById = new ApiMaster();
            $list  = $listById->listbyId($req);

            return responseMsg(true, "Api List", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    //all master list
    public function getAllApi()
    {
        try {
            $list = new ApiMaster();
            $Api = $list->listApi();

            return responseMsg(true, "All Api List", $Api);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    //delete master
    public function deleteApi(Request $req)
    {
        try {
            $req->validate([
                'id' => 'required'
            ]);
            $delete = new ApiMaster();
            $delete->deleteApi($req);

            return responseMsg(true, "Data Deleted", '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Developer List
     */
    public function listDeveloper(Request $req)
    {
        try {

            $mDeveloperList = new DeveloperList();
            $list = $mDeveloperList->developerList();

            return responseMsg(true, "Developer List", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Category List
     */
    public function listCategory(Request $req)
    {
        try {

            $mApiCategory = new ApiCategory();
            $list = $mApiCategory->categoryList();

            return responseMsg(true, "Category List", $list);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
