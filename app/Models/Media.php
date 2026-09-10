<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Media extends Model { protected $fillable=['mediable_type','mediable_id','kind','disk','path','url','alt','caption','mime_type','width','height','sort_order']; public function mediable(){return $this->morphTo();} public function getSrcAttribute(){return $this->kind==='youtube'?$this->url:asset($this->path);} }
