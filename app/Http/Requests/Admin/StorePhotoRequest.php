<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Multiple files in one request — a real `<input type="file" multiple>`
 * submits every selected file under one field name natively, no
 * DataTransfer/synthetic-FormData trickery needed (that category of
 * approach broke uploads under Pest's browser-testing plugin before —
 * see AdminEndToEndTest's own docblock — so this deliberately stays
 * inside what a native multipart form already does on its own).
 * `labels` is a parallel array, index-aligned with `files` by the
 * frontend rendering one label `<input>` per selected file in the same
 * order. See plan/phases/phase-08-admin-qr.md M8.7. No cap on how many
 * photos a station can carry — explicit user request; nothing here or
 * in Station::registerMediaCollections() limits the `photos` collection.
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
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'labels' => ['array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
