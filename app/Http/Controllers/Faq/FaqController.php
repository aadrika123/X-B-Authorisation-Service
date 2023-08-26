<?php

namespace App\Http\Controllers\Faq;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Exception;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * | Save Faq Role
     */
    public function createfaq(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required',
                'answer'   => 'required',
                'moduleId' => 'required',
            ]);
            $mFaq = new Faq();
            $mFaq->store($request);
            return responseMsgs(true, "Data Saved!", "", "", "02", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }

    /**
     * | Update Faq Role
     */
    public function updatefaq(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $mFaq = new Faq();
            $mFaq->edit($request);
            return responseMsgs(true, "Faq Role Updated!", "", "", "02", "733", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }


    /**
     * | Faq Role by Id
     */
    public function faqbyId(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|int'
            ]);
            $mFaq = new Faq();
            $list = $mFaq->faqList()
                ->where('faqs.id', $request->id)
                ->first();

            return responseMsgs(true, "Faq Role!", $list, "", "01", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", "", "POST", "");
        }
    }

    /**
     * | Faq Role List
     */
    public function faqList(Request $request)
    {
        try {
            $request->validate([
                'moduleId' => 'nullable|int'
            ]);

            $mFaq = new Faq();
            if ($request->moduleId) {
                $list = $mFaq->faqList()
                    ->where('module_id', $request->moduleId)
                    ->get();
            } else
                $list = $mFaq->faqList()->get();

            return responseMsgs(true, "List of Faq!", $list, "", "01", "", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", "", "POST", "");
        }
    }

    /**
     * | Delete Faq Role
     */
    public function deletefaq(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required'
            ]);
            Faq::where('id', $request->id)
                ->update(['is_suspended' => true]);
            return responseMsgs(true, "Faq Role Deleted!", "", "", "02", "733", "POST", "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "");
        }
    }
}
