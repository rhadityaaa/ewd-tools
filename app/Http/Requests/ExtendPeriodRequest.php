<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendPeriodRequest extends FormRequest
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
        $period = $this->route('period');

        return [
            'end_date' => 'required|date|after_or_equal:' . ($period->start_date ? $period->start_date->format('Y-m-d') : 'today'),
            'end_time' => 'nullable|string|date_format:H:i:s',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'end_time' => $this->end_time ?? '23:59:59',
        ]);
    }
}
