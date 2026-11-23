<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Requests;

use App\Participation\Entities\Batch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'excel_mapper_name' => Batch::normalizeExcelMapperName((string) $this->input('excel_mapper_name')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Batch $batch */
        $batch = $this->route('batch');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('batches', 'name')->ignore($batch)],
            'excel_mapper_name' => ['required', 'string', 'max:255', Rule::unique('batches', 'excel_mapper_name')->ignore($batch)],
        ];
    }
}
