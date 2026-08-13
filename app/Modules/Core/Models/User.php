<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Enums\UserType;
use Database\Factories\Core\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserType $user_type
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property-read Employee|null $employee
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function isInternal(): bool
    {
        return $this->user_type === UserType::Internal;
    }

    public function isClient(): bool
    {
        return $this->user_type === UserType::Client;
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeInternal(Builder $query): void
    {
        $query->where('user_type', UserType::Internal);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeClients(Builder $query): void
    {
        $query->where('user_type', UserType::Client);
    }
}
