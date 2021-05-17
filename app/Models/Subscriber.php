<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_id',
        'first_name',
        'last_name',
        'photo',
        'status',
        'created_at',
        'updated_at'
    ];

}
