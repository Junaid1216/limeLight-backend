<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seo extends Model
{
    use HasFactory;

     protected $fillable = [
        'page', 'title', 'og_title', 'description', 'og_description', 'keywords'
    ];
}
