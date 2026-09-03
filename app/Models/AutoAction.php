<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_set_id', 'ad_variant_id', 'action', 'reason',
        'payload', 'result', 'message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }

    public function adVariant(): BelongsTo
    {
        return $this->belongsTo(AdVariant::class);
    }

    /**
     * Peraturan mutlak #4: rekod dulu, baru panggil Meta.
     */
    public static function start(string $action, string $reason, ?AdSet $set = null, ?AdVariant $variant = null, array $payload = []): self
    {
        return static::create([
            'ad_set_id' => $set?->id ?? $variant?->ad_set_id,
            'ad_variant_id' => $variant?->id,
            'action' => $action,
            'reason' => $reason,
            'payload' => $payload,
            'result' => 'pending',
        ]);
    }

    public function succeed(?string $message = null): self
    {
        $this->update(['result' => 'ok', 'message' => $message]);

        return $this;
    }

    public function fail(string $message): self
    {
        $this->update(['result' => 'failed', 'message' => $message]);

        return $this;
    }
}
