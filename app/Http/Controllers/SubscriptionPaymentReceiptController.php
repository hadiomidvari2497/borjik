<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

class SubscriptionPaymentReceiptController extends Controller
{
    public function show(Request $request, int $payment)
    {
        $payment = SubscriptionPayment::query()
            ->with(['user', 'subscription.plan'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'paid')
            ->findOrFail($payment);

        return view('subscription.payment-receipt', [
            'payment' => $payment,
            'invoiceNumber' => 'SH-'.($payment->paid_at?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
            'gatewayName' => (string) data_get($payment->raw_response, 'gateway_name', $payment->gateway),
        ]);
    }
}
