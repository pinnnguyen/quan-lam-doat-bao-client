<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Npc extends Model
{
    use HasFactory;

    protected $table = 'npcs';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function things(): BelongsToMany
    {
        return $this->belongsToMany(Thing::class, 'npc_things', 'npc_id', 'thing_id');
    }
}
