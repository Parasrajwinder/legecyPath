<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ShareDocuments extends Model
{
   use HasApiTokens, HasFactory, Notifiable;
    
        protected $table = 'share_documents';
  
        protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id',
        'contact_id',
        'category_id',
        'document_id',
        'status'
    ];
    
     public function contact() {
        return $this->belongsTo( Contact::class, 'contact_id' );
    }
    public function category() {
        return $this->belongsTo( Category::class, 'category_id' );
    }
    
}
