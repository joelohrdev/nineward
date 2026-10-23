<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    /**
     * Owners can't delete their account while their organization depends on it.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('password')) {
                    return;
                }

                if (Organization::where('owner_id', $this->user()?->id)->exists()) {
                    $validator->errors()->add(
                        'password',
                        'You own an organization. Transfer ownership or delete the organization before deleting your account.',
                    );
                }
            },
        ];
    }
}
