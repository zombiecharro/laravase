<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    //
    protected $fillable = [
        "reader_id",
        "client_id",
        "start_time",
        "end_time",
        "status",
        "notes",
        "is_exception",
    ];
}
