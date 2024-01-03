<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XinfaHpTrick extends Model
{
    use HasFactory;

    protected $table = 'xinfa_hp_tricks';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
