<?php
namespace App\Models;

use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use RecordsActivity;

    protected $fillable = ['code', 'name'];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}