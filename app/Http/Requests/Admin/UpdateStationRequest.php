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
 * See plan/phases/phase-08-admin-qr.md M8.3. `public_id` is deliberately
 * absent from every rule here — it is never mass-assignable through this
 * request regardless of what a client submits (see
 * StationController::update()'s explicit field list).
 */
class UpdateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Station $station */
        $station = $this->route('station');

        return $this->user()?->can('update', $station) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Station $station */
        $station = $this->route('station');

        return [
            'code' => ['required', 'string', 'max:50', new UniqueStationCode($station->id)],
            'gcp_reference' => ['nullable', 'string', 'max:255'],
            'highway' => ['required', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'km' => ['required', 'numeric', 'between:0,2000'],
            'direction' => ['required', new Enum(Direction::class)],
            'facility_type' => ['nullable', 'string', 'max:255'],
            'monument_type' => ['required', 'string', 'max:50'],
            'installed_at' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', new Enum(StationStatus::class)],
            'description' => ['nullable', 'string'],
            // Blank means "leave the existing password alone" — see
            // clearAccessPassword() below for the only way to actually
            // remove it. A blank value here must never mean "make this
            // station public," or an admin editing an unrelated field
            // (the KM value, say) would silently un-gate it.
            'access_password' => ['nullable', 'string', 'min:8'],
            'clear_access_password' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'clear_access_password' => $this->boolean('clear_access_password'),
        ]);
    }

    public function clearAccessPassword(): bool
    {
        return (bool) $this->validated('clear_access_password', false);
    }
}
