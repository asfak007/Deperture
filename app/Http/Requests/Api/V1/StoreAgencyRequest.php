<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiErrorResponse;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'first_name' => "required|string|min:3",
            'last_name' => "required|string",
            'phone' => "required|string|unique:agencies,phone",
            'email' => "required|email|unique:agencies,email",
            'password' => "required|string|min:3|max:10",
            "address" => "required|string",
            "city" => "required|string",
            "country" => "required|string",
            "agency_name" => "required|string",
            "agency_phone" => "required|string|unique:agencies,agency_phone",
            "agency_email" => "required|email|unique:agencies,agency_email",
            // "thumbnail" => "required",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // "metadata" => "required",
            // "firebase_token" => "required",
            // "status" => "required",
        ];
    }
}
