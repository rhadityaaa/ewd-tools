<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormSubmissionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'aspectsBorrower.*.questionId' => 'required|exists:question_versions,id',
            'aspectsBorrower.*.selectedOptionId' => 'required|exists:question_options,id',
            'aspectsBorrower.*.score' => 'required|numeric|min:0',
            'informationBorrower.borrowerId' => 'required|exists:borrowers,id',
            'facilitiesBorrower' => 'required|array|min:1',
            'facilitiesBorrower.*.facilityName' => 'required|string|max:255',
            'facilitiesBorrower.*.limit' => 'required|numeric|min:0',
            'facilitiesBorrower.*.outstanding' => 'required|numeric|min:0',
            'aspectsBorrower' => 'required|array',
            'reportMeta.templateId' => 'required|exists:templates,id',
            'reportMeta.periodId' => 'required|exists:periods,id',
        ];
    }
}