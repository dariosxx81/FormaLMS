<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'to_email',
        'from_email',
        'subject',
        'body_html',
        'body_text',
        'status',
    ];
}
