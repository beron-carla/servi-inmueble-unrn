<?php

namespace Tests\Unit\Services;

use App\Repositories\HistoricoLoginRepository;
use App\Services\LoginThrottleService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class LoginThrottleServiceTest extends CIUnitTestCase
{
    public function testEmailAndIpAreLimitedIndependentlyAtFiveFailures(): void
    {
        $history = new class extends HistoricoLoginRepository {
            public int $emailFailures = 0;

            public int $ipFailures = 0;

            public ?string $emailCutoff = null;

            public ?string $ipCutoff = null;

            public function __construct() {}

            public function contarFallosPorEmailDesde(string $email, string $desde): int
            {
                $this->emailCutoff = $desde;

                return $this->emailFailures;
            }

            public function contarFallosPorIpDesde(string $ip, string $desde): int
            {
                $this->ipCutoff = $desde;

                return $this->ipFailures;
            }
        };
        $service = new LoginThrottleService($history);

        $history->emailFailures = 4;
        $history->ipFailures = 4;
        $this->assertFalse($service->estaBloqueado('ana@example.test', '192.0.2.10'));

        $history->emailFailures = 5;
        $this->assertTrue($service->estaBloqueado('ana@example.test', '192.0.2.10'));

        $history->emailFailures = 0;
        $history->ipFailures = 5;
        $this->assertTrue($service->estaBloqueado('ana@example.test', '192.0.2.10'));

        $expectedEarliest = time() - 901;
        $expectedLatest = time() - 899;
        $this->assertGreaterThanOrEqual($expectedEarliest, strtotime((string) $history->ipCutoff));
        $this->assertLessThanOrEqual($expectedLatest, strtotime((string) $history->ipCutoff));
    }
}
