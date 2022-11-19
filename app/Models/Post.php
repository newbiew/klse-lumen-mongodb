<?php namespace App\Models;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model 
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}