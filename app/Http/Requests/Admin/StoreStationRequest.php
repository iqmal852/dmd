<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Models\Station;
use App\Rules\UniqueStationCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * See plan/phases/phase-08-admin-qr.md M8.3.
 */
class StoreStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Station::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', new UniqueStationCode],
            'highway' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
            'km' => ['required', 'numeric', 'between:0,2000'],
            'direction' => ['required', new Enum(Direction::class)],
            'monument_type' => ['required', 'string', 'max:50'],
            'installed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', new Enum(StationStatus::class)],
            'description' => ['nullable', 'string'],
            'access_password' => ['nullable', 'string', 'min:8'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
