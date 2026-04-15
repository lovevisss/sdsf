<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'department_id',
        'student_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the department this user belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all conversation appointments initiated by this student.
     */
    public function initiatedAppointments()
    {
        return $this->hasMany(ConversationAppointment::class, 'student_id');
    }

    /**
     * Get all conversation appointments for this advisor.
     */
    public function advisorAppointments()
    {
        return $this->hasMany(ConversationAppointment::class, 'advisor_id');
    }

    /**
     * Get all conversation records conducted by this advisor.
     */
    public function conductedConversations()
    {
        return $this->hasMany(ConversationRecord::class, 'advisor_id');
    }

    /**
     * Get all conversation records this student participated in.
     */
    public function conversationRecords()
    {
        return $this->hasMany(ConversationRecord::class, 'student_id');
    }

    /**
     * Get all posts created by the user.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function ethicsProfile(): HasOne
    {
        return $this->hasOne(EthicsProfile::class);
    }

    public function reportedEthicsCases(): HasMany
    {
        return $this->hasMany(EthicsCase::class, 'reporter_id');
    }

    public function assignedEthicsWarnings(): HasMany
    {
        return $this->hasMany(EthicsWarning::class, 'assignee_id');
    }

    public function politicalViolations(): HasMany
    {
        return $this->hasMany(EthicsPoliticalViolation::class, 'violator_user_id');
    }

    public function recordedPoliticalViolations(): HasMany
    {
        return $this->hasMany(EthicsPoliticalViolation::class, 'recorder_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }
}
