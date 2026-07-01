<?php

namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use RecordsActivity;

    protected $fillable = ['date', 'description'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
