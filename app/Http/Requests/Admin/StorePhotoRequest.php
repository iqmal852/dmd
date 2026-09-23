<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One file per request — the admin UI loops this for a multi-file
 * selection, which keeps the payload shape simple instead of a nested
 * per-file array. See plan/phases/phase-08-admin-qr.md M8.7. No cap on
 * how many photos a station can carry — explicit user request; nothing
 * here or in Station::registerMediaCollections() limits the `photos`
 * collection.
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
            'bearing' => ['nullable', 'integer', 'between:0,359'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
