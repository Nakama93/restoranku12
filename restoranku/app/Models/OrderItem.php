<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id','item_id','quantity','price','tax','total_price','created_at','update_at'];
    protected $dates = ['deleted_at'];


}
