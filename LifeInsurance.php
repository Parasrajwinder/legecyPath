<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class LifeInsurance extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'life_insurances';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'policy_number',
        'company_name',
    ];
}






