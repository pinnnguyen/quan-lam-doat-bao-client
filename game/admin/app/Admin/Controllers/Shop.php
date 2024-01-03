<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $table = 'shops';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function things()
    {
        return $this->belongsToMany(Thing::class, 'shop_things', 'shop_id', 'thing_id');
    }
}
