<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContentItem extends Model { protected $fillable=['section_id','title','subtitle','body','label','url','settings','sort_order','is_visible']; protected $casts=['settings'=>'array','is_visible'=>'boolean']; public function section(){return $this->belongsTo(Section::class);} public function media(){return $this->morphMany(Media::class,'mediable')->orderBy('sort_order');} }
