<?php

namespace App\Services;

use App\Models\Comprobante;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Models\WithholdingVoucher;
use App\Services\XmlValidator;
use Illuminate\Support\Facades\Auth;
use DOMDocument;

class AnexoTransaccionalService
{
    /**
     * Generate the XML for the ATS report.
     *
     * @param int $year
     * @param int $month
     * @return string
     */
    public function generarXml(int $year, int $month): string
    {
        $user = Auth::user();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $iva = $dom->createElement('iva');
        $iva->setAttribute('version', '1.0.0');
        $dom->appendChild($iva);

        // Header
        $this->appendHeader($dom, $iva, $user, $year, $month);

        // Ventas
        $this->appendVentas($dom, $iva, $user, $year, $month);

        // Compras
        $this->appendCompras($dom, $iva, $user, $year, $month);

        // Retenciones
        $this->appendRetenciones($dom, $iva, $user, $year, $month);

        $xml = $dom->saveXML();

        XmlValidator::validateATS($xml);

        return $xml;
    }

    private function appendHeader(DOMDocument $dom, \DOMElement $iva, User $user, int $year, int $month)
    {
        $iva->appendChild($dom->createElement('TipoIDInformante', 'R'));
        $iva->appendChild($dom->createElement('IdInformante', $user->ruc));
        $iva->appendChild($dom->createElement('razonSocial', $user->razonSocial));
        $iva->appendChild($dom->createElement('Anio', $year));
        $iva->appendChild($dom->createElement('Mes', str_pad($month, 2, '0', STR_PAD_LEFT)));
        $iva->appendChild($dom->createElement('numEstabRuc', str_pad($user->establecimientos()->count(), 3, '0', STR_PAD_LEFT)));

        $totalVentas = Comprobante::where('user_id', $user->id)
            ->where('tipo_comprobante', 1) // 1 = Factura
            ->whereYear('fecha_emision', $year)
            ->whereMonth('fecha_emision', $month)
            ->get()
            ->sum(function ($comprobante) {
                return $comprobante->payload['totalSinImpuestos'];
            });

        $iva->appendChild($dom->createElement('totalVentas', number_format($totalVentas, 2, '.', '')));
        $iva->appendChild($dom->createElement('codigoOperativo', 'IVA'));
    }

    private function appendVentas(DOMDocument $dom, \DOMElement $iva, User $user, int $year, int $month)
    {
        $ventas = $dom->createElement('ventas');
        $iva->appendChild($ventas);

        $detalleVentas = $dom->createElement('detalleVentas');
        $ventas->appendChild($detalleVentas);

        $comprobantes = Comprobante::where('user_id', $user->id)
            ->where('tipo_comprobante', 1) // 1 = Factura
            ->whereYear('fecha_emision', $year)
            ->whereMonth('fecha_emision', $month)
            ->get();

        foreach ($comprobantes as $comprobante) {
            $venta = $dom->createElement('venta');
            $detalleVentas->appendChild($venta);

            $payload = $comprobante->payload;

            $venta->appendChild($dom->createElement('tpIdCliente', $payload['tipoIdentificacionComprador']));
            $venta->appendChild($dom->createElement('idCliente', $payload['identificacionComprador']));
            $venta->appendChild($dom->createElement('parteRel', 'NO')); // Assuming no related parts for now
            $venta->appendChild($dom->createElement('tipoComprobante', '01')); // 01 = Factura
            $venta->appendChild($dom->createElement('establecimiento', $comprobante->establecimiento));
            $venta->appendChild($dom->createElement('puntoEmision', $comprobante->punto_emision));
            $venta->appendChild($dom->createElement('secuencial', $comprobante->secuencial));
            $venta->appendChild($dom->createElement('fechaEmision', $comprobante->fecha_emision->format('d/m/Y')));
            $venta->appendChild($dom->createElement('autorizacion', $comprobante->clave_acceso));

            $baseNoGraIva = 0;
            $baseImponible = 0;
            $baseImpGrav = 0;
            $montoIva = 0;

            foreach ($payload['totalConImpuestos'] as $impuesto) {
                if ($impuesto['codigo'] == 2) { // IVA
                    if ($impuesto['codigoPorcentaje'] == 0) { // 0%
                        $baseImponible += $impuesto['baseImponible'];
                    } elseif ($impuesto['codigoPorcentaje'] == 2 || $impuesto['codigoPorcentaje'] == 3) { // 12% or 15%
                        $baseImpGrav += $impuesto['baseImponible'];
                        $montoIva += $impuesto['valor'];
                    }
                }
            }

            $venta->appendChild($dom->createElement('baseNoGraIva', number_format($baseNoGraIva, 2, '.', '')));
            $venta->appendChild($dom->createElement('baseImponible', number_format($baseImponible, 2, '.', '')));
            $venta->appendChild($dom->createElement('baseImpGrav', number_format($baseImpGrav, 2, '.', '')));
            $venta->appendChild($dom->createElement('montoIce', '0.00')); // Assuming no ICE for now
            $venta->appendChild($dom->createElement('montoIva', number_format($montoIva, 2, '.', '')));
            $venta->appendChild($dom->createElement('valorRetIva', '0.00')); // Assuming no retentions for now
            $venta->appendChild($dom->createElement('valorRetRenta', '0.00')); // Assuming no retentions for now
        }
    }

