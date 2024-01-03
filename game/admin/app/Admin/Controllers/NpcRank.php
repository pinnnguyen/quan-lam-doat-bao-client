<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NpcRank extends Model
{
    use HasFactory;

    protected $table = 'npc_ranks';

    protected $primaryKey = 'id';

    public $timestamps = false;


    public function things()
    {
        return $this->belongsToMany(Thing::class, 'npc_rank_things', 'npc_rank_id', 'thing_id');
    }
}
