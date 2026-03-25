<?php

namespace PhucBui\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:reviewed,dismissed',
        ];
    }

    public function attributes(): array
    {
        return trans('chat::validation.attributes');
    }
}
