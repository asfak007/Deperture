<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiErrorResponse;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    use ApiErrorResponse;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => "required|string|min:3",
            'last_name' => "required|string",
            "username" => "required|string|unique:customers,username",
            'phone' => "required|string|unique:customers,phone",
            'email' => "required|email|unique:customers,email",
            'password' => "required|string|min:3|max:10",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // 'status' => "nullable|boolean",
        ];
    }
}
