<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => 'required|string',
            'reason' => 'sometimes|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
