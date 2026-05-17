<?php

namespace App\Services;

use App\Models\AcademicYear;
use Exception;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    public function setActive(AcademicYear $academicYear): AcademicYear
    {
        // Jika sudah aktif, tidak perlu melakukan apa-apa
        if ($academicYear->is_active) {
            return $academicYear;
        }

        DB::beginTransaction();

        try {
            // 1. Nonaktifkan SEMUA tahun ajaran
            AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);

            // 2. Aktifkan HANYA tahun ajaran yang dipilih
            $academicYear->is_active = true;
            $academicYear->save();

            DB::commit();

            return $academicYear;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
