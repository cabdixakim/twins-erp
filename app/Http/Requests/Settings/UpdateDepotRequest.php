<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepotRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasPermission('depots.manage');
    }

    public function rules(): array
    {
        return [
            'name'                   => ['required', 'string', 'max:255'],
            'city'                   => ['nullable', 'string', 'max:255'],
            'storage_fee_per_1000_l' => ['nullable', 'numeric', 'min:0'],
            'default_shrinkage_pct'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active'              => ['nullable', 'boolean'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'storage_fee_per_1000_l' => $this->input('storage_fee_per_1000_l', 0),
            'default_shrinkage_pct'  => $this->input('default_shrinkage_pct', 0.3),
            'is_active'              => $this->boolean('is_active'),
        ]);
    }
}