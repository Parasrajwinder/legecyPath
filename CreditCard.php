<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CreditCard extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'credit_cards';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'card_type',
        'card_name',
        'bank_name',
        
    ];
}