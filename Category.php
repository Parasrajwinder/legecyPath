<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Category extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'categories';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'type',
        'icon',
        'type2',
        'icon2',
        'title',
        'status',
    ];
    
    public function bankaccount() {
        return $this->hasMany( BankAccount::class, 'category_id' );
    }
    
    
}
