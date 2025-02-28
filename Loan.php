<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Loan extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'loans';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'category_id',
        'user_id',
        'loan_type',
        'lender_name',
        'loan_number',
        
    ];
}
