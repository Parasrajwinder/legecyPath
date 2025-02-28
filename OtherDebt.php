<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OtherDebt extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'other_debts';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'loan_type',
        'lender_name',
        'account_number',
        'other_information', 
        
    ];
}
