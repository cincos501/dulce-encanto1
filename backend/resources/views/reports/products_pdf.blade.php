<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Productos Más Vendidos</title>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 11px; margin: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header h1 { color: #851c36; margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th { background-color: #851c36; color: white; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dulce Encanto</h1>
        <p>Reporte de Productos Más Vendidos ({{ $startDate }} a {{ $endDate }})</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%">#</th>
                <th>Producto</th>
                <th>Presentación</th>
                <th style="text-align: center; width: 15%">Cant. Vendida</th>
                <th style="text-align: right; width: 20%">Total Generado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $index => $p)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: bold; color: #851c36;">{{ $p->product_name }}</td>
                    <td>{{ $p->variant_name }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format((float)$p->quantity_sold) }}</td>
                    <td style="text-align: right; font-weight: bold;">Bs. {{ number_format((float)$p->total_generated, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado automáticamente por el panel administrativo de Dulce Encanto el {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
