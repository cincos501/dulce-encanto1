<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['business_name', 'phone', 'email', 'address', 'is_active'])]
class Supplier extends Model
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the supplies provided by this supplier.
     */
    public function supplies(): BelongsToMany
    {
        return $this->belongsToMany(Supply::class, 'supplier_supplies')
            ->withPivot('purchase_price')
            ->withTimestamps();
    }
}
