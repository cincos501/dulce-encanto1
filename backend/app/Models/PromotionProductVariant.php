<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProductVariant extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotion_product_variant';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'promotion_id',
    ];
}
