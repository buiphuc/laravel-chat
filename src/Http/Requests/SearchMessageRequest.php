<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => 'required|string|min:2',
            'room_id' => 'sometimes|integer',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
