<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HTTP;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class wishlistController extends Controller
{
    public function getWishlist(){

    }

    public function add(Request $request){
        try {

        $validator = Validator::make($request->all(), [

            'service_id' => 'required|integer',

        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // If validation passes, proceed to add the wishlist item
        $customerId = $request->user('customer')->id;
        $serviceId = $request->input('service_id');
            // Check if a wishlist item for the same customer and service exists
            $existingWishlistItem = Wishlist::where('customer_id', $customerId)
                ->where('service_id', $serviceId)
                ->first();

            if ($existingWishlistItem) {
                // If it exists, update its metadata
                $existingWishlistItem->metadata = Service::find($serviceId);
                $existingWishlistItem->save();
            } else {
                // If it doesn't exist, create a new wishlist item
                Wishlist::create([
                    'customer_id' => $customerId,
                    'service_id' => $serviceId,
                    'metadata' => Service::find($serviceId),
                ]);
            }
        return Response::json([
            'success'   => true,
            'status'    => HTTP::HTTP_OK,
            'message'   => "Thank you for your review.",
        ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
    } catch (\Exception $e) {
    // throw $e;
       return Response::json([
       'success'   => false,
       'status'    => HTTP::HTTP_FORBIDDEN,
       'message'   => "Something went wrong. Try after sometimes.",
       'err' => $e->getMessage(),
        ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK

        }
    }
    public function delete(Request $request){
        try {
            $wishlistId = $request->input('wishlist_id');

            // Call the delete function from the WishlistService
            $this->wishlistService->delete($wishlistId);

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Wishlist deleted",
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            // throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong. Try after sometimes.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK

        }
    }
}
