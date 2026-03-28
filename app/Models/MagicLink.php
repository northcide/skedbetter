<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $fillable = ['email', 'token', 'expires_at', 'used_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function generate(string $email, ?int $createdBy = null): self
    {
        $expiryMinutes = (int) Setting::get('magic_link_expiry_minutes', 60);

        return static::create([
            'email' => strtolower(trim($email)),
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes($expiryMinutes),
            'created_by' => $createdBy,
        ]);
    }

    public function isValid(): bool
    {
        return ! $this->used_at && $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    public function getUrl(): string
    {
        return url("/auth/magic/{$this->token}");
    }
}
