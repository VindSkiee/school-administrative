<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rapor Semester</title>
    <style>
        @page {
            margin: 20px 24px 18px 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.35;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-address {
            font-size: 10px;
            margin-top: 2px;
        }

        .report-title {
            margin: 10px 0 12px 0;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 32%;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 6px 0;
            text-transform: uppercase;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .table-bordered th {
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .small {
            font-size: 10px;
        }

        .summary-box {
            margin-top: 10px;
        }

        .summary-box td {
            width: 50%;
            vertical-align: top;
            padding-right: 6px;
        }

        .mini-table th,
        .mini-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }

        .mini-table th {
            background: #f2f2f2;
            text-align: center;
        }

        .note-box {
            min-height: 64px;
            border: 1px solid #000;
            padding: 8px;
        }

        .signature-table {
            margin-top: 16px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 33.3333%;
            vertical-align: top;
            text-align: center;
            padding: 0 6px;
        }

        .signature-space {
            height: 52px;
        }

        .muted {
            font-size: 10px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="school-name">{{ $data['school_name'] }}</div>
                <div class="school-address">{{ $data['school_address'] }}</div>
                <div class="school-address">Tahun Pelajaran: {{ $data['academic_year'] }}</div>
            </td>
            <td width="40%">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Nama Siswa</td>
                        <td>: {{ $data['student_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">NIS / NISN</td>
                        <td>: {{ $data['student_nis'] }} / {{ $data['student_nisn'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Kelas</td>
                        <td>: {{ $data['class_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Semester</td>
                        <td>: {{ $data['semester_label'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="report-title">Laporan Hasil Belajar Siswa</div>

    <div class="section-title">Nilai Capaian Hasil Belajar</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Mata Pelajaran</th>
                <th width="12%">Nilai Akhir (Angka)</th>
                <th width="58%">Capaian Kompetensi / Predikat / Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['results'] as $index => $result)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $result['subject_code'] }}</strong><br>
                        {{ $result['subject_name'] }}
                    </td>
                    <td class="center">
                        {{ is_null($result['final_grade']) ? '-' : number_format($result['final_grade'], 2) }}
                    </td>
                    <td>
                        <div><strong>Guru:</strong> {{ $result['teacher_name'] }}</div>
                        <div><strong>Predikat:</strong> {{ $result['predicate'] }}</div>
                        <div><strong>Deskripsi:</strong> {{ $result['description'] }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Data nilai belum tersedia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="section-title">Ekstrakurikuler</div>
                <table class="mini-table">
                    <tr>
                        <th width="45%">Kegiatan</th>
                        <th>Keterangan</th>
                    </tr>
                    <tr>
                        <td>Ekstrakurikuler</td>
                        <td>Belum diinput</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="section-title">Kehadiran</div>
                <table class="mini-table">
                    <tr>
                        <th width="45%">Jenis</th>
                        <th>Jumlah</th>
                    </tr>
                    <tr>
                        <td>Sakit (S)</td>
                        <td class="center">{{ $data['attendance']['S'] }}</td>
                    </tr>
                    <tr>
                        <td>Izin (I)</td>
                        <td class="center">{{ $data['attendance']['I'] }}</td>
                    </tr>
                    <tr>
                        <td>Tanpa Keterangan (A)</td>
                        <td class="center">{{ $data['attendance']['A'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Catatan Wali Kelas</div>
    <div class="note-box">
        {{ $data['homeroom_note'] }}
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <div>Orang Tua / Wali</div>
                <div class="signature-space"></div>
                <div>(_____________________)</div>
            </td>
            <td>
                <div>Wali Kelas</div>
                <div class="signature-space"></div>
                <div><strong>{{ $data['homeroom_teacher_name'] }}</strong></div>
                <div class="small">NIP. {{ $data['homeroom_teacher_nip'] }}</div>
            </td>
            <td>
                <div>Kepala Sekolah</div>
                <div class="signature-space"></div>
                <div><strong>{{ $data['principal_name'] }}</strong></div>
                <div class="small">NIP. {{ $data['principal_nip'] }}</div>
            </td>
        </tr>
    </table>

    <div class="right small" style="margin-top: 8px;">
        Dicetak pada: {{ $data['generated_at'] }}
    </div>
</body>

</html>
