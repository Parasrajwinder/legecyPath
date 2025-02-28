<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class RealEstate extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'real_estates';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'property_type',
        'address',
        'legal_ownership',
    ];
}
