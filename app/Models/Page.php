<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Page extends Model { protected $fillable=['name','slug','seo_title','seo_description','seo_keywords','canonical_url','og_title','og_description','seo_image','noindex','is_published','show_on_home']; protected $casts=['noindex'=>'boolean','is_published'=>'boolean','show_on_home'=>'boolean']; public function sections(){return $this->hasMany(Section::class)->orderBy('sort_order');} }
