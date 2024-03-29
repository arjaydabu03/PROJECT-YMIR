<?php

namespace App\Http\Requests\PurchaseRequest;

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
            "pr_number" => [
                $this->route()->pr_transaction
                    ? "unique:pr_transactions,pr_number," .
                        $this->route()->pr_transaction
                    : "unique:pr_transactions,pr_number",

                    
            ],
            // "supplier_id" => "exists:suppliers,id,deleted_at,NULL",
        ];
    }
}
