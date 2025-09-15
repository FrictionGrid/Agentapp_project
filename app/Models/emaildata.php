<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\pivot_email_customer;

class  EmailData extends Model
{
    protected $table = 'emaildatas';
    protected $fillable = [
        
        'subject',
        'body',
    ];

    public function contacts()
{
    return $this->belongsToMany(
        Customer::class,
        'pivot_email_customers',  
        'emaildata_id',           
        'customer_id'            
    )
    ->using(pivot_email_customer::class)
    ->withPivot('status')
    ->withTimestamps();
}
}

