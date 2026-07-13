<?php

namespace Tests\Unit;

use App\Services\Ticketing\TicketTaskSupport;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class TicketTaskSupportTest extends TestCase
{
    public function test_number_uses_prefix_date_and_sequence(): void
    {
        $date = CarbonImmutable::parse('2026-07-13 10:00:00');

        $this->assertSame('TCK-20260713-001', TicketTaskSupport::number('TCK', $date, 1));
        $this->assertSame('TSK-20260713-042', TicketTaskSupport::number('TSK', $date, 42));
    }

    public function test_overdue_requires_past_due_date_and_non_final_status(): void
    {
        $now = CarbonImmutable::parse('2026-07-13 10:00:00');

        $this->assertTrue(TicketTaskSupport::isOverdue($now->subMinute(), false, $now));
        $this->assertFalse(TicketTaskSupport::isOverdue($now->addMinute(), false, $now));
        $this->assertFalse(TicketTaskSupport::isOverdue($now->subMinute(), true, $now));
        $this->assertFalse(TicketTaskSupport::isOverdue(null, false, $now));
    }
}
