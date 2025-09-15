<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\pivot_email_customer;
class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',
        'job_title',
        'group',
        'detailmore',
        'status'
    ];

 public function emails()
{
    return $this->belongsToMany(
        EmailData::class,
        'pivot_email_customers',  
        'customer_id',            
        'emaildata_id'              
    )
    ->using(pivot_email_customer::class)
    ->withPivot('status')
    ->withTimestamps();
}
}