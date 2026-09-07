<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Data\DocumentData;
use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * See plan/phases/phase-08-admin-qr.md M8.7/M7.1. A DWG/DXF (or anything
 * else a browser can't render) requires a `preview_file` in the same
 * request — this is the enforcement point for the pairing rule Phase 07
 * only modelled, never validated, since no upload path existed yet.
 */
class StoreDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:51200'],
            'preview_file' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'document_type' => ['required', new Enum(DocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'revision' => ['nullable', 'string', 'max:20'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_primary' => $this->boolean('is_primary')]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('file');

            if (! $file || ! $file->isValid()) {
                return;
            }

            $needsPreview = ! DocumentData::isRenderableMime((string) $file->getMimeType());

            if ($needsPreview && ! $this->file('preview_file')) {
                $validator->errors()->add(
                    'preview_file',
                    'A DWG cannot be displayed in a browser — attach a PDF or image preview.',
                );
            }
        });
    }
}
