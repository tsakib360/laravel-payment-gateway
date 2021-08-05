<?php

namespace Database\Seeders;

use App\Models\PaymentPlatform;
use Illuminate\Database\Seeder;

class PaymentPlatformsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentPlatform::create([
            'name' => 'PayPal',
            'image' => 'img/payment-platform/paypal.jpg',
            'subscriptions_enabled' => true
        ]);

        PaymentPlatform::create([
            'name' => 'Stripe',
            'image' => 'img/payment-platform/stripe.jpg',
            'subscriptions_enabled' => true
        ]);

        PaymentPlatform::create([
            'name' => 'SslCommerze',
            'image' => 'img/payment-platform/ssl.png',
            'subscriptions_enabled' => false
        ]);
    }
}
