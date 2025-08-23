<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnexoTransaccionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'year' => $this->route('year'),
            'month' => $this->route('month'),
        ]);
    }
}
