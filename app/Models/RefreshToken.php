<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'token_hash',
        'expires_at',
        'revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($refreshToken) {
            $refreshToken->token = Str::uuid();
            $refreshToken->token_hash = Hash::make($refreshToken->token);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked()
    {
        return $this->revoked;
    }

    public function isValid()
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    public function revoke()
    {
        $this->update(['revoked' => true]);
    }

    public static function createToken(User $user, $expiresInDays = 30)
    {
        return static::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }

    public static function findByToken($token)
    {
        return static::where('token', $token)->first();
    }
}
