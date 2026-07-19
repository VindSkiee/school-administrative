<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rapor Semester</title>
    <style>
        @page {
            margin: 70px 90px 100px 130px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .master-header td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .header-list td {
            padding: 2px 0;
            border: none;
            font-size: 11px;
            font-weight: semibold;
            line-height: 1;
        }

        .name {
            text-transform: uppercase;
        }

        .label-col {
            width: 90px;
        }

        .colon-col {
            width: 15px;
            text-align: center;
        }

        .report-title {
            margin: 20px 0 15px 0;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000;
            padding: 4px 2px 4px 2px;
            vertical-align: top;
        }

        .table-bordered th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .center {
            text-align: center;
            items-align: center;
        }

        .left {
            text-align: left;
        }

        .capaian-text {
            font-size: 10px;
            line-height: 1.15;
            text-align: justify;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 18px 0 8px 0;
        }

        .attendance-table {
            width: 50%;
            /* atau sesuaikan */
            border-collapse: collapse;
        }

        .attendance-table td {
            border: 1px solid #000;
            padding: 4px 2px 4px 4px;
            font-size: 11px;
        }

        .attendance-label {
            width: 45%;
        }

        .attendance-value {
            width: 55%;
        }

        .note-box {
            border: 1px solid #000;
            min-height: 60px;
            padding: 6px;
            font-size: 11px;
        }

        .signature-section {
            margin-top: 30px;
        }

        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            text-align: center;
            font-size: 11px;
        }

        .signature-cell {
            vertical-align: top;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: left;
        }

        .signature-space {
            height: 70px;
            /* menggantikan <br><br><br> */
        }

        .sig-line {
            width: 140px;
            border-bottom: 1px solid #000;
            transform: translateX(-25px);
        }

        .sig-line-parent {
            width: 130px;
            border-bottom: 2px dotted #000;
        }

        .left-line {
            margin-left: 0;
        }

        .right-line {
            margin: 0 auto;
        }

        .sig-line-center {
            width: 150px;
            border-bottom: 1px solid #000;
            margin: 0 auto;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    {{-- ==================== HALAMAN 1: LAPORAN HASIL BELAJAR ==================== --}}
    <table class="master-header" width="100%">
        <tr>
            <td width="75%">
                <table class="header-list">
                    <tr>
                        <td class="label-col">Nama</td>
                        <td class="colon-col">:</td>
                        <td class="name">{{ $data['student_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">NIS/NISN</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['student_nis'] }} / {{ $data['student_nisn'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Nama Sekolah</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['school_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Alamat</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['school_address'] }}</td>
                    </tr>
                </table>
            </td>
            <td width="25%">
                <table class="header-list">
                    <tr>
                        <td class="label-col">Kelas</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['class_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Fase</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['phase'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Semester</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['semester_label'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Tahun Pelajaran</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['academic_year'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr style="border: none; border-top: 1px solid #000; margin: 10px 0 25px 0;">

    <div class="report-title">LAPORAN HASIL BELAJAR</div>

    <table class="table-bordered">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Mata Pelajaran</th>
                <th width="12%">Nilai Akhir</th>
                <th width="58%">Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['results'] as $index => $result)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $result['subject_name'] }}</td>
                    <td class="center">
                        <strong>{{ is_null($result['final_grade']) ? '-' : number_format($result['final_grade'], 0) }}</strong>
                    </td>
                    <td class="left">
                        <div class="capaian-text">
                            {{ $result['capaian_kompetensi'] ?? '-' }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center" style="padding: 20px;">Data nilai belum tersedia atau belum
                        lengkap.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ==================== HALAMAN 2: EKSTRAKURIKULER, KEHADIRAN & CATATAN ==================== --}}
    <div class="page-break"></div>

    <table class="master-header" width="100%">
        <tr>
            <td width="75%">
                <table class="header-list">
                    <tr>
                        <td class="label-col">Nama</td>
                        <td class="colon-col">:</td>
                        <td class="name">{{ $data['student_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">NIS/NISN</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['student_nis'] }} / {{ $data['student_nisn'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Nama Sekolah</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['school_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Alamat</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['school_address'] }}</td>
                    </tr>
                </table>
            </td>
            <td width="25%">
                <table class="header-list">
                    <tr>
                        <td class="label-col">Kelas</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['class_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Fase</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['phase'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Semester</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['semester_label'] }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Tahun Pelajaran</td>
                        <td class="colon-col">:</td>
                        <td>{{ $data['academic_year'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr style="border: none; border-top: 1px solid #000; margin: 10px 0 25px 0;">

    <table class="table-bordered">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="40%">Kegiatan Ekstrakurikuler</th>
                <th width="15%">Predikat</th>
                <th width="40%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($data['eskul_results'] ?? []) as $index => $eskul)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $eskul['eskul_name'] }}</td>
                    <td class="center"><strong>{{ $eskul['predikat'] ?? '-' }}</strong></td>
                    <td>{{ $eskul['keterangan'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center">1</td>
                    <td colspan="3" class="center">Belum mengikuti ekstrakurikuler.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <br>
    <table class="attendance-table">
        <tr>
            <td class="attendance-label">Sakit</td>
            <td class="attendance-value">:&nbsp;&nbsp;{{ $data['attendance']['S'] ?? 0 }} hari</td>
        </tr>
        <tr>
            <td class="attendance-label">Izin</td>
            <td class="attendance-value">:&nbsp;&nbsp;{{ $data['attendance']['I'] ?? 0 }} hari</td>
        </tr>
        <tr>
            <td class="attendance-label">Tanpa Keterangan</td>
            <td class="attendance-value">:&nbsp;&nbsp;{{ $data['attendance']['A'] ?? 0 }} hari</td>
        </tr>
    </table>

    <div class="section-title">Catatan Wali Kelas</div>
    <div class="note-box">{{ $data['homeroom_note'] ?? '-' }}</div>

    @if (($data['semester'] ?? '') === 'even')
        <div class="section-title" style="margin-top: 12px;">Keterangan Kenaikan Kelas</div>
        <div class="note-box" style="text-align: center; font-weight: bold;">Keterangan Kenaikan Kelas :
            {{ $data['keterangan_kenaikan_kelas'] ?? '-' }}</div>
    @endif

    <table class="signature-section" width="100%">
        <tr>
            <td width="33%" class="signature-cell left">
                <div class="sig-label">Mengetahui</div>
                <div class="sig-label">Orang Tua/Wali,</div>

                <div class="signature-space"></div>

                <div class="sig-line-parent left-line"></div>
            </td>

            <td width="33%"></td>

            <td width="33%" class="signature-cell right">
                <div class="sig-label">Purwakarta, {{ $data['generated_at'] }}</div>
                <div class="sig-label">Wali Kelas,</div>

                <div class="signature-space"></div>

                <div class="sig-name">{{ $data['homeroom_teacher_name'] }}</div>
                <div class="sig-line right-line"></div>
                <div class="sig-label">NIP. {{ $data['homeroom_teacher_nip'] }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; padding-top: 15px;">
                <div class="sig-label">Mengetahui</div>
                <div class="sig-label">Kepala Sekolah</div>

                <div class="signature-space"></div>

                <div class="sig-name">{{ $data['principal_name'] }}</div>
                <div class="sig-line-center"></div>
                <div class="sig-label">NIP. {{ $data['principal_nip'] }}</div>
            </td>
        </tr>
    </table>

</body>

</html>
