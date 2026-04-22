<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $venta->numero_venta }}</title>
    <style>
        @page {
            margin: 2mm 2mm 2mm 2mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-size: {{ $format === '58mm' ? '9px' : '11px' }};
            line-height: 1.2;
        }

        .ticket {
            width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }

        .logo {
            width: {{ $format === '58mm' ? '28%' : '40%' }};
            display: block;
            margin: 0 auto 4px auto;
        }

        .brand-name {
            margin: 0;
            font-size: {{ $format === '58mm' ? '14px' : '16px' }};
            font-weight: bold;
        }

        .subtitle {
            margin: 2px 0 4px 0;
            font-size: {{ $format === '58mm' ? '9px' : '10px' }};
            font-weight: bold;
        }

        .info-business {
            font-size: {{ $format === '58mm' ? '8px' : '9px' }};
            line-height: 1.25;
            margin-bottom: 4px;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .row {
            width: 100%;
            clear: both;
            margin-bottom: 2px;
        }

        .row .label {
            display: inline-block;
            width: 38%;
            vertical-align: top;
        }

        .row .value {
            display: inline-block;
            width: 60%;
            vertical-align: top;
            text-align: right;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            border-bottom: 1px solid #000;
            padding: 2px 0;
            font-size: {{ $format === '58mm' ? '8px' : '9px' }};
        }

        tbody td {
            padding: 2px 0;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .col-producto {
            width: 58%;
        }

        .col-cant {
            width: 12%;
            text-align: center;
        }

        .col-total {
            width: 30%;
            text-align: right;
        }

        .producto-nombre {
            word-break: break-word;
        }

        .totales {
            margin-top: 4px;
        }

        .totales .line {
            width: 100%;
            margin-bottom: 2px;
        }

        .totales .line .left {
            display: inline-block;
            width: 58%;
        }

        .totales .line .right {
            display: inline-block;
            width: 40%;
            text-align: right;
        }

        .total-final {
            border-top: 1px solid #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            margin-top: 3px;
            font-size: {{ $format === '58mm' ? '11px' : '13px' }};
            font-weight: bold;
        }

        .payment-section {
            border: 1px dashed #000;
            padding: 4px;
            margin-top: 6px;
        }

        .payment-section .line {
            width: 100%;
            margin-bottom: 2px;
        }

        .payment-section .left {
            display: inline-block;
            width: 58%;
        }

        .payment-section .right {
            display: inline-block;
            width: 40%;
            text-align: right;
        }

        .footer {
            margin-top: 8px;
        }

        .qr-container {
            display: block;
            margin: 5px auto;
        }

        .qr-container img {
            width: {{ $format === '58mm' ? '60px' : '85px' }};
            height: auto;
        }

        .thanks {
            font-weight: bold;
            margin: 2px 0;
            font-size: {{ $format === '58mm' ? '9px' : '10px' }};
        }

        .thanks-sub {
            margin: 0;
            font-size: {{ $format === '58mm' ? '8px' : '9px' }};
        }
    </style>
</head>
<body @if(!request()->has('preview')) onload="window.print(); window.onafterprint = function(){ window.close(); }" @endif>
    <div class="ticket">

        <div class="text-center">
            <div class="brand-name">{{ config('empresa.nombre') }}</div>
            <div class="subtitle">{{ config('empresa.slogan') }}</div>

            @if(file_exists(public_path('img/logo.png')))
                <img src="{{ public_path('img/logo.png') }}" class="logo">
            @endif

            <div class="info-business">
                <div><span class="fw-bold">RUC:</span> {{ config('empresa.ruc') }}</div>
                <div>{{ $venta->sucursal->direccion ?? config('empresa.direccion') }}</div>
                <div>WhatsApp: {{ $venta->sucursal->telefono ?? config('empresa.celular') }}</div>
            </div>
        </div>

        <div class="separator"></div>

        <div class="row">
            <span class="label">TICKET:</span>
            <span class="value">{{ $venta->numero_venta }}</span>
        </div>
        <div class="row">
            <span class="label">FECHA:</span>
            <span class="value">{{ $venta->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span class="label">CAJERO:</span>
            <span class="value text-uppercase">{{ $venta->usuario->name }}</span>
        </div>

        <div class="separator"></div>

        <table>
            <thead>
                <tr>
                    <th class="col-producto text-left">PRODUCTO</th>
                    <th class="col-cant">CANT</th>
                    <th class="col-total text-right">IMP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                    <tr>
                        <td class="col-producto producto-nombre">
                            {{ $detalle->producto->nombre }}
                        </td>
                        <td class="col-cant">
                            {{ rtrim(rtrim(number_format($detalle->cantidad, 2, '.', ''), '0'), '.') }}
                        </td>
                        <td class="col-total text-right">
                            {{ number_format($detalle->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="separator"></div>

        <div class="totales">
            @if($venta->igv > 0)
                <div class="line">
                    <span class="left">SUBTOTAL:</span>
                    <span class="right">S/ {{ number_format($venta->subtotal, 2) }}</span>
                </div>
                <div class="line">
                    <span class="left">IGV:</span>
                    <span class="right">S/ {{ number_format($venta->igv, 2) }}</span>
                </div>
            @endif

            <div class="line total-final">
                <span class="left">TOTAL:</span>
                <span class="right">S/ {{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        <div class="payment-section">
            <div class="line">
                <span class="left">RECIBIDO:</span>
                <span class="right">S/ {{ number_format($venta->monto_pagado, 2) }}</span>
            </div>
            <div class="line">
                <span class="left">VUELTO:</span>
                <span class="right">S/ {{ number_format($venta->vuelto, 2) }}</span>
            </div>
        </div>

        <div class="footer text-center">
            @php
                $comprobante = $venta->comprobante;
            @endphp
            @if(isset($qrCodeBase64) && $qrCodeBase64)
                <div class="qr-container mb-2">
                    <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" alt="QR">
                </div>
            @elseif(isset($venta->comprobante) && $venta->comprobante->codigo_qr)
                <div class="qr-container mb-2">
                    <img src="data:image/svg+xml;base64,{{ base64_encode((string) SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(90)->margin(0)->generate($venta->comprobante->codigo_qr)) }}" alt="QR">
                </div>
            @endif
            <p class="thanks">¡GRACIAS POR SU COMPRA!</p>
            <p class="thanks-sub">Vuelva pronto</p>
        </div>

    </div>
</body>
</html>