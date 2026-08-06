<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Hadir Rapat</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0 0; color: #666; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; }
        .info td { padding: 3px; }
        .info td:first-child { width: 150px; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #999; padding: 8px; text-align: left; }
        .table th { background-color: #f3f4f6; }
        .status-present { color: green; font-weight: bold; }
        .status-late { color: orange; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Pondok Pesantren Tamjidullah</h1>
        <p>Sistem Manajemen Kehadiran Asatidz (SIMAS)</p>
    </div>

    <div class="info">
        <table>
            <tr>
                <td>Topik Rapat</td>
                <td>: {{ $meeting->title }}</td>
            </tr>
            <tr>
                <td>Tipe Rapat</td>
                <td>: {{ $meeting->type->name ?? 'Internal' }}</td>
            </tr>
            <tr>
                <td>Unit / Instansi</td>
                <td>: {{ $meeting->unit->name ?? 'Semua Unit' }}</td>
            </tr>
            <tr>
                <td>Waktu Pelaksanaan</td>
                <td>: {{ \Carbon\Carbon::parse($meeting->start_time)->format('d F Y, H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 20%">ID Asatidz</th>
                <th style="width: 40%">Nama Asatidz</th>
                <th style="width: 15%">Status</th>
                <th style="width: 20%">Waktu Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($participants as $index => $p)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $p->asatidz->id_asatidz ?? '-' }}</td>
                <td>{{ $p->asatidz->name ?? '-' }}</td>
                <td>
                    <span class="
                        @if(strtolower($p->attendance_status) == 'present') status-present 
                        @elseif(strtolower($p->attendance_status) == 'late') status-late 
                        @elseif(strtolower($p->attendance_status) == 'absent') status-absent 
                        @endif
                    ">
                        {{ ucfirst($p->attendance_status) }}
                    </span>
                </td>
                <td>{{ $p->created_at->format('H:i:s') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
