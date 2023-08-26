<?php

namespace App\Models\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiMaster extends Model
{
    use HasFactory;

    /**
     * | Create Api
     */
    public function addApi($req)
    {
        $data = new ApiMaster;
        $data->description      = $req->description;
        $data->category         = $req->category;
        $data->end_point        = $req->endPoint;
        $data->usage            = $req->usage;
        $data->pre_condition    = $req->preCondition;
        $data->request_payload  = $req->requestPayload;
        $data->response_payload = $req->responsePayload;
        $data->post_condition   = $req->postCondition;
        $data->version          = $req->version;
        $data->created_by       = $req->createdBy;
        $data->revision_no      = $req->revisionNo ?? 1;
        $data->remarks          = $req->remarks;
        $data->tags             = implode(',', $req->tags);
        $data->category_id      = $req->categoryId;
        $data->developer_id     = $req->developerId;
        $data->save();
    }

    /**
     * | Update Api
     */
    public function updateApi($req)
    {
        $data = ApiMaster::find($req->id);
        $data->description   = $req->description ?? $data->description;
        $data->category      = $req->category ?? $data->category;
        $data->end_point     = $req->endPoint ?? $data->end_point;
        $data->usage         = $req->usage ?? $data->usage;
        $data->pre_condition = $req->preCondition ?? $data->pre_condition;
        $data->request_payload  = $req->requestPayload ?? $data->request_payload;
        $data->response_payload = $req->responsePayload ?? $data->response_payload;
        $data->post_condition   = $req->postCondition ?? $data->post_condition;
        $data->version          = $req->version ?? $data->version;
        $data->revision_no      = $req->revisionNo ?? $data->revision_no;
        $data->remarks          = $req->remarks ?? $data->remarks;
        $data->tags             = $req->tags ?? $data->tags;
        $data->category_id      = $req->categoryId ?? $data->category_id;
        $data->developer_id     = $req->developerId ?? $data->developer_id;
        $data->save();
    }

    /**
     * | List Api by id
     */
    public function listbyId($req)
    {
        $data = ApiMaster::where('discontinued', false)
            ->where('id', $req->id)
            ->first();
        return $data;
    }

    /**
     * | All Api list
     */
    public function listApi()
    {
        $data = ApiMaster::where('discontinued', false)
            ->orderbydesc('id')
            ->get();
        return $data;
    }

    /**
     * Delete Api
     */
    public function deleteApi($req)
    {
        $data = ApiMaster::find($req->id);
        $data->discontinued = true;
        $data->save();
    }
}
