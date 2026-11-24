<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use App\Participation\Entities\ParticipantRelation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::enum(ParticipantRelation::class)],
        ];
    }
}
