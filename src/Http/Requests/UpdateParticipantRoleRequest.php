<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParticipantRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_name' => 'required|string',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
