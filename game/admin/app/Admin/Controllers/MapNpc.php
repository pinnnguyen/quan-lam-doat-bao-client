<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapNpc extends Model
{
    use HasFactory;

    protected $table = 'map_npcs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function map()
    {
        return $this->hasOne(Map::class,'id','map_id');
    }
    public function npc()
    {
        return $this->hasOne(Npc::class,'id','npc_id');
    }
}
