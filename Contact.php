<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Contact extends Model
{
     use HasApiTokens, HasFactory, Notifiable;
     
     protected $table = 'contacts';
     
     protected $primaryKey = 'id';
     
       protected $fillable = [
        'user_id',
        'full_name',
        'profile_img',
        'country_code',
        'contact_number',
        'email',
        'relation',
        'is_trusted'
        
    ];
    
     public function user() {
        return $this->belongsTo( User::class, 'email','email' );
    }
}
