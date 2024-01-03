<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentKind extends Model
{
    use HasFactory;

    protected $table = 'equipment_kinds';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
