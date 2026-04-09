<?php
namespace App\Servcices\Billing;
use App\Models\Coupon;

class BillingGateway
{
    public function createCoupon($request)
    {
//        dd("called");
        Coupon::create(
            [
                'code' => $request->input('code'),
                'percentage_discount' => $request->input('percentage_discount'),
                'description' => $request->input('description'),
            ]
        );
    }
}
