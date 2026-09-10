<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Section extends Model { protected $fillable=['page_id','type','eyebrow','title','body','settings','sort_order','is_visible']; protected $casts=['settings'=>'array','is_visible'=>'boolean']; public function page(){return $this->belongsTo(Page::class);} public function items(){return $this->hasMany(ContentItem::class)->orderBy('sort_order');} public function media(){return $this->morphMany(Media::class,'mediable')->orderBy('sort_order');} }
