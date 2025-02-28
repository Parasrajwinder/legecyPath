<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BrokerageAccount extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'brokerage_accounts';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'account_type',
        'account_number',
        'brokerage_firm_name',
        'legal_ownership,', 
    ];
}
