<?php

namespace Hitexis\Wholesale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WholesaleRequest extends FormRequest
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
            'batch_amount' => 'required|int',
            'discount_percentage' => 'required|int',
        ];
    }
}
