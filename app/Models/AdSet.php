<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'problem', 'offer', 'phone',
        'region_key', 'region_name', 'daily_budget_sen', 'status',
    ];

    protected function casts(): array
    {
        return [
            'daily_budget_sen' => 'integer',
        ];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AdVariant::class)->orderBy('position');
    }

    public function autoActions(): HasMany
    {
        return $this->hasMany(AutoAction::class)->latest('id');
    }

    /** Jumlah bajet sehari untuk keseluruhan set — satu gambar satu campaign. */
    public function totalDailyBudgetSen(): int
    {
        return $this->daily_budget_sen * $this->variants()->count();
    }

    public function regionLabel(): string
    {
        return $this->region_name ?: 'Seluruh Malaysia';
    }
}
