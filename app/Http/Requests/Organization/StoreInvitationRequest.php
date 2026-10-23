<?php

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRole;
use App\Models\Membership;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(OrganizationRole::class)->only([OrganizationRole::Admin, OrganizationRole::Coach])],
        ];
    }

    /**
     * People who are already members don't need an invitation.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('email')) {
                    return;
                }

                $isMember = Membership::query()
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->whereHas('user', fn ($user) => $user->whereRaw('lower(email) = ?', [mb_strtolower(trim($this->string('email')->toString()))]))
                    ->exists();

                if ($isMember) {
                    $validator->errors()->add('email', 'That person is already a member.');
                }
            },
        ];
    }
}
