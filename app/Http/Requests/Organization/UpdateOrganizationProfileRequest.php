<?php

namespace App\Http\Requests\Organization;

use App\Support\BrandColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOrganizationProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'primary_color' => ['nullable', 'hex_color'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'remove_logo' => ['boolean'],
            'show_public_rosters' => ['boolean'],
        ];
    }

    /**
     * The brand color fills buttons and marks on light and dark pages, so it
     * has to stay readable on both.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $color = $this->input('primary_color');

                if ($validator->errors()->has('primary_color') || ! is_string($color) || $color === '') {
                    return;
                }

                $brandColor = new BrandColor($color);

                if ($brandColor->isReadable()) {
                    return;
                }

                $suggestion = $brandColor->nearestReadable();

                $validator->errors()->add('primary_color', $suggestion === null
                    ? 'That color is hard to read on buttons and on dark pages. Pick a mid-tone color.'
                    : "That color is hard to read on buttons and on dark pages. The closest color that works is {$suggestion}.");
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'primary_color' => 'brand color',
        ];
    }
}
