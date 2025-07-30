<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringNoteRequest extends FormRequest
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
            'watchlist_reason' => 'nullable|string|max:1000',
            'account_strategy' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'watchlist_reason.max' => 'Alasan watchlist tidak boleh lebih dari 1000 karakter.',
            'account_strategy.max' => 'Account strategy tidak boleh lebih dari 1000 karakter.',
        ];
    }
}
