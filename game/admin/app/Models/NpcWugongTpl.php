<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpcWugongTpl extends Model
{
    use HasFactory;

    protected $table = 'npc_wugong_tpls';

    protected $primaryKey = 'id';

    public $timestamps = false;

}
