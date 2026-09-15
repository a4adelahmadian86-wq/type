<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['name','mobile','email','password','role','is_verified','is_blocked','avatar_path','bio','notification_preferences','privacy_preferences','last_login_at'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','is_verified'=>'boolean','is_blocked'=>'boolean','notification_preferences'=>'array','privacy_preferences'=>'array','last_login_at'=>'datetime']; }
    public function documents(): HasMany { return $this->hasMany(TypingDocument::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
    public function wallet(): HasOne { return $this->hasOne(Wallet::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function freeCredits(): HasMany { return $this->hasMany(FreeCredit::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
}
