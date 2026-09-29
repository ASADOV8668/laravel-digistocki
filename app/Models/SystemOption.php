<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemOption extends Model
{
    protected $table = 'options';

    protected $fillable = ['key', 'value', 'type'];
}
