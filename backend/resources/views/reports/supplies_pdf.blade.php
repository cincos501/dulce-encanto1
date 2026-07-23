<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Insumos</title>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 11px; margin: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header h1 { color: #851c36; margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th { background-color: #851c36; color: white; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef9c3; color: #a16207; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dulce Encanto</h1>
        <p>Reporte de Estado de Insumos y Suministros</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%">#</th>
                <th>Nombre del Insumo</th>
                <th style="text-align: right;">Stock Actual</th>
                <th style="text-align: center;">Unidad</th>
                <th style="text-align: right;">Stock Mínimo</th>
                <th style="text-align: center; width: 20%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($supplies as $index => $s)
                @php
                    $badgeClass = 'badge-success';
                    if ($s->status === 'Stock crítico') {
                        $badgeClass = 'badge-danger';
                    } elseif ($s->status === 'Stock bajo') {
                        $badgeClass = 'badge-warning';
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold; color: #851c36;">{{ $s->name }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format((float)$s->stock, 2) }}</td>
                    <td style="text-align: center;">{{ $s->unit }}</td>
                    <td style="text-align: right;">{{ number_format((float)$s->minimum_stock, 2) }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ $badgeClass }}">{{ $s->status }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado automáticamente por el panel administrativo de Dulce Encanto el {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
