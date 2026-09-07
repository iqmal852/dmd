<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PhotoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * One file per request — the admin UI loops this for a multi-file
 * selection, which keeps the payload shape simple instead of a nested
 * per-file array. See plan/phases/phase-08-admin-qr.md M8.7.
 */
class StorePhotoRequest extends FormRequest
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
            'file' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'photo_type' => ['required', new Enum(PhotoType::class)],
            'bearing' => ['nullable', 'integer', 'between:0,359'],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
