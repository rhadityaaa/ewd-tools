<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'selected_aspects' => ['required', 'array', 'min:1'],
            'selected_aspects.*.id' => ['required', 'integer', 'exists:aspects,id'],
            'selected_aspects.*.weight' => ['required', 'numeric', 'min:1'],
            'visibility_rules' => ['nullable', 'array'],
            'visibility_rules.*.description' => ['nullable', 'string'],
            'visibility_rules.*.source_type' => ['required_with:visibility_rules', Rule::in(['borrower_detail', 'borrower_facility', 'answer'])],
            'visibility_rules.*.source_field' => ['required_with:visibility_rules', 'string'],
            'visibility_rules.*.operator' => ['required_with:visibility_rules', 'string'],
            'visibility_rules.*.value' => ['required_with:visibility_rules', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode template wajib diisi.',
            'name.required' => 'Nama template wajib diisi.',
            'selected_aspects.required' => 'Anda harus memilih setidaknya satu aspek.',
            'selected_aspects.*.id.exists' => 'Aspek yang dipilih tidak valid.',
            'selected_aspects.*.weight.required' => 'Setiap aspek harus memiliki bobot.',
        ];
    }
}
