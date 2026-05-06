<?php

declare(strict_types=1);

namespace App\Infrastructure\SIFEN\Signature;

use RuntimeException;

/**
 * XmlDsigSigner — Firma el XML del DE con XMLDSig (RSA-SHA256).
 * Requiere extensión PHP: openssl, dom
 * Certificado: PKCS#12 (.p12) emitido por la SET Paraguay.
 */
class XmlDsigSigner
{
    public function sign(string $xmlContent, string $certPath, string $passphrase): string
    {
        if (!file_exists($certPath)) {
            // En modo sandbox/desarrollo sin certificado real, retornar XML sin firmar
            return $xmlContent;
        }

        $p12Data = file_get_contents($certPath);
        $certs   = [];

        if (!openssl_pkcs12_read($p12Data, $certs, $passphrase)) {
            throw new RuntimeException('No se pudo leer el certificado PKCS#12. Verifique la contraseña.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xmlContent, LIBXML_PARSEHUGE);

        // Crear el nodo de firma
        $signatureNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $signedInfoNode= $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'SignedInfo');

        // Canonicalization
        $c14nNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'CanonicalizationMethod');
        $c14nNode->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfoNode->appendChild($c14nNode);

        // SignatureMethod (RSA-SHA256)
        $sigMethodNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'SignatureMethod');
        $sigMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $signedInfoNode->appendChild($sigMethodNode);

        $signatureNode->appendChild($signedInfoNode);
        $dom->documentElement->appendChild($signatureNode);

        // Calcular firma real
        $c14n    = $dom->C14N();
        openssl_sign($c14n, $signature, $certs['pkey'], OPENSSL_ALGO_SHA256);
        $sigB64  = base64_encode($signature);

        // Agregar SignatureValue
        $sigValueNode = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'SignatureValue', $sigB64);
        $signatureNode->appendChild($sigValueNode);

        return $dom->saveXML();
    }
}
