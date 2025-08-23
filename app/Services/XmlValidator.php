<?php

namespace App\Services;

use DOMDocument;
use Exception;

class XmlValidator
{
    public static function validateComprobante(string $xml, string $type = "factura", string $version = "2.1.0"): bool
    {
        try {
            $dom = new DOMDocument();
            $dom->loadXML($xml);

            $xsdPath = base_path("app/Services/xsd/{$type}_V{$version}.xsd");

            if (!file_exists($xsdPath)) {
                throw new Exception("No se encontró el esquema XSD para el tipo '{$type}' y versión '{$version}'.");
            }

            if (!$dom->schemaValidate($xsdPath)) {
                throw new Exception("El XML no es válido según el esquema XSD.");
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error al validar el XML: " . $e->getMessage());
        }
    }

    public static function validateATS(string $xml): bool
    {
        try {
            $dom = new DOMDocument();
            $dom->loadXML($xml);

            $xsdPath = base_path("app/Services/xsd/ats.xsd");

            if (!file_exists($xsdPath)) {
                throw new Exception("No se encontró el esquema XSD para el ATS.");
            }

            if (!$dom->schemaValidate($xsdPath)) {
                throw new Exception("El XML no es válido según el esquema XSD del ATS.");
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error al validar el XML del ATS: " . $e->getMessage());
        }
    }
}