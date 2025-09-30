<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static updateOrCreate(array $array, array $array1)
 */
class UserToken extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['user_id', 'type', 'fcm_token'];
}
