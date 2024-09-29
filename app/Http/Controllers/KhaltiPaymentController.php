<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KhaltiPaymentController extends Controller
{
    // Step 1: Initiate the payment
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer',  // Amount in paisa
            'purchase_order_id' => 'required',
            'purchase_order_name' => 'required',
            'return_url' => 'required|url',
        ]);

        // Check if this order already has a completed payment
        $existingPayment = Payment::where('purchase_order_id', $request->purchase_order_id)
                                    ->where('status', 'completed')
                                    ->first();
        if ($existingPayment) {
            return response()->json(['error' => 'This order is already paid.'], 400);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key ' . env('KHALTI_SECRET_KEY')
        ])->post('https://a.khalti.com/api/v2/epayment/initiate/', [
            'return_url' => $request->return_url,
            'website_url' => env('APP_URL'),
            'amount' => $request->amount,
            'purchase_order_id' => $request->purchase_order_id,
            'purchase_order_name' => $request->purchase_order_name,
            'customer_info' => $request->customer_info ?? [],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            Payment::create([
                'pidx'=>$data['pidx'],
                'purchase_order_id' => $request->purchase_order_id,
                'amount' => $request->amount,
                'mobile' => $request->customer_info['phone'],
            ]);
            return response()->json(['payment_url' => $data['payment_url'], 'pidx' => $data['pidx']]);
        }else {
            Log::error('Khalti Payment Verification Failed', $response->json());
            return response()->json(['error' => $response->json()], 400);
        }
    }

    // Step 2: Handle the callback after payment
    public function paymentCallback(Request $request)
    {
        $payment = Payment::where('pidx', $request->pidx)->first();
        
        if (!$payment) {
            return response()->json(['error' => 'Invalid Pidx'], 404);
        }

        // Verify payment status
        $response = Http::withHeaders([
            'Authorization' => 'Key ' . env('KHALTI_SECRET_KEY')
        ])->post('https://a.khalti.com/api/v2/epayment/lookup/', [
            'pidx' => $request->pidx,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Update payment status in DB
            $payment->update([
                'transaction_id' => $data['transaction_id'],
                'status' => $data['status'],
                'amount' => $data['total_amount'],
            ]);

            return response()->json(['message' => 'Payment Verified', 'status' => $data['status']]);
        } else {
            Log::error('Khalti Payment Verification Failed', $response->json());
            return response()->json(['error' => 'Failed to verify payment'], 400);
        }
    }
}
