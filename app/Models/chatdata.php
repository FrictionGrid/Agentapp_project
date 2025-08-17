<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class chatdata extends Model
{
    protected $table = 'chat_states';
    protected $fillable = ['user_id', 'state', 'data'];
    public $timestamps = true;
}
