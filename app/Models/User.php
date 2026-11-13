<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    public const ROLES = [
        'admin' => 'Administrator',
        'manager' => 'Kepala gudang / QA',
        'inbound' => 'Admin inbound',
        'outbound' => 'Admin outbound',
        'operator' => 'Operator gudang (handheld)',
    ];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'bool'];
    }

    public function isManager(): bool { return in_array($this->role, ['admin', 'manager']); }

    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function roleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}
