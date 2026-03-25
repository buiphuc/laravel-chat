<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnblockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
