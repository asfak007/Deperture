<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\Guide;
use App\Models\Agency;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Events\RegisteredAgency;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response as HTTP;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\GuideResource;
use App\Http\Requests\Api\V1\GuideLoginRequest;
use App\Http\Requests\Api\V1\StoreAgencyRequest;
use App\Http\Requests\Api\V1\UpdateGuideRequest;

class GuideController extends Controller
{
    /**
     * Retrieve guide info.
     */
    public function guide(Request $request)
    {
        try {
            $guide = $request->user('guide');
            // $guide->load(["address", "bookings"]);
            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Successfully authorized.",
                'data'      => [
                    "guide" => new GuideResource($guide)
                ]
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * Logout guide.
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user('guide')) {
                $request->user('guide')->tokens()->delete();
                return Response::json([
                    'success'   => true,
                    'status'    => HTTP::HTTP_OK,
                    'message'   => "Logged out successfully.",
                ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
            } else {
                return Response::json([
                    'success'   => true,
                    'status'    => HTTP::HTTP_OK,
                    'message'   => "You have already logged out.",
                ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
            }
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong. Try after sometimes.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * guide Login with mail and phone.
     */
    public function login(GuideLoginRequest $request)
    {
        $loginField = $request->input('login_field');
        $password = $request->input('password');

        try {
            $credentials = [];
            if (filter_var($loginField, FILTER_VALIDATE_EMAIL)) {
                // The input is an email
                $credentials['email'] = $loginField;
            } else {
                // The input is a phone number
                $credentials['phone'] = $loginField;
            }


            $guide = Guide::where($credentials)->first();

            if (!$guide || !Hash::check($password, $guide->password)) {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNAUTHORIZED,
                    'message'   => "Unauthenticated guide credentials.",
                    'errors' => [
                        "password" => ['Invalid credentials.']
                    ]
                ],  HTTP::HTTP_UNAUTHORIZED); // HTTP::HTTP_OK
            }
            // $request->user('guide')->tokens()->delete();

            // $guide->tokens()->delete(); // uncomment for live server
            $token = $guide->createToken('authToken')->plainTextToken;

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Agency successfully authorized.",
                'data'      => [
                    'token' => $token
                ]
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * Crete a newly created guide in database.
     */
    public function register(StoreAgencyRequest $request)
    {
        try {
            // If the guide is not registered, proceed with registration
            $phone = $request->phone;
            $ttl = 1; // 1 min lock for otp
            $guide = Agency::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => $request->input("status", false),
            ]);

            event(new RegisteredAgency($guide));


            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "guide_$guide->id.png";
                $imagePath = "assets/images/guide/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/guide"))) {
                        File::makeDirectory((public_path("assets/images/guide")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/guide'), $imageName);
                    $guide->image = $imagePath;
                    $guide->save();
                } catch (\Exception $e) {
                    //throw $e;
                    // skip if not uploaded
                }
            }

            // Send the OTP to the user's phone
            try {
                Cache::remember("$phone", 60 * $ttl, function () { // disabled for 2 minutes
                    return true;
                });

                // start::sending otp
                // send otp here
                // end::sending otp
            } catch (\Exception $e) {
                //throw $e;

                // skip error for first time send OTP
            }

            $token = $guide->createToken('authToken')->plainTextToken;
            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_CREATED,
                'message'   => "Successfully registered.",
                "data"      => [
                    "token" => $token
                ]
            ],  HTTP::HTTP_CREATED); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * Update guide data database.
     */
    public function update(UpdateGuideRequest $request)
    {
        // get guide
        $guide = $request->user('guide');

        $credentials = Arr::only($request->all(), [
            'first_name',
            'last_name',
            'email',
            'image',
        ]);

        try {
            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "guide_$guide->id.png";
                $imagePath = "assets/images/guide/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/guide"))) {
                        File::makeDirectory((public_path("assets/images/guide")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/guide'), $imageName);
                    $credentials["image"] = $imagePath;
                } catch (\Exception $e) {
                    // throw $e;
                    // skip if not uploaded
                }
            }


            // Update the guide data
            $guide->update($credentials);

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Profile updated successfully.",
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong. Try after sometimes.",
                // 'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }


    /**
     * Delete guide from database.
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_UNPROCESSABLE_ENTITY,
                'message'   => "Validation failed.",
                'errors' => $validator->errors()
            ],  HTTP::HTTP_UNPROCESSABLE_ENTITY); // HTTP::HTTP_OK
        }

        // get guide
        $guide = $request->user('guide');

        try {
            // Verify the provided password
            if (password_verify($request->password, $guide->password)) {
                // Delete the account from the database
                $guide->tokens()->delete();
                $guide->delete();
            } else {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNPROCESSABLE_ENTITY,
                    'message'   => "Invalid password.",
                    'errors'     => [
                        "password" => ['Invalid credentials.']
                    ],
                ],  HTTP::HTTP_UNPROCESSABLE_ENTITY); // HTTP::HTTP_OK
            }

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_ACCEPTED,
                'message'   => "Account deleted successfully.",
            ],  HTTP::HTTP_ACCEPTED); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            //throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                // 'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }
}
