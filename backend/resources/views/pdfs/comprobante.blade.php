<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago - Panadería Jara</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 8px;
            color: #111;
            background: #fff;
            font-size: 10px;
            line-height: 1.25;
        }

        .ticket {
            width: 100%;
        }

        .ticket.thermal-58 {
            font-size: 9px;
        }

        .ticket.thermal-80 {
            font-size: 10px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .small { font-size: 9px; }
        .x-small { font-size: 8px; }

        .logo {
            width: 62px;
            max-height: 62px;
            object-fit: contain;
            margin: 0 auto 4px auto;
            display: block;
        }

        .brand-name {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .brand-subtitle {
            margin-top: 2px;
            font-size: 9px;
        }

        .header-info,
        .customer-info,
        .sale-info,
        .payment-info,
        .footer {
            width: 100%;
        }

        .header-info div,
        .customer-info div,
        .sale-info div,
        .payment-info div,
        .footer div {
            margin-bottom: 1px;
        }

        .separator {
            border-top: 1px dashed #555;
            margin: 8px 0;
            width: 100%;
        }

        .separator-strong {
            border-top: 1px solid #000;
            margin: 8px 0 6px 0;
            width: 100%;
        }

        .doc-box {
            border: 1px solid #000;
            padding: 5px 4px;
            margin-top: 6px;
            text-align: center;
        }

        .doc-title {
            font-size: 11px;
            font-weight: bold;
            margin: 0;
        }

        .doc-number {
            font-size: 11px;
            font-weight: bold;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table {
            margin-top: 4px;
            font-size: 9px;
        }

        .items-table thead th {
            border-bottom: 1px solid #000;
            padding: 3px 0;
            font-size: 8px;
        }

        .items-table tbody td {
            padding: 3px 0;
            vertical-align: top;
        }

        .items-table .qty {
            width: 14%;
            text-align: left;
        }

        .items-table .desc {
            width: 50%;
            padding-right: 4px;
        }

        .items-table .price {
            width: 16%;
            text-align: right;
        }

        .items-table .total {
            width: 20%;
            text-align: right;
        }

        .totals {
            margin-top: 6px;
            width: 100%;
        }

        .totals-row {
            width: 100%;
            clear: both;
            margin-bottom: 2px;
        }

        .totals-label {
            display: inline-block;
            width: 60%;
            text-align: left;
        }

        .totals-value {
            display: inline-block;
            width: 38%;
            text-align: right;
        }

        .grand-total {
            border-top: 1px solid #000;
            margin-top: 4px;
            padding-top: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .payment-box {
            border: 1px dashed #666;
            padding: 6px;
            margin-top: 8px;
        }

        .highlight {
            font-weight: bold;
        }

        .footer {
            margin-top: 10px;
            color: #333;
        }

        .thanks {
            margin-top: 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .legal {
            margin-top: 6px;
            font-size: 8px;
            text-align: center;
        }

        .qr-box {
            width: 68px;
            height: 68px;
            border: 1px solid #000;
            margin: 8px auto 4px auto;
            text-align: center;
            line-height: 68px;
            font-size: 9px;
            font-weight: bold;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
@php
    $is58 = $format === '58mm';
    $ticketClass = $is58 ? 'thermal-58' : 'thermal-80';

    $venta = $venta ?? null;
    $cliente = $venta?->cliente;
    $usuario = $venta?->usuario;
    $comprobante = $venta?->comprobante;

    $serieNumero = $comprobante
        ? trim(($comprobante->serie ?? '') . '-' . ($comprobante->numero ?? ''))
        : strtoupper($venta->tipo_comprobante ?? 'COMPROBANTE') . ' SIN SERIE';

    $tipoComprobante = strtoupper($venta->tipo_comprobante ?? 'COMPROBANTE');

    $fechaEmision = optional($venta->created_at)->format('d/m/Y');
    $horaEmision  = optional($venta->created_at)->format('H:i');

    $clienteNombre = $cliente?->razon_social
        ?? $cliente?->nombre_completo
        ?? $cliente?->nombre
        ?? 'CLIENTE VARIOS';

    $clienteDocumento = $cliente?->numero_documento
        ?? $cliente?->documento
        ?? '-';

    $clienteTipoDocumento = $cliente?->tipo_documento ?? 'DOC';

    $subtotal = (float) ($venta->subtotal ?? 0);
    $igv = (float) ($venta->igv ?? 0);
    $descuento = (float) ($venta->descuento ?? 0);
    $total = (float) ($venta->total ?? 0);
    $montoPagado = (float) ($venta->monto_pagado ?? 0);
    $vuelto = (float) ($venta->vuelto ?? 0);

    $formaPago = $venta->forma_pago ? ucfirst(str_replace('_', ' ', $venta->forma_pago)) : 'No especificado';
@endphp

<div class="ticket {{ $ticketClass }}">

    {{-- CABECERA --}}
    <div class="text-center">
        @if(file_exists(public_path('img/logo_ticket.png')))
            <img
                src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logo_ticket.png'))) }}"
                class="logo"
                alt="Logo"
            >
        @endif

        <div class="brand-name uppercase">Panadería Jara</div>
        <div class="brand-subtitle">Panadería y Pastelería</div>

        <div class="header-info small" style="margin-top:4px;">
            <div><span class="fw-bold">RUC:</span> 20123456789</div>
            <div>Calle Principal 123 - Ayacucho</div>
            <div>WhatsApp: 987 654 321</div>
        </div>

        <div class="doc-box">
            <div class="doc-title">{{ $tipoComprobante }}</div>
            <div class="doc-number">{{ $serieNumero }}</div>
        </div>
    </div>

    <div class="separator"></div>

    {{-- DATOS DE VENTA --}}
    <div class="sale-info small">
        <div><span class="fw-bold">N° Venta:</span> {{ $venta->numero_venta ?? '-' }}</div>
        <div><span class="fw-bold">Fecha:</span> {{ $fechaEmision }} <span class="fw-bold">Hora:</span> {{ $horaEmision }}</div>
        <div><span class="fw-bold">Cajero:</span> {{ $usuario->name ?? 'Sistema' }}</div>
        <div><span class="fw-bold">Estado:</span> {{ strtoupper($venta->estado ?? 'EMITIDO') }}</div>
    </div>

    <div class="separator"></div>

    {{-- CLIENTE --}}
    <div class="customer-info small">
        <div class="fw-bold">DATOS DEL CLIENTE</div>
        <div><span class="fw-bold">Cliente:</span> {{ $clienteNombre }}</div>
        <div><span class="fw-bold">{{ strtoupper($clienteTipoDocumento) }}:</span> {{ $clienteDocumento }}</div>

        @if(!empty($cliente?->direccion))
            <div><span class="fw-bold">Dirección:</span> {{ $cliente->direccion }}</div>
        @endif
    </div>

    <div class="separator"></div>

    {{-- DETALLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="qty">Cant.</th>
                <th class="desc">Descripción</th>
                <th class="price">P.Unit</th>
                <th class="total">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($venta->detalles as $detalle)
                @php
                    $cantidad = (float) ($detalle->cantidad ?? 0);
                    $precio = (float) ($detalle->precio ?? $detalle->precio_unitario ?? 0);
                    $importe = (float) ($detalle->subtotal ?? ($cantidad * $precio));
                    $producto = $detalle->producto->nombre ?? 'Producto';
                @endphp
                <tr>
                    <td class="qty">{{ rtrim(rtrim(number_format($cantidad, 2, '.', ''), '0'), '.') }}</td>
                    <td class="desc">{{ $producto }}</td>
                    <td class="price nowrap">S/ {{ number_format($precio, 2) }}</td>
                    <td class="total nowrap">S/ {{ number_format($importe, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay productos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="separator-strong"></div>

    {{-- TOTALES --}}
    <div class="totals small">
        <div class="totals-row">
            <span class="totals-label">Subtotal:</span>
            <span class="totals-value">S/ {{ number_format($subtotal, 2) }}</span>
        </div>

        @if($descuento > 0)
            <div class="totals-row">
                <span class="totals-label">Descuento:</span>
                <span class="totals-value">- S/ {{ number_format($descuento, 2) }}</span>
            </div>
        @endif

        <div class="totals-row">
            <span class="totals-label">IGV:</span>
            <span class="totals-value">S/ {{ number_format($igv, 2) }}</span>
        </div>

        <div class="totals-row grand-total">
            <span class="totals-label">TOTAL:</span>
            <span class="totals-value">S/ {{ number_format($total, 2) }}</span>
        </div>
    </div>

    {{-- PAGO --}}
    <div class="payment-box small">
        <div class="fw-bold text-center">DETALLE DE PAGO</div>
        <div style="margin-top:4px;">
            <div><span class="fw-bold">Forma de pago:</span> {{ $formaPago }}</div>
            <div><span class="fw-bold">Monto pagado:</span> S/ {{ number_format($montoPagado, 2) }}</div>
            <div><span class="fw-bold">Vuelto:</span> S/ {{ number_format($vuelto, 2) }}</div>
        </div>
    </div>

    @if(!empty($venta->observacion))
        <div class="separator"></div>
        <div class="small">
            <div class="fw-bold">OBSERVACIÓN</div>
            <div>{{ $venta->observacion }}</div>
        </div>
    @endif

    {{-- QR opcional --}}
    {{--
    <div class="qr-box">QR</div>
    --}}

    {{-- FOOTER --}}
    <div class="footer text-center">
        <div class="thanks">¡Gracias por su compra!</div>

        <div class="legal">
            Representación impresa del {{ strtolower($tipoComprobante) }} electrónico.
        </div>

        <div class="x-small" style="margin-top:4px;">
            Impreso el {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

</div>
</body>
</html>