<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class GenerateCoupons extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function only_admins_can_generate_coupon()
    {
        $response = $this->post('/admin/coupons')
            ->assertRedirect('/');
    }

    public function test_it_records_a_coupon_in_the_database()
    {
        $this->signIn();

        $this->post('/admin/coupons',[
            'code' => 'BLACKFRIDAY',
            'description' => 'Foo des',
            'percentage_discount' => 50,
        ]);

        $this->assertDatabaseHas('coupons',[
            'code' => 'BLACKFRIDAY',
            'description' => 'Foo des',
            'percentage_discount' => 50,
        ]);


    }

    public function test_it_generates_the_coupon_on_the_billing_service()
    {
        $this->signIn();
        $gateway = Mockery::mock(\App\Servcices\Billing\BillingGateway::class);
        $gateway->shouldReceive('createCoupon')->once();

        app()->instance(\App\Servcices\Billing\BillingGateway::class, $gateway);
        $this->post('/admin/coupons',[
            'code' => 'BLACKFRIDAY',
            'description' => 'Foo des',
            'percentage_discount' => 50,
        ]);

        $this->assertTrue(true, true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


}
