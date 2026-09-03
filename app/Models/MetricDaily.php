<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricDaily extends Model
{
    use HasFactory;

    protected $table = 'metrics_daily';

    protected $fillable = [
        'ad_variant_id', 'date', 'spend_sen', 'impressions', 'clicks', 'leads', 'ctr',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'spend_sen' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'leads' => 'integer',
            'ctr' => 'float',
        ];
    }

    public function adVariant(): BelongsTo
    {
        return $this->belongsTo(AdVariant::class);
    }
}
