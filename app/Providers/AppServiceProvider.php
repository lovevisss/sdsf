<?php

namespace App\Providers;

use App\Models\ConversationAppointment;
use App\Models\ConversationRecord;
use App\Models\EthicsCase;
use App\Models\EthicsAcademicViolation;
use App\Models\EthicsDisciplineViolation;
use App\Models\EthicsEducationViolation;
use App\Models\EthicsPoliticalViolation;
use App\Models\EthicsProfile;
use App\Models\EthicsProfessionalViolation;
use App\Models\EthicsWarning;
use App\Policies\ConversationAppointmentPolicy;
use App\Policies\ConversationRecordPolicy;
use App\Policies\EthicsCasePolicy;
use App\Policies\EthicsAcademicViolationPolicy;
use App\Policies\EthicsDisciplineViolationPolicy;
use App\Policies\EthicsEducationViolationPolicy;
use App\Policies\EthicsPoliticalViolationPolicy;
use App\Policies\EthicsProfilePolicy;
use App\Policies\EthicsProfessionalViolationPolicy;
use App\Policies\EthicsWarningPolicy;
use App\Services\Ethics\AttendanceAbnormalitySource;
use App\Services\Ethics\NullAttendanceAbnormalitySource;
use Illuminate\Support\Facades\Gate;
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
        $this->app->bind(AttendanceAbnormalitySource::class, NullAttendanceAbnormalitySource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ConversationRecord::class, ConversationRecordPolicy::class);
        Gate::policy(ConversationAppointment::class, ConversationAppointmentPolicy::class);
        Gate::policy(EthicsProfile::class, EthicsProfilePolicy::class);
        Gate::policy(EthicsCase::class, EthicsCasePolicy::class);
        Gate::policy(EthicsWarning::class, EthicsWarningPolicy::class);
        Gate::policy(EthicsPoliticalViolation::class, EthicsPoliticalViolationPolicy::class);
        Gate::policy(EthicsEducationViolation::class, EthicsEducationViolationPolicy::class);
        Gate::policy(EthicsAcademicViolation::class, EthicsAcademicViolationPolicy::class);
        Gate::policy(EthicsProfessionalViolation::class, EthicsProfessionalViolationPolicy::class);
        Gate::policy(EthicsDisciplineViolation::class, EthicsDisciplineViolationPolicy::class);
    }
}
