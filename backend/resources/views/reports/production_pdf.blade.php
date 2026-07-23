<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Producción</title>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 11px; margin: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .header h1 { color: #851c36; margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0 0; color: #666; font-size: 12px; }
        .metrics-grid { margin-bottom: 25px; width: 100%; border-collapse: collapse; }
        .metrics-grid td { width: 16.66%; padding: 8px; background-color: #f8fafc; border: 1px solid #e2e8f0; text-align: center; }
        .metric-title { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; margin-bottom: 4px; }
        .metric-value { font-size: 14px; color: #851c36; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { background-color: #851c36; color: white; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .table td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dulce Encanto</h1>
        <p>Reporte de Control de Producción ({{ $startDate }} a {{ $endDate }})</p>
    </div>

    <table class="metrics-grid">
        <tr>
            <td>
                <div class="metric-title">Pendientes</div>
                <div class="metric-value">{{ $metrics['status_counts']['pendiente'] ?? 0 }}</div>
            </td>
            <td>
                <div class="metric-title">Confirmados</div>
                <div class="metric-value">{{ $metrics['status_counts']['confirmado'] ?? 0 }}</div>
            </td>
            <td>
                <div class="metric-title">Preparación</div>
                <div class="metric-value">{{ $metrics['status_counts']['en_preparación'] ?? 0 }}</div>
            </td>
            <td>
                <div class="metric-title">Listos</div>
                <div class="metric-value">{{ $metrics['status_counts']['listo'] ?? 0 }}</div>
            </td>
            <td>
                <div class="metric-title">Entregados</div>
                <div class="metric-value">{{ $metrics['status_counts']['entregado'] ?? 0 }}</div>
            </td>
            <td>
                <div class="metric-title">Cancelados</div>
                <div class="metric-value">{{ $metrics['status_counts']['cancelado'] ?? 0 }}</div>
            </td>
        </tr>
    </table>

    <h3 style="color: #851c36; font-size: 13px; margin-bottom: 5px;">Detalle de Pedidos en Producción</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Nº Pedido</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th style="text-align: center;">Cant. Prod.</th>
                <th>Estado</th>
                <th>Tipo Entrega</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $o)
                @php
                    $deliveryDetails = ['delivery_type' => 'Retiro en tienda', 'address' => '', 'observations' => ''];
                    $email = $o->customer?->email;
                    if ($email && str_starts_with(trim($email), '{')) {
                        $parsed = json_decode($email, true);
                        if (is_array($parsed)) {
                            $deliveryDetails['delivery_type'] = $parsed['delivery_type'] ?? 'Retiro en tienda';
                        }
                    }
                @endphp
                <tr>
                    <td>#{{ $o->id }}</td>
                    <td>{{ $o->created_at->format('Y-m-d') }}</td>
                    <td>{{ $o->customer?->full_name ?? 'Cliente Anónimo' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $o->items->sum('quantity') }}</td>
                    <td style="font-weight: bold; color: #851c36;">{{ $o->status }}</td>
                    <td>{{ $deliveryDetails['delivery_type'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado automáticamente por el panel administrativo de Dulce Encanto el {{ date('Y-m-d H:i:s') }}
    </div>
</body>
</html>
