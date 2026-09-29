<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Registration extends Model {
    protected $fillable=['event_id','reference','first_name','last_name','email','phone','organization','notes','status','registered_at'];
    protected function casts(): array { return ['registered_at'=>'datetime']; }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
