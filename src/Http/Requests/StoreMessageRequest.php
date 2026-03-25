<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => 'required_without:attachments|string|max:' . config('chat.messages.max_length', 5000),
            'type' => 'sometimes|string|in:text,image,file,system',
            'parent_id' => 'sometimes|integer',
            'metadata' => 'sometimes|array',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
