<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Redemption extends Model
{
    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'redemptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['outlet_id', 'year', 'month','redemption_datetime'];
}