    private function appendCompras(DOMDocument $dom, \DOMElement $iva, User $user, int $year, int $month)
    {
        $comprasNode = $dom->createElement('compras');
        $iva->appendChild($comprasNode);

        $detalleCompras = $dom->createElement('detalleCompras');
        $comprasNode->appendChild($detalleCompras);

        $compras = PurchaseInvoice::where('user_id', $user->id)
            ->whereYear('fecha_emision', $year)
            ->whereMonth('fecha_emision', $month)
            ->get();

        foreach ($compras as $compra) {
            $compraNode = $dom->createElement('compra');
            $detalleCompras->appendChild($compraNode);

            $compraNode->appendChild($dom->createElement('codSustento', $compra->cod_sustento));
            $compraNode->appendChild($dom->createElement('tpIdProv', $compra->supplier->tipo_id));
            $compraNode->appendChild($dom->createElement('idProv', $compra->supplier->identificacion));
            $compraNode->appendChild($dom->createElement('razonSocialProv', $compra->supplier->razon_social));
            $compraNode->appendChild($dom->createElement('tipoComprobante', $compra->tipo_comprobante));
            $compraNode->appendChild($dom->createElement('parteRel', $compra->parte_relacionada));
            $compraNode->appendChild($dom->createElement('fechaRegistro', $compra->fecha_registro->format('d/m/Y')));
            $compraNode->appendChild($dom->createElement('establecimiento', $compra->establecimiento));
            $compraNode->appendChild($dom->createElement('puntoEmision', $compra->punto_emision));
            $compraNode->appendChild($dom->createElement('secuencial', $compra->secuencial));
            $compraNode->appendChild($dom->createElement('fechaEmision', $compra->fecha_emision->format('d/m/Y')));
            $compraNode->appendChild($dom->createElement('autorizacion', $compra->autorizacion));

            $baseNoGraIva = 0;
            $baseImponible = 0;
            $baseImpGrav = 0;
            $montoIva = 0;
            $montoIce = 0;

            foreach ($compra->taxes as $tax) {
                if ($tax->codigo_impuesto == 2) { // IVA
                    if ($tax->codigo_porcentaje == 0) { // 0%
                        $baseImponible += $tax->base_imponible;
                    } else { // Other IVA rates
                        $baseImpGrav += $tax->base_imponible;
                        $montoIva += $tax->valor;
                    }
                } elseif ($tax->codigo_impuesto == 3) { // ICE
                    $montoIce += $tax->valor;
                }
            }

            $compraNode->appendChild($dom->createElement('baseNoGraIva', number_format($baseNoGraIva, 2, '.', '')));
            $compraNode->appendChild($dom->createElement('baseImponible', number_format($baseImponible, 2, '.', '')));
            $compraNode->appendChild($dom->createElement('baseImpGrav', number_format($baseImpGrav, 2, '.', '')));
            $compraNode->appendChild($dom->createElement('montoIce', number_format($montoIce, 2, '.', '')));
            $compraNode->appendChild($dom->createElement('montoIva', number_format($montoIva, 2, '.', '')));
            $compraNode->appendChild($dom->createElement('valorRetBienes', '0.00')); // Assuming no retentions for now
            $compraNode->appendChild($dom->createElement('valorRetServicios', '0.00')); // Assuming no retentions for now
            $compraNode->appendChild($dom->createElement('valRetServ100', '0.00')); // Assuming no retentions for now

            $formasDePagoNode = $dom->createElement('formasDePago');
            $compraNode->appendChild($formasDePagoNode);
            foreach ($compra->payments as $payment) {
                $formasDePagoNode->appendChild($dom->createElement('formaPago', $payment->forma_pago));
            }
        }
    }

