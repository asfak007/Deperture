<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiErrorResponse;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
            'first_name' => "required|string|min:2",
            'last_name' => "nullable|required|string",
            // "username" => "required|unique:customers,username",
            // 'phone' => "required|unique:customers,phone",
            // 'email' => "required|email|unique:customers,email",
            // 'password' => "required|min:3|max:10",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
