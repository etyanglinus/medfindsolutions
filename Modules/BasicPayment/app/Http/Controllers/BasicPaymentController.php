<?php

namespace Modules\BasicPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Currency\app\Models\MultiCurrency;

class BasicPaymentController extends Controller {
    public function basicpayment() {
        checkAdminHasPermissionAndThrowException('basic.payment.view');

        $payment_info = BasicPayment::get();

        $basic_payment = [];

        foreach ($payment_info as $payment_item) {
            $basic_payment[$payment_item->key] = $payment_item->value;
        }

        // Ensure every key the views reference has a default so we never get
        // "Undefined property: stdClass::$xxx" if a DB row was never seeded.
        $defaults = [
            'stripe_key'             => '',
            'stripe_secret'          => '',
            'stripe_charge'          => 0,
            'stripe_status'          => 'inactive',
            'stripe_image'           => '',
            'stripe_currency_id'     => null,
            'paypal_app_id'          => '',
            'paypal_client_id'       => '',
            'paypal_secret_key'      => '',
            'paypal_account_mode'    => 'sandbox',
            'paypal_currency_id'     => null,
            'paypal_charge'          => 0,
            'paypal_status'          => 'inactive',
            'paypal_image'           => '',
            'bank_information'       => '',
            'bank_status'            => 'inactive',
            'bank_image'             => '',
            'bank_charge'            => 0,
            'bank_currency_id'       => null,
            'razorpay_key'           => '',
            'razorpay_secret'        => '',
            'razorpay_name'          => '',
            'razorpay_description'   => '',
            'razorpay_charge'        => 0,
            'razorpay_theme_color'   => '#6d0ce4',
            'razorpay_status'        => 'inactive',
            'razorpay_currency_id'   => null,
            'razorpay_image'         => '',
            'flutterwave_public_key' => '',
            'flutterwave_secret_key' => '',
            'flutterwave_app_name'   => '',
            'flutterwave_charge'     => 0,
            'flutterwave_currency_id'=> null,
            'flutterwave_status'     => 'inactive',
            'flutterwave_image'      => '',
            'paystack_public_key'    => '',
            'paystack_secret_key'    => '',
            'paystack_charge'        => 0,
            'paystack_status'        => 'inactive',
            'paystack_image'         => '',
            'paystack_currency_id'   => null,
            'mollie_key'             => '',
            'mollie_charge'          => 0,
            'mollie_status'          => 'inactive',
            'mollie_image'           => '',
            'mollie_currency_id'     => null,
            'instamojo_api_key'      => '',
            'instamojo_auth_token'   => '',
            'instamojo_account_mode' => 'Sandbox',
            'instamojo_charge'       => 0,
            'instamojo_currency_id'  => null,
            'instamojo_status'       => 'inactive',
            'instamojo_image'        => '',
        ];

        // Merge: DB values take precedence over defaults
        $basic_payment = (object) array_merge($defaults, $basic_payment);

        $currencies = MultiCurrency::get();
        return view('basicpayment::index', compact('basic_payment', 'currencies'));
    }

    public function update_stripe(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'stripe_key'    => 'required',
            'stripe_secret' => 'required',
            'stripe_charge' => 'required|numeric|max:100',
            'stripe_status' => 'required|in:inactive,active',
        ];