    private function appendRetenciones(DOMDocument $dom, \DOMElement $iva, User $user, int $year, int $month)
    {
        $retencionesNode = $dom->createElement('retenciones');
        $iva->appendChild($retencionesNode);

        $detalleRetenciones = $dom->createElement('detalleRetenciones');
        $retencionesNode->appendChild($detalleRetenciones);

        $retenciones = WithholdingVoucher::where('user_id', $user->id)
            ->whereYear('fecha_emision', $year)
            ->whereMonth('fecha_emision', $month)
            ->get();

        foreach ($retenciones as $retencion) {
            $retencionNode = $dom->createElement('retencion');
            $detalleRetenciones->appendChild($retencionNode);

            $retencionNode->appendChild($dom->createElement('tpIdCliente', $retencion->supplier->tipo_id));
            $retencionNode->appendChild($dom->createElement('idCliente', $retencion->supplier->identificacion));
            $retencionNode->appendChild($dom->createElement('razonSocial', $retencion->supplier->razon_social));
            $retencionNode->appendChild($dom->createElement('estabRetencion1', $retencion->establecimiento));
            $retencionNode->appendChild($dom->createElement('ptoEmiRetencion1', $retencion->punto_emision));
            $retencionNode->appendChild($dom->createElement('secRetencion1', $retencion->secuencial));
            $retencionNode->appendChild($dom->createElement('autRetencion1', $retencion->autorizacion));
            $retencionNode->appendChild($dom->createElement('fechaEmisionRet1', $retencion->fecha_emision->format('d/m/Y')));

            $detalleAir = $dom->createElement('detalleAir');
            $retencionNode->appendChild($detalleAir);
            foreach ($retencion->incomeLines as $line) {
                $detalleAir->appendChild($dom->createElement('codRetAir', $line->cod_ret_air));
                $detalleAir->appendChild($dom->createElement('baseImpAir', number_format($line->base_imponible, 2, '.', '')));
                $detalleAir->appendChild($dom->createElement('porcentajeAir', number_format($line->porcentaje, 2, '.', '')));
                $detalleAir->appendChild($dom->createElement('valRetAir', number_format($line->valor, 2, '.', '')));
            }

            if ($retencion->purchaseInvoice) {
                $retencionNode->appendChild($dom->createElement('estabFactura', $retencion->purchaseInvoice->establecimiento));
                $retencionNode->appendChild($dom->createElement('ptoEmiFactura', $retencion->purchaseInvoice->punto_emision));
                $retencionNode->appendChild($dom->createElement('secFactura', $retencion->purchaseInvoice->secuencial));
                $retencionNode->appendChild($dom->createElement('autFactura', $retencion->purchaseInvoice->autorizacion));
                $retencionNode->appendChild($dom->createElement('fechaEmisionFactura', $retencion->purchaseInvoice->fecha_emision->format('d/m/Y')));
            }

            $retIva = $dom->createElement('retIva');
            $retencionNode->appendChild($retIva);
            foreach ($retencion->vatLines as $line) {
                $retIva->appendChild($dom->createElement('codRetIva', $line->cod_ret_iva));
                $retIva->appendChild($dom->createElement('baseImpIva', number_format($line->base_imponible, 2, '.', '')));
                $retIva->appendChild($dom->createElement('porcentajeRetIva', number_format($line->porcentaje, 2, '.', '')));
                $retIva->appendChild($dom->createElement('valRetIva', number_format($line->valor, 2, '.', '')));
            }
        }
    }
}
