<!DOCTYPE html>
<html>

<head>
    <title>Rapor Semester</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .grade-table {
            width: 100%;
            border-collapse: collapse;
        }

        .grade-table th,
        .grade-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        .grade-table th {
            background-color: #f2f2f2;
        }

        .text-left {
            text-align: left !important;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>LAPORAN HASIL BELAJAR SISWA</h2>
        <p>Tahun Ajaran: {{ $data['academic_year'] }} | Semester: {{ ucfirst($data['semester']) }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="150"><strong>NIS / ID Siswa</strong></td>
            <td>: {{ $data['student_id'] }}</td>
        </tr>
        <tr>
            <td><strong>Status Rapor</strong></td>
            <td>: {{ isset($data['published_at']) ? 'Resmi (Diterbitkan)' : 'DRAFT' }}</td>
        </tr>
    </table>

    <table class="grade-table">
        <thead>
            <tr>
                <th>No</th>
                <th class="text-left">Mata Pelajaran</th>
                <th>Total Tugas</th>
                <th>Nilai Rata-Rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['results'] as $index => $result)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $result->subject_name }}</td>
                    <td>{{ $result->total_graded_assignments }}</td>
                    <td><strong>{{ $result->final_grade }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d M Y') }}</p>
    </div>

</body>

</html>
