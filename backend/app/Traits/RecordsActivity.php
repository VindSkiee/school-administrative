<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/** @mixin Model */
trait RecordsActivity
{
    public static function bootRecordsActivity(): void
    {
        // Hindari logging saat menjalankan seeder atau testing console
        if (app()->runningInConsole()) {
            return;
        }

        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                $model->recordActivity($event);
            });
        }
    }

    protected function recordActivity(string $event): void
    {
        $oldValues = $event === 'updated' ? $this->getOriginal() : null;
        $newValues = $event !== 'deleted' ? $this->getAttributes() : null;

        // Jangan log jika tidak ada perubahan data saat update
        if ($event === 'updated' && empty($this->getChanges())) {
            return;
        }

        ActivityLog::create([
            'user_id' => Auth::id(), // ID user yang sedang login
            'action' => $event,
            'loggable_type' => get_class($this),
            'loggable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
