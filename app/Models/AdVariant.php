<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_set_id', 'position', 'image_path', 'caption',
        'meta_image_hash', 'meta_campaign_id', 'meta_adset_id',
        'meta_creative_id', 'meta_ad_id', 'status', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(MetricDaily::class);
    }

    /**
     * Peraturan mutlak #2: app ini hanya menyentuh campaign yang ia sendiri buat.
     * Campaign lain dalam akaun langsung tiada dalam jadual ad_variants, jadi
     * pemeriksaan ini memadai — dan setiap pause/run mesti melaluinya.
     */
    public function isOwnedByApp(): bool
    {
        return filled($this->meta_campaign_id) && $this->exists;
    }

    public function isLive(): bool
    {
        return $this->status === 'active';
    }

    public function spendSen(): int
    {
        return (int) $this->metrics()->sum('spend_sen');
    }

    public function leads(): int
    {
        return (int) $this->metrics()->sum('leads');
    }

    public function costPerLeadSen(): ?int
    {
        $leads = $this->leads();

        return $leads > 0 ? intdiv($this->spendSen(), $leads) : null;
    }
}
