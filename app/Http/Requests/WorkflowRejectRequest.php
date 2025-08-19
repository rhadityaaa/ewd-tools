<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class WorkflowRejectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        $report = $this->route('report');
        
        // Check if user has approval role
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            return false;
        }
        
        // Check if user can approve current step (same permission for reject)
        return app(\App\Services\WorkflowService::class)->canApproveCurrentStep($report, $user);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'comments' => 'required|string|max:1000|min:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'comments.required' => 'Komentar wajib diisi saat menolak laporan.',
            'comments.min' => 'Komentar harus minimal 10 karakter.',
            'comments.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'comments' => 'komentar',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        abort(403, 'Anda tidak memiliki akses untuk menolak laporan ini.');
    }
}