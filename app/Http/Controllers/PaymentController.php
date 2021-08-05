<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaypalService;
use Illuminate\Support\Facades\Auth;
use App\Resolvers\PaymentPlatformResolver;

class PaymentController extends Controller
{
    protected $paymentPlatformResolver;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth')->except('cancelled');

        $this->paymentPlatformResolver = $paymentPlatformResolver;

        
    }

    public function pay(Request $request)
    {
        $rules = [
            'value' => ['required', 'numeric', 'min:5'],
            'currency' => ['required', 'exists:currencies,iso'],
            'payment_platform' => ['required', 'exists:payment_platforms,id'],
        ];

        $request->validate($rules);

        $paymentPlatform = $this->paymentPlatformResolver
                                ->resolveService($request->payment_platform);
        session()->put('paymentPlatformId', $request->payment_platform);



        return $paymentPlatform->handlePayment($request);
    }

    public function approval()
    {
        if(session()->has('paymentPlatformId')) {

            $paymentPlatform = $this->paymentPlatformResolver
                                    ->resolveService(session()->get('paymentPlatformId'));

            return $paymentPlatform->handleApproval();
        }

        return redirect()->route('home')->withErrors('We can not retrieve payment platform. Please,  try again!');
    }

    public function cancelled(Request $request)
    {
        if ($request->isMethod('POST')) {
            
            return redirect()->route('home')->withErrors('Payment cancelled!');
            // return Response('Transaction is cancelled');
        }
        return redirect()->route('home')->withErrors('Payment cancelled!');
    }
}
