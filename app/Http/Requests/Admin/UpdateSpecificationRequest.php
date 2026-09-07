<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\QcStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * See plan/phases/phase-08-admin-qr.md M8.6.
 */
class UpdateSpecificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('station')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'observation_method' => ['nullable', 'string', 'max:50'],
            'observation_minutes' => ['nullable', 'integer', 'min:0'],
            'satellite_count' => ['nullable', 'integer', 'between:0,60'],
            'pdop_max' => ['nullable', 'numeric', 'between:0,99'],
            'elevation_cutoff_deg' => ['nullable', 'integer', 'between:0,90'],
            'antenna_type' => ['nullable', 'string', 'max:100'],
            'antenna_height' => ['nullable', 'numeric', 'min:0'],
            'antenna_reference_point' => ['nullable', 'string', 'max:50'],
            'horizontal_rms_mm' => ['nullable', 'numeric', 'between:0,10000'],
            'vertical_rms_mm' => ['nullable', 'numeric', 'between:0,10000'],
            'qc_status' => ['required', new Enum(QcStatus::class)],
            'verified_at' => ['nullable', 'date', 'before_or_equal:today'],
            'verified_by' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
