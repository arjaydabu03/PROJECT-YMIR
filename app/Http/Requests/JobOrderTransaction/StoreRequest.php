<?php

namespace App\Http\Requests\JobOrderTransaction;

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
            "jo_number" => [
                $this->route()->job_order_transaction
                    ? "unique:jo_transactions,jo_number," .
                        $this->route()->job_order_transaction
                    : "unique:jo_transactions,jo_number",
            ],
        ];
    }
}
