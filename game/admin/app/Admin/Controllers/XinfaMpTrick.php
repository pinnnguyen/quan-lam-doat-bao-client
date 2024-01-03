<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XinfaMpTrick extends Model
{
    use HasFactory;

    protected $table = 'xinfa_mp_tricks';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
