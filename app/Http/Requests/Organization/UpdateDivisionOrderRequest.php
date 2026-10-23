<?php

namespace App\Http\Requests\Organization;

use App\Models\Division;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDivisionOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'division_ids' => ['required', 'array'],
            'division_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * The submitted division IDs, in their new order.
     *
     * @return list<int>
     */
    public function divisionIds(): array
    {
        return array_values(array_map(intval(...), $this->array('division_ids')));
    }

    /**
     * The new order has to list every one of the organization's divisions exactly once.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $submitted = $this->divisionIds();
                sort($submitted);
                $existing = Division::query()->orderBy('id')->pluck('id')->all();

                if ($submitted !== $existing) {
                    $validator->errors()->add('division_ids', 'The divisions changed while you were reordering. Refresh and try again.');
                }
            },
        ];
    }
}
