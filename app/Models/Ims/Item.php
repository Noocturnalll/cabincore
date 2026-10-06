<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_items';
    protected $guarded = [];
    public function category() { return $this->belongsTo(Category::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function location() { return $this->belongsTo(Location::class, 'default_location_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }
}