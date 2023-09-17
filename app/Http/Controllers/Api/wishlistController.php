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
    public function getWishlist(Request $request){
        try {
            $categoryId = $request->input('category_id');
            $customerId = $request->user('customer')->id;

            // Call the delete function from the WishlistService

            $data = Wishlist::where('customer_id',$customerId)->where('category_id',$categoryId)->get();

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Wishlist deleted",
                'data'      => [
                    'wishlist'  => $data
                ]
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
        $categoryId = $request->input('category_id');
            // Check if a wishlist item for the same customer and service exists
            $existingWishlistItem = Wishlist::where('customer_id', $customerId)
                ->where('service_id', $serviceId)
                ->first();

            if ($existingWishlistItem) {
                // If it exists, update its metadata
                return Response::json([
                    'success'   => true,
                    'status'    => HTTP::HTTP_OK,
                    'message'   => "wishlist already Exist",
                ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
            } else {
                // If it doesn't exist, create a new wishlist item
                Wishlist::create([
                    'customer_id' => $customerId,
                    'category_id' => $categoryId,
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
    public function delete(Request $request)
    {
        try {
            $wishlistId = $request->input('wishlist_id');


            // Find the wishlist item by ID
            $wishlist = Wishlist::find($wishlistId);

            if (!$wishlist) {
                return response()->json([
                    'success' => false,
                    'status' => HTTP::HTTP_NOT_FOUND,
                    'message' => "Wishlist not found.",
                ], HTTP::HTTP_NOT_FOUND);
            }

            // Delete the wishlist item
            $wishlist->delete();

            return response()->json([
                'success' => true,
                'status' => HTTP::HTTP_OK,
                'message' => "Wishlist deleted",
            ], HTTP::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => HTTP::HTTP_FORBIDDEN,
                'message' => "Something went wrong. Try again later.",
                'err' => $e->getMessage(),
            ], HTTP::HTTP_FORBIDDEN);
        }
    }

}
