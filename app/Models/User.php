<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name','mobile','password','role','is_verified','is_blocked'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','is_verified'=>'boolean','is_blocked'=>'boolean']; }
    public function documents(): HasMany { return $this->hasMany(TypingDocument::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
}
