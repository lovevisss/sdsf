<?php

namespace App\Providers;

use App\Models\ConversationAppointment;
use App\Models\ConversationRecord;
use App\Policies\ConversationAppointmentPolicy;
use App\Policies\ConversationRecordPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        ConversationRecord::class => ConversationRecordPolicy::class,
        ConversationAppointment::class => ConversationAppointmentPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
