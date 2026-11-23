<?php

namespace Tests\Unit\Services;

use App\Services\NumeroComprobanteService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

final class NumeroComprobanteServiceTest extends CIUnitTestCase
{
    public function testInvalidPaymentDateIsRejected(): void
    {
        $service = new NumeroComprobanteService($this->createMock(BaseConnection::class));

        $this->expectException(InvalidArgumentException::class);
        $service->siguiente('2026-02-30');
    }
}
