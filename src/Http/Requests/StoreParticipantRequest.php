<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor_id' => 'required|integer',
            'actor_type' => 'required|string',
            'role_name' => 'sometimes|string',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
