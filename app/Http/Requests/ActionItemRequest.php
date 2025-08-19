<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActionItemRequest extends FormRequest
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
            'action_description' => 'required|string|max:500',
            'item_type' => 'required|in:previous_period, current_progress, next_period',
            'people_in_charge' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending, in_progress, completed, overdue'
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
            'action_description.required' => 'Deskripsi action item wajib diisi.',
            'action_description.max' => 'Deskripsi action item tidak boleh lebih dari 500 karakter.',
            'item_type.required' => 'Tipe item wajib dipilih.',
            'item_type.in' => 'Tipe item tidak valid.',
            'progress_notes.required' => 'Progress notes wajib diisi untuk item dari periode sebelumnya.',
            'progress_notes.max' => 'Progress notes tidak boleh lebih dari 1000 karakter.',
            'people_in_charge.max' => 'PIC tidak boleh lebih dari 255 karakter.',
            'notes.max' => 'Catatan tidak boleh lebih dari 1000 karakter.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'completion_date.date' => 'Format tanggal penyelesaian tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.'
        ];
    }
}
