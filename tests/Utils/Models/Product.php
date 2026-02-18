<?php

declare(strict_types=1);

namespace Tests\Utils\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $product_id
 * @property string $external_sku
 * @property string $product_name
 * @property float $unit_price
 * @property int $stock_quantity
 */
final class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'unit_price' => 'float',
        'stock_quantity' => 'integer',
    ];
}
