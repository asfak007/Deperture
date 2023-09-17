<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
        ];
    }
}
