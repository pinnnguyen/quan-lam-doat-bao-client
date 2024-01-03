<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Xinfa extends Model
{
    use HasFactory;

    protected $table = 'xinfas';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
