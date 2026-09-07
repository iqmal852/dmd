<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PhotoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePhotoRequest extends FormRequest
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
            'photo_type' => ['required', new Enum(PhotoType::class)],
            'bearing' => ['nullable', 'integer', 'between:0,359'],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
