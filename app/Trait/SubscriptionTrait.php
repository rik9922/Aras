<?php

namespace App\Trait;

use App\Models\Subscriptions;
use Google\Client;
use Google\Service\AndroidPublisher;
use Illuminate\Support\Facades\Log;
use ReceiptValidator\iTunes\Validator as iTunesValidator;


trait SubscriptionTrait
{

    public function checkAndroid($receipt)
    {
        if (isset($receipt)) {
            $keyFilePath = app_path('Services/subscription_account.json');
            $client = new Client;
            $client->setAuthConfig($keyFilePath);
            $client->addScope(AndroidPublisher::ANDROIDPUBLISHER);

            $packageName = 'Enter Package Name';
            $subscriptionId = $receipt->productId;
            $token = $receipt->purchaseToken;

            $service = new AndroidPublisher($client);

            try {
                $subscriptionDetails = $service->purchases_subscriptions->get($packageName, $subscriptionId, $token);
                $raw_data = [];

                if (isset($subscriptionDetails->expiryTimeMillis)) {
                    $expiryTimeMillis = $subscriptionDetails->expiryTimeMillis / 1000;
                    $currentTime = time();
                    $raw_data['purchaseToken'] = $token;
                    $raw_data['subscription_status'] = $expiryTimeMillis >= $currentTime ? 'yes' : 'no';
                    $raw_data['expires_date_pst'] = date('Y-m-d h:i A', $expiryTimeMillis);
                    $raw_data['subscription_type'] = $subscriptionId;

                    if (isset($subscriptionDetails->priceAmountMicros) && isset($subscriptionDetails->priceCurrencyCode)) {
                        // Convert the price from micros to standard format
                        $price = $subscriptionDetails->priceAmountMicros / 1000000; 
                        $currency = $subscriptionDetails->priceCurrencyCode;
                        $raw_data['subscription_price'] = $price.' '.$currency;
                    } else {
                        $raw_data['subscription_price'] = 'N/A';
                    }
                    
                    return [
                        'success' => true,
                        'data' => $raw_data,
                    ];

                } else {
                    return [
                        'success' => false,
                        'message' => 'Android User not subscribed',
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Subscription validation failed', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to validate subscription',
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Android User not subscribed',
            ];
        }
    }

    public function checkIos($receipt)
    {
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);
        $receiptBase64Data = $receipt;
        $data = [];
        try {
            $response = $validator->setReceiptData($receiptBase64Data)->validate();
            $sharedSecret = env('SUBSCRIPTION_API_IOS');
            $response = $validator->setSharedSecret($sharedSecret)->setReceiptData($receiptBase64Data)->validate();

            if ($response->isValid()) {
                $raw_data = $response->getRawData();
                $receipt = current($raw_data['latest_receipt_info']);


                $data['original_transaction_id'] = $receipt['original_transaction_id'];
                $data['subscription_status'] = '';
                $data['expires_date_pst'] = date('Y-m-d h:i A', ($receipt['expires_date_ms'] / 1000));
                $data['subscription_type'] = $receipt['product_id'];
                $data['is_expired'] = strtotime($data['expires_date_pst']) >= strtotime(date('Y-m-d h:i A')) ? 'no' : 'yes';
                $data['platform'] = 'ios';
                $data['subscription_status'] = strtotime($data['expires_date_pst']) >= strtotime(date('Y-m-d h:i A')) ? 'yes' : 'no';

                 return [
                        'success' => true,
                        'data' => $data,
                    ];
            } else {
                 return [
                    'success' => false,
                    'message' => 'Invalid Receipt Or Incorrect Receipt',
                ];
            }
        } catch (\Exception $e) {
             // return $this->errorResponse($e->getMessage(), 500);
            
            Log::error('Subscription validation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to validate subscription',
            ];
        }
    }
}
