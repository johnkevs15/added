<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use Srmklive\PayPal\Services\PayPal;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Order;
class PaypalController extends Controller
{
    //
    public function create(Request $request)
    {
        $data =json_decode($request->getContent(),true);
        // $provider = new PayPalClient;
        $provider = \PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->getAccessToken($token);

        $price = Order::getProductPrice($data['value']);
        $description = Order::getProductDescription($data['value']);

        $order = $provider->createOrder([
            "intent" => "CAPTURE",
            "purchase_units"=>[
                [
                    "amount"=>[
                        "currency_code" =>"USD",
                        "value"=> $price
                    ],
                    "description"=>$description
                ]

            ]
        ]);
        Order::create([
            'price'=>$price,
            'description'=>$description,
            'status'=>$order['status'],
            'reference_number'=>$order['id']
        ]);

        return response()->json($order); 

    }
   

    public function capture(Request $request)
    {
        $data =json_decode($request->getContent(),true);
        $orderId = $data['orderId'];
        // $provider = new PayPalClient;
        $provider = \PayPal::setProvider();
        $provider->setApiCredentials(config('paypal'));
        $token = $provider->getAccessToken();
        $provider->getAccessToken($token);

        $result = $provider->capturePaymentOrder($orderId);


        if($result['status']=='COMPLETED'){
            DB::table('orders')
            ->where('reference_number',$result['id'])
            ->update(['status'=>'COMPLETED','updated_at'=>\Carbon\Carbon::now()]);
        }
        return response()->json($result);
    }
}
