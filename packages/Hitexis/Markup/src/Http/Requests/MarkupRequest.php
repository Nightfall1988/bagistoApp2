<?php

namespace Hitexis\Markup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkupRequest extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => 'required',
            // 'currency' => 'required',
            'name' => 'required',
        ];
    }
}
