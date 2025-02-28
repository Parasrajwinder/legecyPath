<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ContactModule extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'contact_modules';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'contact_id',
        'module_type',
        'status'
        
    ];
}