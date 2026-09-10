<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    protected $fillable = ['subject', 'preheader', 'body', 'image_path', 'action_label', 'action_url', 'status', 'recipient_count', 'sent_at', 'created_by'];
    protected $casts = ['sent_at' => 'datetime'];
    public function deliveries() { return $this->hasMany(NewsletterDelivery::class, 'campaign_id'); }
}
