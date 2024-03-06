<?php

namespace App\Http\Requests\FinancialStatement;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "name" => [
                "required",
                $this->route()->account_type
                    ? "unique:account_types,name," .
                        $this->route()->account_type
                    : "unique:account_types,name",
                "string",
            ],
        ];
    }
}
