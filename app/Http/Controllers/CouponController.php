<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\User;
use App\Servcices\Billing\BillingGateway;
use Illuminate\Http\Request;

class CouponController extends Controller
{


    public function index()
    {

        return 'Coupon index';
    }


    public function store(BillingGateway $gateway, Request $request)
    {

//        Coupon::create(
//            [
//                'code' => $request->input('code'),
//                'percentage_discount' => $request->input('percentage_discount'),
//                'description' => $request->input('description'),
//            ]
//        );

        $gateway->createCoupon($request);
    }
}
