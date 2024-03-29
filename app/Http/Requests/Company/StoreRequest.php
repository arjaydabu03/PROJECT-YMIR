<?php

namespace App\Http\Requests\Company;

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
                $this->route()->company
                    ? "unique:companies,name," . $this->route()->company
                    : "unique:companies,name",
                "string",
            ],
            "code" => [
                "required",
                "string",
                $this->route()->company
                    ? "unique:companies,code," . $this->route()->company
                    : "unique:companies,code",
            ],
        ];
    }
}
