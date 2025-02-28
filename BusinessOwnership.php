<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BusinessOwnership extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'business_ownerships';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'business_name',
        'business_type',
        'ownership_percentage',
        'legal_ownership', 
    ];
}