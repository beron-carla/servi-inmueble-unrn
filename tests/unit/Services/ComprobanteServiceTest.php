<?php

namespace Tests\Unit\Services;

use App\Services\ComprobanteService;
use CodeIgniter\Test\CIUnitTestCase;

final class ComprobanteServiceTest extends CIUnitTestCase
{
    public function testPdfIsAcceptedByContent(): void
    {
        $archivo = [
            'tmp_name' => '',
            'name' => 'archivo.sin-extension',
            'size' => 11,
            'contenido' => "%PDF-1.4\n%%EOF",
        ];

        $resultado = (new ComprobanteService())->validar($archivo, 'transferencia');

        $this->assertNotNull($resultado);
        $this->assertSame('application/pdf', $resultado['mime']);
    }

    public function testDeceptiveExtensionWithTextIsRejected(): void
    {
        $resultado = (new ComprobanteService())->validar([
            'tmp_name' => '',
            'name' => 'comprobante.pdf',
            'size' => 10,
            'contenido' => 'texto plano',
        ], 'transferencia');

        $this->assertNull($resultado);
    }

    public function testOversizedFileIsRejected(): void
    {
        $resultado = (new ComprobanteService())->validar([
            'tmp_name' => '',
            'name' => 'comprobante.pdf',
            'size' => ComprobanteService::MAXIMO_BYTES + 1,
            'contenido' => "%PDF-1.4\n%%EOF",
        ], 'transferencia');

        $this->assertNull($resultado);
    }

    public function testCashWithoutFileIsAccepted(): void
    {
        $this->assertNull(
            (new ComprobanteService())->validar(null, 'efectivo'),
        );
    }
}
