<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ArchiveParticipantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => trim((string) $this->input('note')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
