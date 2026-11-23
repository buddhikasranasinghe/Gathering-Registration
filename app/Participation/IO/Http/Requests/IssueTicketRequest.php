<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
