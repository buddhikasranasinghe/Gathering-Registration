<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:xlsx,csv',
                'mimes:xlsx,csv',
                'max:5120',
            ],
        ];
    }
}
