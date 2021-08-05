<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

class SslCommerzeService
{
    use ConsumesExternalServices;

    protected $baseUri;
    // protected $clientId;
    // protected $clientSecret;
    // protected $plans;

    public function __construct()
    {
        $this->baseUri = config('services.sslcommerze.base_uri');
        // $this->clientId = config('services.paypal.client_id');
        // $this->clientSecret = config('services.paypal.client_secret');
        // $this->plans = config('services.paypal.plans');
    }

    public function resolveAuthorization(&$queryParams, &$formParams, &$headers)
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response)
    {
        return json_decode($response);
    }

    public function resolveAccessToken()
    {
        return "";
    }

    public function handlePayment(Request $request)
    {
        $order = $this->createOrder($request->value, $request->currency);

        // $orderLinks = collect($order->links);

        // $approve = $orderLinks->where('rel', 'approve')->first();

        session()->put('sessionKey', $order->sessionkey);

        return redirect($order->GatewayPageURL);
    }

    public function handleApproval()
    {
        if(session()->has('sessionKey')) {
            $sessionKey = session()->get('sessionKey');

            $payment = $this->sslCapture($sessionKey);

            // $name = $payment->payer->name->given_name;

            // $payment = $payment->purchase_units[0]->payments->captures[0]->amount;

            $amount = $payment->amount;

            $currency = $payment->currency_type;

            return redirect()->route('home')->withSuccess(['payment' => "Thanks. We have received {$amount} {$currency} payment from you."]);
        }

        return redirect()->route('home')->withErrors('We can not capture the payment. Please, Try again!');
    }

    public function handleSubscription(Request $request)
    {
        $subscription = $this->createSubscription(
            $request->plan,
            $request->user()->name,
            $request->user()->email,
        );

        $subscriptionLinks = collect($subscription->links);

        $approve = $subscriptionLinks->where('rel', 'approve')->first();

        session()->put('subscriptionId', $subscription->id);

        return redirect($approve->href);
    }

    public function validateSubscription(Request $request)
    {
        if (session()->has('subscriptionId')) {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }

    public function createOrder($value, $currency)
    {
        return $this->makeRequest(
            'POST',
            '/gwprocess/v4/api.php',
            [],
            [
                'store_id' => 'testc60c3694adfe26',
                'store_passwd' => 'testc60c3694adfe26@ssl',
                'total_amount' => $value,
                'currency' => strtoupper($currency),
                'tran_id' => 'SSLCZ_TEST_'.uniqid(),
                'success_url' => route('approval.post'),
                'fail_url' => route('cancelled.post'),
                'cancel_url' => route('cancelled.post'),
                'product_category' => 'clothing',
                'emi_option' => 1,
                'cus_name' => 'Tanvir Sakib',
                'cus_phone' => '017111111111',
                'cus_email' => 'tsakib360@gmail.com',
                'cus_add1' => 'Khulna',
                'cus_city' => 'Khulna',
                'cus_postcode' => 'GPO9000',
                'cus_country' => 'Bangladesh',
                'shipping_method' => 'NO',
                'num_of_item' => '1',
                'product_name' => 'sfjhbvsdf',
                'product_category' => 'Electronics',
                'product_profile' => 'general',




            ],
            [],
            $isJsonRequest =  false
        );
    }

    public function validationAPI($val_id)
    {
        return $this->makeRequest(
            'GET',
            "/validator/api/validationserverAPI.php",
            [
                'val_id' => $val_id,
                'store_id' => 'testc60c3694adfe26',
                'store_passwd' => 'testc60c3694adfe26@ssl',
            ],
            [],
            [],
        );
    }

    public function sslCapture($session_key)
    {
        return $this->makeRequest(
            'POST',
            "/validator/api/merchantTransIDvalidationAPI.php",
            [
                'sessionkey' => $session_key,
                'store_id' => 'testc60c3694adfe26',
                'store_passwd' => 'testc60c3694adfe26@ssl',
            ],
            [],
            [],
            $isJsonRequest =  true
        );
    }

    public function capturePayment($approvalId)
    {
        return $this->makeRequest(
            'POST',
            "/v2/checkout/orders/{$approvalId}/capture",
            [],
            [],
            [
                'Content-Type' => 'application/json'
            ],

        );
    }

    public function createSubscription($planSlug, $name, $email)
    {
        return $this->makeRequest(
            'POST',
            '/v1/billing/subscriptions',
            [],
            [
                'plan_id' => $this->plans[$planSlug],
                'subscriber' => [
                    'name' => [
                        'given_name' => $name,
                    ],
                    'email_address' => $email
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'SUBSCRIBE_NOW',
                    'return_url' => route('subscribe.approval', ['plan' => $planSlug]),
                    'cancel_url' => route('subscribe.cancelled'),
                ]
            ],
            [],
            $isJsonRequest = true,
        );
    }

    public function resolveFactor($currency)
    {
        $zeroDecimalCurrencies = ['JPY'];

        if(in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return 1;
        }
        return 100;
    }

}
