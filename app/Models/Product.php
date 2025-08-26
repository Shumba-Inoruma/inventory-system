<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'price'];

    public function stocks()

{

    return $this->hasMany(Stock::class);
}
public function currentStock()
    {
        return $this->stocks->sum(function($stock) {
            return $stock->type === 'in' ? $stock->quantity : -$stock->quantity;
        });
    }
}
