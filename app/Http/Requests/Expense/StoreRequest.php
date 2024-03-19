<?php

namespace App\Http\Requests\Expense;

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
                $this->route()->expense
                    ? "unique:pr_transactions,pr_number," .
                        $this->route()->expense
                    : "unique:pr_transactions,pr_number",
            ],
            "company_id" => "exists:companies,id,deleted_at,NULL",
            "business_unit_id" => "exists:business_units,id,deleted_at,NULL",
            "department_id" => "exists:departments,id,deleted_at,NULL",
            "department_unit_id" =>
                "exists:department_units,id,deleted_at,NULL",
            "sub_unit_id" => "exists:sub_units,id,deleted_at,NULL",
            "location_id" => "exists:locations,id,deleted_at,NULL",
            "account_title_id" => "exists:account_titles,id,deleted_at,NULL",
            "supplier_id" => "exists:suppliers,id,deleted_at,NULL",

            "order.*.uom_id" => "exists:uoms,id,deleted_at,NULL",
        ];
    }
}
