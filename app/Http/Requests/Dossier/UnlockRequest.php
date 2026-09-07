<?php

declare(strict_types=1);

namespace App\Http\Requests\Dossier;

use Illuminate\Foundation\Http\FormRequest;

class UnlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'redirect' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