        $customMessages = [
            'stripe_key.required'    => __('Stripe key is required'),
            'stripe_secret.required' => __('Stripe secret is required'),
            'stripe_charge.required' => __('Gateway charge is required'),
            'stripe_charge.numeric'  => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        if ($request->file('stripe_image')) {
            $this->updateImage($request->stripe_image, 'stripe_image');
        }

        $this->updateColumns($request->only('stripe_key', 'stripe_secret', 'stripe_charge', 'stripe_status'));

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];
        return redirect()->back()->with($notification);
    }

    public function update_paypal(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');

        $rules = [
            'paypal_client_id'  => 'required',
            'paypal_secret_key' => 'required',
            'paypal_charge'     => 'required|numeric|max:100',
            'paypal_app_id'     => 'required',
            'paypal_status'     => 'required|in:inactive,active',
        ];

        $customMessages = [
            'paypal_client_id.required'  => __('Client is required'),
            'paypal_secret_key.required' => __('Secret key is required'),
            'paypal_charge.required'     => __('Gateway charge is required'),
            'paypal_app_id.required'     => __('App id is required'),
            'paypal_charge.numeric'      => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        if ($request->file('paypal_image')) {
            $this->updateImage($request->paypal_image, 'paypal_image');
        }

        $this->updateColumns($request->only('paypal_client_id', 'paypal_secret_key', 'paypal_app_id', 'paypal_charge', 'paypal_status', 'paypal_account_mode'));

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function update_bank_payment(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');

        $rules = [
            'bank_information' => 'required',
            'bank_charge'      => 'required|numeric',
            'bank_status'      => 'required|in:inactive,active',
        ];

        $customMessages = [
            'bank_information.required' => __('Bank information is required'),
            'bank_charge.required'      => __('Gateway charge is required'),
            'bank_charge.numeric'       => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        if ($request->file('bank_image')) {
            $this->updateImage($request->bank_image, 'bank_image');
        }

        $this->updateColumns($request->only('bank_information', 'bank_charge', 'bank_status'));

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function razorpay_update(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'razorpay_key'         => 'required',
            'razorpay_secret'      => 'required',
            'razorpay_charge'      => 'required|numeric',
            'razorpay_name'        => 'required',
            'razorpay_description' => 'required',
            'razorpay_theme_color' => 'required',
        ];
        $customMessages = [
            'razorpay_key.required'         => __('Razorpay key is required'),
            'razorpay_secret.required'      => __('Razorpay secret is required'),
            'razorpay_charge.required'      => __('Gateway charge is required'),
            'razorpay_charge.numeric'       => __('Gateway charge should be numeric'),
            'razorpay_name.required'        => __('Name is required'),
            'razorpay_description.required' => __('Description is required'),
            'razorpay_theme_color.required' => __('Theme is required'),
        ];

        $request->validate($rules, $customMessages);

        $this->updateColumns($request->only('razorpay_key', 'razorpay_secret', 'razorpay_charge', 'razorpay_name', 'razorpay_description', 'razorpay_theme_color', 'razorpay_status'));

        if ($request->file('razorpay_image')) {
            $this->updateImage($request->razorpay_image, 'razorpay_image');
        }

        Cache::forget('basic_payment');

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    public function flutterwave_update(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'flutterwave_public_key' => 'required',
            'flutterwave_secret_key' => 'required',
            'flutterwave_charge'     => 'required|numeric',
            'flutterwave_app_name'   => 'required',
        ];
        $customMessages = [
            'flutterwave_public_key.required' => __('Public key is required'),
            'flutterwave_secret_key.required' => __('Secret key is required'),
            'flutterwave_charge.required'     => __('Gateway charge is required'),
            'flutterwave_charge.numeric'      => __('Gateway charge should be numeric'),
            'flutterwave_app_name.required'   => __('Name is required'),
        ];

        $request->validate($rules, $customMessages);

        $this->updateColumns($request->only('flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_charge', 'flutterwave_app_name', 'flutterwave_status'));

        if ($request->file('flutterwave_image')) {
            $this->updateImage($request->flutterwave_image, 'flutterwave_image');
        }

        Cache::forget('basic_payment');

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    public function paystack_update(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'paystack_public_key' => 'required',
            'paystack_secret_key' => 'required',
            'paystack_charge'     => 'required|numeric',
        ];
        $customMessages = [
            'paystack_public_key.required' => __('Public key is required'),
            'paystack_secret_key.required' => __('Secret key is required'),
            'paystack_charge.required'     => __('Gateway charge is required'),
            'paystack_charge.numeric'      => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        $this->updateColumns($request->only('paystack_charge', 'paystack_public_key', 'paystack_secret_key', 'paystack_status'));

        if ($request->file('paystack_image')) {
            $this->updateImage($request->paystack_image, 'paystack_image');
        }

        Cache::forget('basic_payment');

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);

    }

    public function mollie_update(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'mollie_key'    => 'required',
            'mollie_charge' => 'required|numeric',
        ];
        $customMessages = [
            'mollie_key.required'    => __('Mollie key is required'),
            'mollie_charge.required' => __('Gateway charge is required'),
            'mollie_charge.numeric'  => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        $this->updateColumns($request->only('mollie_charge', 'mollie_key', 'mollie_status'));

        if ($request->file('mollie_image')) {
            $this->updateImage($request->mollie_image, 'mollie_image');
        }

        Cache::forget('basic_payment');

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function instamojo_update(Request $request) {
        checkAdminHasPermissionAndThrowException('basic.payment.update');
        $rules = [
            'instamojo_api_key'    => 'required',
            'instamojo_auth_token' => 'required',
            'instamojo_charge'     => 'required|numeric',
        ];
        $customMessages = [
            'instamojo_api_key.required'    => __('API key is required'),
            'instamojo_auth_token.required' => __('Auth token is required'),
            'instamojo_charge.required'     => __('Gateway charge is required'),
            'instamojo_charge.numeric'      => __('Gateway charge should be numeric'),
        ];

        $request->validate($rules, $customMessages);

        $this->updateColumns($request->only('instamojo_charge', 'instamojo_api_key', 'instamojo_auth_token', 'instamojo_account_mode', 'instamojo_status'));

        if ($request->file('instamojo_image')) {
            $this->updateImage($request->instamojo_image, 'instamojo_image');
        }

        Cache::forget('basic_payment');

        $notification = __('Update Successfully');
        $notification = ['message' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    private function updateColumns($request) {
        foreach ($request as $key => $value) {
            BasicPayment::where('key', $key)->update(['value' => $value]);
        }

        Cache::forget('basic_payment');
    }

    private function updateImage(UploadedFile $image, string $fieldName) {
        $bank_setting = BasicPayment::where('key', $fieldName)->first();
        $basicImage = $bank_setting ? $bank_setting?->value : null;
        if ($file_name = file_upload($image, 'uploads/custom-images/', $basicImage)) {
            $bank_setting->value = $file_name;
            $bank_setting->save();
        }
    }
}
