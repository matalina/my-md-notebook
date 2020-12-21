<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = ['provider', 'provider_id', 'user_id', 'avatar'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function providers()
    {
        return $this->hasMany(Provider::class, 'user_id', 'id');
    }
}
