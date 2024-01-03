<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XinfaAttackTrick extends Model
{
    use HasFactory;

    protected $table = 'xinfa_attack_tricks';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
