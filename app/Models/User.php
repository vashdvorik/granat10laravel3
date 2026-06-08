<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordCustom;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\HasApiTokens;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $guarded = [
        'id',
        'balance',
        'role_id',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'collection',
    ];

    public $additional_attributes = ['locale'];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordCustom($token));
    }

    public function routeNotificationForMail(): string
    {
        if (str_contains($this->email, "\r") || str_contains($this->email, "\n")) {
            throw new InvalidArgumentException('Email address contains prohibited line breaks.');
        }

        return $this->email;
    }

    public function seller()
    {
        return $this->hasOne(Seller::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function rolesAll(): Collection
    {
        $this->loadMissing(['role', 'roles']);

        return collect([$this->role])
            ->filter()
            ->merge($this->roles)
            ->unique('id')
            ->values();
    }

    public function roles_all(): Collection
    {
        return $this->rolesAll();
    }

    public function hasRole($name): bool
    {
        $roleNames = is_array($name) ? $name : [$name];

        return $this->rolesAll()
            ->pluck('name')
            ->intersect($roleNames)
            ->isNotEmpty();
    }

    public function setRole($name): static
    {
        $role = Role::query()->where('name', $name)->first();

        if ($role) {
            $this->role()->associate($role);
            $this->save();
        }

        return $this;
    }

    public function hasPermission($name): bool
    {
        $this->loadMissing(['role.permissions', 'roles.permissions']);

        return $this->rolesAll()
            ->flatMap->permissions
            ->contains('key', $name);
    }

    public function hasPermissionOrFail($name): bool
    {
        if (! $this->hasPermission($name)) {
            throw new UnauthorizedHttpException(null);
        }

        return true;
    }

    public function hasPermissionOrAbort($name, $statusCode = 403): bool
    {
        abort_unless($this->hasPermission($name), $statusCode);

        return true;
    }

    public function getAvatarAttribute(?string $value): string
    {
        return $value ?? 'users/default.png';
    }

    public function getLocaleAttribute(): mixed
    {
        return collect($this->settings)->get('locale');
    }

    public function setLocaleAttribute(mixed $value): void
    {
        $this->settings = collect($this->settings)->put('locale', $value);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'control'
            && Gate::forUser($this)->allows('access-admin');
    }
}
