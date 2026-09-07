<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `dimensions:ratio=2/1` enforces the panorama's required 2:1 equirectangular
 * aspect ratio server-side — client-side validation happens too, but never
 * instead of this. See plan/phases/phase-08-admin-qr.md M8.7 Test Gate #11.
 */
class StorePanoramaRequest extends FormRequest
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
            'file' => ['required', 'image', 'mimes:jpeg,webp', 'max:20480', 'dimensions:ratio=2/1'],
            'initial_yaw' => ['nullable', 'numeric', 'between:-360,360'],
            'initial_pitch' => ['nullable', 'numeric', 'between:-90,90'],
            'hfov' => ['nullable', 'integer', 'between:50,150'],
        ];
    }
}
