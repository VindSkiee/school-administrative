<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RecordsActivity;

class Principal extends Model
{
    use RecordsActivity;
    use HasFactory;

    protected $primaryKey = 'user_id'; // Kunci PK ke user_id

    public $incrementing = false;     // Matikan auto-increment

    protected $fillable = [
        'user_id',
        'nip',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
