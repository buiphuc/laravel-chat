<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_id' => 'required_without:name|integer',
            'target_type' => 'required_with:target_id|string',
            'name' => 'required_without:target_id|string|max:255',
            'max_members' => 'nullable|integer|min:2',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'integer',
            'participant_type' => 'required_with:participant_ids|string',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
