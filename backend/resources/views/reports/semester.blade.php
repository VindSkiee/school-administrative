<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor Semester</title>
    <style>
        @page { margin: 70px 90px 100px 130px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; line-height: 1.3; }
        table { width: 100%; border-collapse: collapse; }

        /* Trik Master Table untuk DOMPDF agar Header Kiri-Kanan rapi */
        .master-header td { vertical-align: top; border: none; padding: 0; }
        
        /* Table khusus list key-value header */
        .header-list td { padding: 2px 0; border: none; font-size: 11px; font-weight: semibold; line-height: 1; }
        .name { text-transform: uppercase; }
        .label-col { width: 90px; }
        .colon-col { width: 15px; text-align: center; }

        .report-title { margin: 20px 0 15px 0; text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; }

        /* Tabel Nilai Utama */
        .table-bordered th, .table-bordered td { border: 1px solid #000; padding: 4px 2px 4px 2px; vertical-align: top; }
        .table-bordered th { text-align: center; font-weight: bold; font-size: 11px; }
        .center { text-align: center; items-align: center; }
        .left { text-align: left; }

        .capaian-text { font-size: 10px; line-height: 1; text-align: start; }

        /* Footer Kustom DOMPDF */
        footer { position: fixed; bottom: -10px; left: 0px; right: 0px; height: 20px; font-size: 10px; font-weight: bold; font-style: italic; font-family:'Times New Roman', Times, serif; word-spacing: 4px; letter-spacing: 1px; }
        .footer-table td { border: none; padding: 0; }
    </style>
</head>
<body>
    <footer>
        <hr style="border: none; border-top: 1px solid #000; margin: 10px 0 5px 0;">
        <table class="footer-table">
            <tr>
                <td width="80%" class="left">
                    Kelas {{ $data['class_name'] }} | {{ $data['student_name'] }} | {{ $data['student_nis'] ?? $data['student_nisn'] }}
                </td>
                <td width="20%" class="right" style="text-align: right;">Halaman : 1</td>
            </tr>
        </table>
    </footer>

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
                        <td>D</td>
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

    <hr style="border: none; border-top: 1px solid #000; margin: 10px 0 5px 0;">

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
                    <td colspan="4" class="center" style="padding: 20px;">Data nilai belum tersedia atau belum lengkap.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
</body>
</html>