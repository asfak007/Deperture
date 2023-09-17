<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return Arr::only(parent::toArray($request), [
            'id',
            'first_name',
            'last_name',
            'username',
            'email',
            'phone',
            'image',
            // 'bookings',
            // Add other user data as needed
        ]);
    }
}
