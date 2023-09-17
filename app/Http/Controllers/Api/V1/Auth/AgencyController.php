<?php

namespace App\Http\Controllers\Api\V1\Auth;

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
use App\Http\Resources\Api\V1\AgencyResource;
use App\Http\Requests\Api\V1\AgencyLoginRequest;
use App\Http\Requests\Api\V1\StoreAgencyRequest;
use App\Http\Requests\Api\V1\UpdateAgencyRequest;
use App\Http\Requests\Api\V1\UpdateAgencyPasswordRequest;

class AgencyController extends Controller
{
    /**
     * Retrieve agency info.
     */
    public function agency(Request $request)
    {
        try {
            $agency = $request->user('agency');
            // $agency->load(["address", "bookings"]);
            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Successfully authorized.",
                'data'      => [
                    "agency" => new AgencyResource($agency)
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
     * Logout agency.
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user('agency')) {
                $request->user('agency')->tokens()->delete();
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
     * Update agency password database.
     */
    public function password(UpdateAgencyPasswordRequest $request)
    {
        // get agency
        $agency = $request->user('agency');

        try {
            if (!$agency || !Hash::check($request->password, $agency->password)) {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNAUTHORIZED,
                    'message'   => "Unauthenticated credentials.",
                    'errors' => [
                        "password" => ['Invalid credentials.']
                    ]
                ],  HTTP::HTTP_UNAUTHORIZED); // HTTP::HTTP_OK
            }

            // Update the agency data
            $agency->password = Hash::make($request->input("new_password", $request->password));
            $agency->save();

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Password updated successfully.",
            ],  HTTP::HTTP_OK); // HTTP::HTTP_OK
        } catch (\Exception $e) {
            throw $e;
            return Response::json([
                'success'   => false,
                'status'    => HTTP::HTTP_FORBIDDEN,
                'message'   => "Something went wrong.",
                // 'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * Agency Login with mail and phone.
     */
    public function login(AgencyLoginRequest $request)
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

            $agency = Agency::where($credentials)->first();

            if (!$agency || !Hash::check($password, $agency->password)) {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNAUTHORIZED,
                    'message'   => "Unauthenticated agency credentials.",
                    'errors' => [
                        "password" => ['Invalid credentials.']
                    ]
                ],  HTTP::HTTP_UNAUTHORIZED); // HTTP::HTTP_OK
            }
            // $request->user('agency')->tokens()->delete();

            // $agency->tokens()->delete(); // uncomment for live server
            $token = $agency->createToken('authToken')->plainTextToken;

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
     * Crete a newly created agency in database.
     */
    public function register(StoreAgencyRequest $request)
    {
        try {
            // If the agency is not registered, proceed with registration
            $phone = $request->phone;
            $ttl = 1; // 1 min lock for otp

            $agency = new Agency();
            $agency->category_id = $request->category_id;
            $agency->first_name = $request->first_name;
            $agency->last_name = $request->last_name;
            $agency->phone = $request->phone;
            $agency->email = $request->email;
            $agency->password = Hash::make($request->password);
            $agency->address = $request->address;
            $agency->city = $request->city;
            $agency->country = $request->country;
            $agency->agency_name = $request->agency_name;
            $agency->agency_phone = $request->agency_phone;
            $agency->agency_email = $request->agency_email;
            $agency->save();

            // $agency->thumbnail = $request->thumbnail;
            // $agency->metadata = $request->// "metadata
            // $agency->firebase_token = $request->// "firebase_token
            // $agency->status = $request->// "status

            event(new RegisteredAgency($agency));


            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "agency_$agency->id.png";
                $imagePath = "assets/images/agency/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/agency"))) {
                        File::makeDirectory((public_path("assets/images/agency")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/agency'), $imageName);
                    $agency->image = $imagePath;
                    $agency->save();
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

            $token = $agency->createToken('authToken')->plainTextToken;
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
     * Update agency data database.
     */
    public function update(UpdateAgencyRequest $request)
    {
        // get agency
        $agency = $request->user('agency');

        $credentials = Arr::only($request->all(), [
            'firebase_token',
        ]);

        try {
            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "customer_$agency->id.png";
                $imagePath = "assets/images/agency/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/agency"))) {
                        File::makeDirectory((public_path("assets/images/agency")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/agency'), $imageName);
                    $credentials["image"] = $imagePath;
                } catch (\Exception $e) {
                    // throw $e;
                    // skip if not uploaded
                }
            }


            // Update the agency data
            $agency->update($credentials);

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
     * Deactivate agency from database.
     */
    public function deactivate(Request $request)
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

        // get agency
        $agency = $request->user('agency');

        try {
            // Verify the provided password
            if (password_verify($request->password, $agency->password)) {
                // Delete the account from the database
                $agency->tokens()->delete();
                // $agency->delete();
                $agency->status = false;
                $agency->save();
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
                'message'   => "Account deactivate successfully.",
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

    /**
     * Delete agency from database.
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

        // get agency
        $agency = $request->user('agency');

        try {
            // Verify the provided password
            if (password_verify($request->password, $agency->password)) {
                // Delete the account from the database
                $agency->tokens()->delete();
                $agency->delete();
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
