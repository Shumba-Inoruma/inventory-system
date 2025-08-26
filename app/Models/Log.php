<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Log extends Model
{
    protected $connection = 'mongodb'; // MongoDB connection
    protected $collection = 'logs'; // MongoDB collection

    protected $fillable = [
        'user_id',
        'action',
        'details',
        'type',
        'metadata'
    ];

    public $timestamps = true;
}
