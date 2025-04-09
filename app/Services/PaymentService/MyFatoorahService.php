<?php

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use App\Models\Shop;
use App\Models\Subscription;
use Exception;
use Illuminate\Database\Eloquent\Model;

class MyFatoorahService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

	/**
	 * @param array $data
	 * @return PaymentProcess|Model
	 * @throws Exception
	 */
    public function orderProcessTransaction(array $data): Model|PaymentProcess
    {
		$payment = Payment::where('tag', Payment::TAG_STRIPE)->first();

		$paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
		$payload        = $paymentPayload?->payload;
		[$key, $before] = $this->getPayload($data, $payload, $payment?->id);

		$modelId 		= data_get($before, 'model_id');

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_id'   => $modelId,
            'model_type' => data_get($before, 'model_type')
        ], [
            'id' => $data['invoice_id'],
            'data' => [
				'cart'		 => $data,
				'payment_id' => $payment->id,
			]
        ]);
    }

	/**
	 * @param array $data
	 * @param Shop|null $shop
	 * @return Model|array|PaymentProcess
	 */
    public function subscriptionProcessTransaction(array $data, ?Shop $shop): Model|array|PaymentProcess
    {
        $payment = Payment::where('tag', Payment::TAG_STRIPE)->first();

        $subscription = Subscription::find(data_get($data, 'subscription_id'));

		$totalPrice = ceil($subscription->price * 100);

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
			'model_id'   => $subscription->id,
			'model_type' => get_class($subscription)
        ], [
            'id' => $data['invoice_id'],
            'data' => [
                'price'           => $totalPrice,
                'shop_id'         => $shop?->id,
				'subscription_id' => $subscription->id,
				'payment_id' 	  => $payment->id,
			]
        ]);
    }

}
