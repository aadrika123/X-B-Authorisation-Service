<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    /**
     * | Save Api
     */
    public function store($request)
    {
        $mFaq = new Faq();
        $mFaq->question  =  $request->question;
        $mFaq->answer    =  $request->answer;
        $mFaq->module_id =  $request->moduleId;
        $mFaq->save();
    }

    /**
     * | Update the Api master details
     */
    public function edit($request)
    {
        $mFaq = Faq::findorfail($request->id);
        $mFaq->question  =  $request->question ?? $mFaq->question;
        $mFaq->answer    =  $request->answer   ?? $mFaq->answer;
        $mFaq->module_id =  $request->moduleId ?? $mFaq->module_id;
        $mFaq->save();
    }

    /**
     * | 
     */
    public function faqList()
    {
        return Faq::select('faqs.id', 'question', 'answer', 'module_name', 'module_id')
            ->join('module_masters', 'module_masters.id', 'faqs.module_id')
            ->where('faqs.is_suspended', false)
            ->orderbyDesc('faqs.id');
    }
}
