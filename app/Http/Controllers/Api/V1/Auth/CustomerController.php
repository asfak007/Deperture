<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\Customer;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Events\RegisteredCustomer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response as HTTP;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Requests\Api\V1\CustomerLoginRequest;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerPasswordRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;

class CustomerController extends Controller
{
    /**
     * Retrieve customer info.
     */
    public function customer(Request $request)
    {
        try {
            $customer = $request->user('customer');
            // $customer->load(["address", "bookings"]);
            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Successfully authorized.",
                'data'      => [
                    "customer" => new CustomerResource($customer)
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
     * Logout customer.
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user('customer')) {
                $request->user('customer')->tokens()->delete();
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
                'message'   => "Something went wrong.",
                'err' => $e->getMessage(),
            ],  HTTP::HTTP_FORBIDDEN); // HTTP::HTTP_OK
        }
    }

    /**
     * customer Login with mail and phone.
     */
    public function login(CustomerLoginRequest $request)
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

            $customer = Customer::where($credentials)->first();

            if (!$customer || !Hash::check($password, $customer->password)) {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNAUTHORIZED,
                    'message'   => "Unauthenticated customer credentials.",
                    'errors' => [
                        "password" => ['Invalid credentials.']
                    ]
                ],  HTTP::HTTP_UNAUTHORIZED); // HTTP::HTTP_OK
            }
            // $request->user('customer')->tokens()->delete();

            // $customer->tokens()->delete(); // uncomment for live server
            $token = $customer->createToken('authToken')->plainTextToken;

            return Response::json([
                'success'   => true,
                'status'    => HTTP::HTTP_OK,
                'message'   => "Customer successfully authorized.",
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
     * Crete a newly created customer in database.
     */
    public function register(StoreCustomerRequest $request)
    {
        try {
            // return $request->all();
            // If the customer is not registered, proceed with registration
            $phone = $request->phone;
            $ttl = 1; // 1 min lock for otp
            $customer = new Customer();
            $customer->first_name = $request->first_name;
            $customer->last_name = $request->last_name;
            $customer->username = $request->username;
            $customer->phone = $request->phone;
            $customer->email = $request->email;
            $customer->password = Hash::make($request->password);
            $customer->save();

            event(new RegisteredCustomer($customer));


            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "customer_$customer->id.png";
                $imagePath = "assets/images/customer/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/customer"))) {
                        File::makeDirectory((public_path("assets/images/customer")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/customer'), $imageName);
                    $customer->image = $imagePath;
                    $customer->save();
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

            $token = $customer->createToken('authToken')->plainTextToken;
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
     * Update customer data database.
     */
    public function update(UpdateCustomerRequest $request)
    {
        // get customer
        $customer = $request->user('customer');

        $credentials = Arr::only($request->all(), [
            'first_name',
            'last_name',
            'image',
        ]);

        try {
            // Handle image upload and update
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = "customer_$customer->id.png";
                $imagePath = "assets/images/customer/$imageName";

                try {
                    // Create the "public/images" directory if it doesn't exist
                    if (!File::isDirectory(public_path("assets/images/customer"))) {
                        File::makeDirectory((public_path("assets/images/customer")), 0777, true, true);
                    }

                    // Save the image to the specified path
                    $image->move(public_path('assets/images/customer'), $imageName);
                    $credentials["image"] = $imagePath;
                } catch (\Exception $e) {
                    // throw $e;
                    // skip if not uploaded
                }
            }


            // Update the customer data
            $customer->update($credentials);

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
     * Update customer password database.
     */
    public function password(UpdateCustomerPasswordRequest $request)
    {
        // get customer
        $customer = $request->user('customer');

        try {
            if (!$customer || !Hash::check($request->password, $customer->password)) {
                return Response::json([
                    'success'   => false,
                    'status'    => HTTP::HTTP_UNAUTHORIZED,
                    'message'   => "Unauthenticated credentials.",
                    'errors' => [
                        "password" => ['Invalid credentials.']
                    ]
                ],  HTTP::HTTP_UNAUTHORIZED); // HTTP::HTTP_OK
            }

            // Update the customer data
            $customer->password = Hash::make($request->input("new_password", $request->password));
            $customer->save();

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
     * Deactivate customer from database.
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

        // get customer
        $customer = $request->user('customer');

        try {
            // Verify the provided password
            if (password_verify($request->password, $customer->password)) {
                // Delete the account from the database
                $customer->tokens()->delete();
                // $customer->delete();
                $customer->status = false;
                $customer->save();
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
}
