<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'metadata' => 'sometimes|array',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
