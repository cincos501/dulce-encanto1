<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'unit', 'stock', 'minimum_stock', 'average_cost', 'is_active'])]
class Supply extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
            'average_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the suppliers that provide this supply.
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_supplies')
            ->withPivot('purchase_price')
            ->withTimestamps();
    }

    /**
     * Get the recipes using this supply.
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
