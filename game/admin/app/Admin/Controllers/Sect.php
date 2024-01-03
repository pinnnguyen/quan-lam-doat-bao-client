<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sect extends Model
{
    use HasFactory;

    protected $table = 'sects';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
