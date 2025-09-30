<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $all)
 * @method static findOrFail(int $id)
 * @method static where(string $string, int $int)
 */
class Theme extends Model
{
    use HasFactory;


    protected $guarded = [];
    protected $hidden = [];
}
