<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BankAccount extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'bank_accounts';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'account_number',
        'bank_name',
        'account_type',
        'legal_ownership', 
        
    ];
}