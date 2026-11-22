<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Requests;

use App\Identity\Entities\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in([UserRole::User->value, UserRole::Admin->value]),
            ],
        ];
    }
}
