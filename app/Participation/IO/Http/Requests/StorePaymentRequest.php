<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use App\Participation\Entities\Payment;
use App\Participation\Entities\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'slip' => ['nullable', 'file', 'mimes:'.Payment::SLIP_MIMES, 'max:5120'],
        ];
    }
}
