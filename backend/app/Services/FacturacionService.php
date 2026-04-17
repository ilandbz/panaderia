<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\Comprobante;
use App\Infrastructure\Sunat\Services\GreenterService;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FacturacionService
{
    protected $greenterService;

    public function __construct(GreenterService $greenterService)
    {
        $this->greenterService = $greenterService;
    }

    public function generar(Venta $venta)
    {
        $serie = ($venta->tipo_comprobante === 'factura') ? 'F001' : 'BA01';
        $ultimo = Comprobante::where('serie', $serie)->max('correlativo') ?? 0;
        $correlativo = $ultimo + 1;

        $comprobante = Comprobante::create([
            'venta_id'           => $venta->id,
            'tipo'               => $venta->tipo_comprobante,
            'serie'               => $serie,
            'correlativo'         => $correlativo,
            'numero_comprobante' => "{$serie}-" . str_pad($correlativo, 8, '0', STR_PAD_LEFT),
            'estado_sunat'       => 'pendiente',
        ]);

        try {
            // Cargar relaciones necesarias para el mapeo
            $venta->load('detalles.producto', 'cliente');

            $invoice = $this->mapearVentaAInvoice($venta, $comprobante);
            $see = $this->greenterService->getSee();

            /** @var \Greenter\Model\Response\BillResult $result */
            $result = $see->send($invoice);

            // Guardar XML firmado
            Storage::put("sunat/xml/{$comprobante->numero_comprobante}.xml", $see->getFactory()->getLastXml());

            if (!$result->isSuccess()) {
                Log::error("Error SUNAT al enviar comprobante {$comprobante->numero_comprobante}: " . $result->getError()->getMessage());
                $comprobante->update(['estado_sunat' => 'rechazado', 'respuesta_sunat' => ['error' => $result->getError()->getMessage()]]);
                return $comprobante;
            }

            $cdr = $result->getCdrResponse();
            Storage::put("sunat/cdr/R-{$comprobante->numero_comprobante}.zip", $result->getCdrZip());

            $comprobante->update([
                'estado_sunat'    => 'aceptado',
                'codigo_hash'     => $this->extractHash($see->getFactory()->getLastXml()),
                'codigo_qr'       => $this->generateQrString($venta, $comprobante, $this->extractHash($see->getFactory()->getLastXml())),
                'respuesta_sunat' => [
                    'id' => $cdr->getId(),
                    'code' => $cdr->getCode(),
                    'description' => $cdr->getDescription(),
                    'notes' => $cdr->getNotes(),
                ],
                'xml_path' => "sunat/xml/{$comprobante->numero_comprobante}.xml",
            ]);
        } catch (Exception $e) {
            Log::error("Excepción al enviar comprobante {$comprobante->numero_comprobante}: " . $e->getMessage());
            $comprobante->update(['estado_sunat' => 'rechazado', 'respuesta_sunat' => ['exception' => $e->getMessage()]]);
        }

        return $comprobante;
    }

    public function generarNotaCredito(Venta $venta, string $motivo)
    {
        $serieOrig = $venta->comprobante->serie;
        $serieNC = (str_starts_with($serieOrig, 'F')) ? 'FC01' : 'BC01';

        $ultimo = Comprobante::where('serie', $serieNC)->max('correlativo') ?? 0;
        $correlativo = $ultimo + 1;

        $comprobanteNC = Comprobante::create([
            'venta_id'           => $venta->id,
            'tipo'               => 'nota_credito',
            'serie'               => $serieNC,
            'correlativo'         => $correlativo,
            'numero_comprobante' => "{$serieNC}-" . str_pad($correlativo, 8, '0', STR_PAD_LEFT),
            'estado_sunat'       => 'pendiente',
            'respuesta_sunat'    => ['motivo' => $motivo, 'documento_referencia' => $venta->comprobante->numero_comprobante]
        ]);

        try {
            // Cargar relaciones necesarias para el mapeo
            $venta->load('detalles.producto', 'cliente', 'comprobante');

            $note = $this->mapearVentaANote($venta, $comprobanteNC, $motivo);
            $see = $this->greenterService->getSee();

            /** @var \Greenter\Model\Response\BillResult $result */
            $result = $see->send($note);

            Storage::put("sunat/xml/{$comprobanteNC->numero_comprobante}.xml", $see->getFactory()->getLastXml());

            if (!$result->isSuccess()) {
                Log::error("Error SUNAT al enviar Nota de Crédito {$comprobanteNC->numero_comprobante}: " . $result->getError()->getMessage());
                $comprobanteNC->update(['estado_sunat' => 'rechazado', 'respuesta_sunat' => array_merge($comprobanteNC->respuesta_sunat, ['error' => $result->getError()->getMessage()])]);
                return $comprobanteNC;
            }

            $cdr = $result->getCdrResponse();
            Storage::put("sunat/cdr/R-{$comprobanteNC->numero_comprobante}.zip", $result->getCdrZip());

            $comprobanteNC->update([
                'estado_sunat'    => 'aceptado',
                'codigo_hash'     => $this->extractHash($see->getFactory()->getLastXml()),
                'codigo_qr'       => $this->generateQrString($venta, $comprobanteNC, $this->extractHash($see->getFactory()->getLastXml())),
                'respuesta_sunat' => array_merge($comprobanteNC->respuesta_sunat, [
                    'id' => $cdr->getId(),
                    'code' => $cdr->getCode(),
                    'description' => $cdr->getDescription(),
                    'notes' => $cdr->getNotes(),
                ]),
                'xml_path' => "sunat/xml/{$comprobanteNC->numero_comprobante}.xml",
            ]);
        } catch (Exception $e) {
            Log::error("Excepción al enviar Nota de Crédito {$comprobanteNC->numero_comprobante}: " . $e->getMessage());
            $comprobanteNC->update(['estado_sunat' => 'rechazado', 'respuesta_sunat' => array_merge($comprobanteNC->respuesta_sunat, ['exception' => $e->getMessage()])]);
        }

        return $comprobanteNC;
    }

    /**
     * Reenviar un comprobante que quedó en estado 'pendiente' (error de conectividad u otro fallo de envío).
     * NO usar con comprobantes rechazados: el número ya fue consumido tributariamente.
     */
    public function reenviar(Venta $venta): Comprobante
    {
        $comprobante = $venta->comprobante;

        $estado = $comprobante->estado_sunat;
        $respuesta = $comprobante->respuesta_sunat;
        $mensaje = strtolower(($respuesta['description'] ?? '') . ($respuesta['error'] ?? '') . ($respuesta['exception'] ?? ''));
        $esErrorPerfil = str_contains($mensaje, 'perfil') || str_contains($mensaje, 'policy');

        if (!$comprobante || ($estado !== 'pendiente' && !($estado === 'rechazado' && $esErrorPerfil))) {
            throw new Exception('Solo se pueden reenviar comprobantes en estado pendiente o rechazados por falta de permisos en el perfil SUNAT.');
        }

        if (!in_array($comprobante->tipo, ['boleta', 'factura'])) {
            throw new Exception('Solo se pueden reenviar boletas y facturas.');
        }

        $venta->load('detalles.producto', 'cliente');

        try {
            $invoice = $this->mapearVentaAInvoice($venta, $comprobante);
            $see = $this->greenterService->getSee();

            /** @var \Greenter\Model\Response\BillResult $result */
            $result = $see->send($invoice);

            // Guardar XML firmado (sobreescribe si ya existía)
            Storage::put("sunat/xml/{$comprobante->numero_comprobante}.xml", $see->getFactory()->getLastXml());

            if (!$result->isSuccess()) {
                $errorMsg = $result->getError()->getMessage();
                Log::error("Reenvío fallido para comprobante {$comprobante->numero_comprobante}: {$errorMsg}");

                // Mantener en 'pendiente' si fue un error de comunicación (código 0 o vacío),
                // solo marcar rechazado si SUNAT respondió con código de error válido.
                $errorCode = $result->getError()->getCode();
                $nuevoEstado = ($errorCode && $errorCode !== '0') ? 'rechazado' : 'pendiente';

                $comprobante->update([
                    'estado_sunat'    => $nuevoEstado,
                    'respuesta_sunat' => ['error' => $errorMsg, 'code' => $errorCode],
                ]);
                return $comprobante->fresh();
            }

            $cdr = $result->getCdrResponse();
            Storage::put("sunat/cdr/R-{$comprobante->numero_comprobante}.zip", $result->getCdrZip());
            $hash = $this->extractHash($see->getFactory()->getLastXml());

            $comprobante->update([
                'estado_sunat'    => 'aceptado',
                'codigo_hash'     => $hash,
                'codigo_qr'       => $this->generateQrString($venta, $comprobante, $hash),
                'xml_path'        => "sunat/xml/{$comprobante->numero_comprobante}.xml",
                'respuesta_sunat' => [
                    'id'          => $cdr->getId(),
                    'code'        => $cdr->getCode(),
                    'description' => $cdr->getDescription(),
                    'notes'       => $cdr->getNotes(),
                ],
            ]);

            Log::info("Comprobante {$comprobante->numero_comprobante} reenviado y aceptado por SUNAT.");
        } catch (Exception $e) {
            Log::error("Excepción al reenviar comprobante {$comprobante->numero_comprobante}: " . $e->getMessage());
            $comprobante->update([
                'estado_sunat'    => 'pendiente', // Mantener pendiente para poder volver a intentar
                'respuesta_sunat' => ['exception' => $e->getMessage()],
            ]);
        }

        return $comprobante->fresh();
    }

    private function mapearVentaAInvoice(Venta $venta, Comprobante $comprobante): Invoice
    {
        $invoice = new Invoice();

        $invoice->setUblVersion('2.1')
            ->setTipoDoc($comprobante->tipo === 'factura' ? '01' : '03')
            ->setTipoOperacion('0101') // Venta Interna
            ->setSerie($comprobante->serie)
            ->setCorrelativo($comprobante->correlativo)
            ->setFechaEmision(new \DateTime())
            ->setTipoMoneda('PEN')
            ->setCompany($this->getCompany())
            ->setClient($this->getClient($venta))
            ->setFormaPago(new FormaPagoContado()); // Obligatorio para Facturas (error 3244 si falta)

        $totalGravada = 0;
        $totalExonerada = 0;
        $totalIGV = 0;
        $totalValorVenta = 0; // suma de todos los LineExtensionAmount de líneas

        $details = [];
        foreach ($venta->detalles as $det) {
            $item = new SaleDetail();
            $lineTotal = (float)$det->subtotal;
            $cantidad = (float)$det->cantidad;
            $afectoIgv = $det->producto->afecto_igv;

            if ($afectoIgv) {
                // Gravado (10)
                // valorUnitario redondeado a 2 decimales para que el XML tenga
                // PriceAmount con 2 decimales y SUNAT valide: TaxableAmount = Qty * PriceAmount
                $valorUnitario = round((float)$det->precio_unitario / 1.18, 2);
                $baseLine = round($valorUnitario * $cantidad, 2);
                $igvLine = round($lineTotal - $baseLine, 2);

                $item->setTipAfeIgv('10')
                    ->setMtoBaseIgv($baseLine)
                    ->setIgv($igvLine)
                    ->setPorcentajeIgv(18.0)
                    ->setTotalImpuestos($igvLine)
                    ->setMtoValorUnitario($valorUnitario)
                    ->setMtoValorVenta($baseLine); // LineExtensionAmount de la línea

                $totalGravada += $baseLine;
                $totalIGV += $igvLine;
                $totalValorVenta += $baseLine;
            } else {
                // Exonerado (20)
                $item->setTipAfeIgv('20')
                    ->setMtoBaseIgv($lineTotal)
                    ->setIgv(0)
                    ->setPorcentajeIgv(0.0)
                    ->setTotalImpuestos(0)
                    ->setMtoValorUnitario((float)$det->precio_unitario)
                    ->setMtoValorVenta($lineTotal); // LineExtensionAmount de la línea

                $totalExonerada += $lineTotal;
                $totalValorVenta += $lineTotal;
            }

            $item->setCodProducto($det->producto->id)
                ->setUnidad('NIU')
                ->setCantidad($cantidad)
                ->setDescripcion($det->producto->nombre)
                ->setMtoPrecioUnitario((float)$det->precio_unitario);

            $details[] = $item;
        }

        // Solo incluir TaxSubtotals por los tipos de operación que apliquen
        if ($totalGravada > 0) {
            $invoice->setMtoOperGravadas($totalGravada)
                ->setMtoIGV($totalIGV);
        }
        if ($totalExonerada > 0) {
            $invoice->setMtoOperExoneradas($totalExonerada);
        }

        $invoice->setTotalImpuestos($totalIGV)
            ->setValorVenta($totalValorVenta)       // LineExtensionAmount del cabezal (suma de bases)
            ->setSubTotal((float)$venta->total)     // TaxInclusiveAmount = Total Precio de Venta (obligatorio SUNAT)
            ->setMtoImpVenta((float)$venta->total)  // PayableAmount = Importe total a pagar
            ->setDetails($details)
            ->setLegends([
                (new Legend())
                    ->setCode('1000')
                    ->setValue($this->convertirMontoALetras($venta->total))
            ]);

        return $invoice;
    }

    private function mapearVentaANote(Venta $venta, Comprobante $comprobanteNC, string $motivo): Note
    {
        $note = new Note();

        $note->setUblVersion('2.1')
            ->setTipoDoc('07') // Nota de Crédito
            ->setSerie($comprobanteNC->serie)
            ->setCorrelativo($comprobanteNC->correlativo)
            ->setFechaEmision(new \DateTime())
            ->setTipoMoneda('PEN')
            ->setCompany($this->getCompany())
            ->setClient($this->getClient($venta))
            ->setTipDocAfectado($venta->tipo_comprobante === 'factura' ? '01' : '03')
            ->setNumDocfectado($venta->comprobante->numero_comprobante)
            ->setCodMotivo('01') // Anulación de la operación
            ->setDesMotivo($motivo);

        $totalGravada = 0;
        $totalExonerada = 0;
        $totalIGV = 0;
        $totalValorVenta = 0;

        $details = [];
        foreach ($venta->detalles as $det) {
            $item = new SaleDetail();
            $lineTotal = (float)$det->subtotal;
            $cantidad = (float)$det->cantidad;
            $afectoIgv = $det->producto->afecto_igv;

            if ($afectoIgv) {
                // Gravado (10)
                $valorUnitario = round((float)$det->precio_unitario / 1.18, 2);
                $baseLine = round($valorUnitario * $cantidad, 2);
                $igvLine = round($lineTotal - $baseLine, 2);

                $item->setTipAfeIgv('10')
                    ->setMtoBaseIgv($baseLine)
                    ->setIgv($igvLine)
                    ->setPorcentajeIgv(18.0)
                    ->setTotalImpuestos($igvLine)
                    ->setMtoValorUnitario($valorUnitario)
                    ->setMtoValorVenta($baseLine);

                $totalGravada += $baseLine;
                $totalIGV += $igvLine;
                $totalValorVenta += $baseLine;
            } else {
                // Exonerado (20)
                $item->setTipAfeIgv('20')
                    ->setMtoBaseIgv($lineTotal)
                    ->setIgv(0)
                    ->setPorcentajeIgv(0.0)
                    ->setTotalImpuestos(0)
                    ->setMtoValorUnitario((float)$det->precio_unitario)
                    ->setMtoValorVenta($lineTotal);

                $totalExonerada += $lineTotal;
                $totalValorVenta += $lineTotal;
            }

            $item->setCodProducto($det->producto->id)
                ->setUnidad('NIU')
                ->setCantidad($cantidad)
                ->setDescripcion($det->producto->nombre)
                ->setMtoPrecioUnitario((float)$det->precio_unitario);

            $details[] = $item;
        }

        // Solo incluir TaxSubtotals por los tipos que apliquen
        if ($totalGravada > 0) {
            $note->setMtoOperGravadas($totalGravada)
                ->setMtoIGV($totalIGV);
        }
        if ($totalExonerada > 0) {
            $note->setMtoOperExoneradas($totalExonerada);
        }

        $note->setTotalImpuestos($totalIGV)
            ->setValorVenta($totalValorVenta)       // LineExtensionAmount del cabezal
            ->setSubTotal((float)$venta->total)     // TaxInclusiveAmount = Total Precio de Venta
            ->setMtoImpVenta((float)$venta->total)  // PayableAmount
            ->setDetails($details)
            ->setLegends([
                (new Legend())
                    ->setCode('1000')
                    ->setValue($this->convertirMontoALetras($venta->total))
            ]);

        return $note;
    }

    private function getCompany(): Company
    {
        $config = config('facturacion');
        $company = new Company();
        $company->setRuc($config['ruc'])
            ->setRazonSocial($config['razon_social'])
            ->setNombreComercial($config['razon_social'])
            ->setAddress((new Address())
                ->setUbigueo('100101')
                ->setDepartamento('HUANUCO')
                ->setProvincia('HUANUCO')
                ->setDistrito('HUANUCO')
                ->setDireccion($config['direccion']));

        return $company;
    }

    private function getClient(Venta $venta): Client
    {
        $client = new Client();
        if ($venta->cliente_id && $venta->cliente) {
            $tipoDoc = strlen($venta->cliente->numero_documento) === 11 ? '6' : '1';
            $client->setTipoDoc($tipoDoc)
                ->setNumDoc($venta->cliente->numero_documento)
                ->setRznSocial($venta->cliente->nombre_completo);
        } else {
            // Cliente Varios / Consumidor Final
            $client->setTipoDoc('0')
                ->setNumDoc('00000000')
                ->setRznSocial('CLIENTE VARIOS');
        }

        return $client;
    }

    private function extractHash(string $xmlContent): ?string
    {
        preg_match('/<ds:DigestValue>(.*?)<\/ds:DigestValue>/', $xmlContent, $matches);
        return $matches[1] ?? null;
    }

    private function generateQrString(Venta $venta, Comprobante $comprobante, string $hash): string
    {
        $rucEmisor = config('facturacion.ruc');
        $tipoComprobante = match ($comprobante->tipo) {
            'factura' => '01',
            'boleta' => '03',
            'nota_credito' => '07',
            default => '00',
        };

        $serie = $comprobante->serie;
        $correlativo = str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT);
        $igv = number_format($venta->igv, 2, '.', '');
        $total = number_format($venta->total, 2, '.', '');
        $fecha = $venta->created_at->format('Y-m-d');

        $tipoDocReceptor = '0';
        $nroDocReceptor = '00000000';

        if ($venta->cliente) {
            $nroDocReceptor = $venta->cliente->numero_documento;
            $tipoDocReceptor = strlen($nroDocReceptor) === 11 ? '6' : '1';
        }

        // Formato SUNAT: RUC|TIPO|SERIE|CORRELATIVO|IGV|TOTAL|FECHA|TIPO_DOC_RECEPTOR|NRO_DOC_RECEPTOR|HASH|
        return "{$rucEmisor}|{$tipoComprobante}|{$serie}|{$correlativo}|{$igv}|{$total}|{$fecha}|{$tipoDocReceptor}|{$nroDocReceptor}|{$hash}|";
    }

    private function convertirMontoALetras($monto): string
    {
        // Simplificación para el ejemplo
        return "SON " . number_format($monto, 2) . " SOLES";
    }
}
