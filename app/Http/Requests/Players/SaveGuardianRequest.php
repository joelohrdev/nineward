<?php

namespace App\Http\Requests\Players;

use App\Models\Guardian;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Adding a guardian to a player, or correcting an existing guardian's details.
 */
class SaveGuardianRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $guardian = $this->route('guardian');
        $isEditing = $guardian instanceof Guardian;

        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                ...($isEditing ? [Rule::unique('guardians')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->ignore($guardian->id)] : []),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'relationship' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * A claimed guardian's email is their login, so it can't be changed here.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $guardian = $this->route('guardian');

                if ($guardian instanceof Guardian
                    && $guardian->isClaimed()
                    && mb_strtolower(trim($this->string('email')->toString())) !== $guardian->email) {
                    $validator->errors()->add('email', "This guardian has claimed their account, so their email can't be changed here.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another guardian already uses that email.',
        ];
    }

    /**
     * @return array{first_name: string, last_name: string, email: string, phone: string|null}
     */
    public function guardianDetails(): array
    {
        return [
            'first_name' => $this->string('first_name')->toString(),
            'last_name' => $this->string('last_name')->toString(),
            'email' => $this->string('email')->toString(),
            'phone' => $this->filled('phone') ? $this->string('phone')->toString() : null,
        ];
    }
}
