<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiErrorResponse;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            "name" => "required|string|min:3",
            "price" => "required|integer",
            "guide_id" => "nullable|integer",
            "agency_id" => "required|exists:agencies,id",
            // "category_id" => "required|exists:categories,id",
            "short_description" => "required|string",
            "long_description" => "required|string",
            "address" => "required|string",
            "discount" => "required|integer",
            // "thumbnail" => "required",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            // "metadata" => "required",
            // "booking_count" => "required|integer",
        ];
    }
}
