<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago - Panadería Jara</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            color: #333;
            background-color: #fff;
        }
        .container {
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        .logo {
            width: 80px;
            margin-bottom: 5px;
        }
        .brand-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            color: #451a03;
        }
        .header-info {
            font-size: 10px;
            line-height: 1.2;
            margin-top: 5px;
        }
        .separator {
            border-top: 1px dashed #ccc;
            margin: 10px 0;
            width: 100%;
        }
        .sale-info {
            font-size: 10px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th {
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
            text-align: left;
        }
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        .totals {
            margin-top: 10px;
            font-size: 11px;
        }
        .total-row {
            font-size: 14px;
            border-top: 1px solid #333;
            padding-top: 8px;
            margin-top: 5px;
        }
        .payment-box {
            background-color: #f9f9f9;
            padding: 8px;
            margin-top: 15px;
            border: 1px dashed #bbb;
            font-size: 11px;
        }
        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #666;
        }
        .qr-placeholder {
            width: 60px;
            height: 60px;
            border: 2px solid #000;
            margin: 10px auto;
            line-height: 60px;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container {{ $format }}">
        <!-- Cabecera -->
        <div class="text-center">
            @if(file_exists(public_path('img/logo_ticket.png')))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo_ticket.png'))) }}" class="logo">
            @endif
            <h1 class="brand-name">PANADERÍA JARA</h1>
            <div class="header-info">
                <div>RUC: 20123456789</div>
                <div>Calle Principal 123 - Ayacucho</div>
                <div>WhatsApp: 987 654 321</div>
            </div>
        </div>

        <div class="separator"></div>

        <!-- Info Venta -->
        <div class="sale-info">
            <table style="width: 100%">
                <tr>
                    <td class="fw-bold">COMPROBANTE:</td>
                    <td class="text-right">{{ $venta->numero_venta }}</td>
                </tr>
                <tr>
                    <td>FECHA:</td>
                    <td class="text-right">{{ $venta->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>HORA:</td>
                    <td class="text-right">{{ $venta->created_at->format('H:i') }}</td>
                </tr>
                <tr>
                    <td>CAJERO:</td>
                    <td class="text-right text-uppercase">{{ $venta->usuario->name }}</td>
                </tr>
            </table>
        </div>

        <div class="separator"></div>

        <!-- Detalles -->
        <table>
            <thead>
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <th class="text-center">CANT</th>
                    <th class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td>
                        {{ $detalle->producto->nombre }}<br>
                        <span style="font-size: 8px; color: #777;">S/ {{ number_format($detalle->precio_unitario, 2) }}</span>
                    </td>
                    <td class="text-center">{{ (float)$detalle->cantidad }}</td>
                    <td class="text-right">S/ {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totales -->
        <div class="totals border-top border-dashed pt-2">
            @if($venta->igv > 0)
            <div style="margin-bottom: 3px;">
                <table style="width: 100%">
                    <tr>
                        <td class="text-right" style="width: 70%">SUBTOTAL:</td>
                        <td class="text-right fw-bold">S/ {{ number_format($venta->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-right">IGV (18%):</td>
                        <td class="text-right fw-bold">S/ {{ number_format($venta->igv, 2) }}</td>
                    </tr>
                </table>
            </div>
            @endif
            
            <div class="total-row">
                <table style="width: 100%">
                    <tr class="fw-bold h4">
                        <td class="text-left">TOTAL A PAGAR:</td>
                        <td class="text-right">S/ {{ number_format($venta->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Pago -->
        <div class="payment-box">
            <table style="width: 100%">
                <tr>
                    <td>RECIBIDO EN EFECTIVO:</td>
                    <td class="text-right">S/ {{ number_format($venta->monto_pagado, 2) }}</td>
                </tr>
                <tr class="fw-bold" style="font-size: 12px;">
                    <td>CAMBIO ENTREGADO:</td>
                    <td class="text-right text-primary">S/ {{ number_format($venta->vuelto, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Pie -->
        <div class="footer text-center">
            <div class="qr-placeholder">QR</div>
            <p class="fw-bold">¡GRACIAS POR SU COMPRA!</p>
            <p>Panadería & Pastelería Jara: <br>Calidad Hecha Pan.</p>
        </div>
    </div>
</body>
</html>
