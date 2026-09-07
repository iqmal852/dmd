<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * See plan/phases/phase-08-admin-qr.md M8.6. The Malaysia range check
 * (`sometimes|warn`) is deliberately a soft warning surfaced client-side,
 * not a server-side validation error — the app must not refuse valid data
 * because someone deployed it outside Malaysia, but a swapped lat/lon is
 * the single most common survey data-entry error, so the client warns
 * before submit rather than after.
 */
class UpdateCoordinateSetRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'ellipsoidal_height' => ['required', 'numeric', 'between:-100,3000'],
            'easting' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'northing' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'zone' => ['nullable', 'string', 'max:20'],
            'orthometric_height' => ['required', 'numeric', 'between:-100,3000'],
            'geoid_model' => ['required', 'string', 'max:50'],
            'epoch' => ['nullable', 'string', 'max:20'],
            'computed_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
