<?php

namespace Tests\Feature\Foundation;

use App\Support\Format;
use Tests\TestCase;

class FormatTimeUntilTest extends TestCase
{
    public function test_it_uses_minutes_hours_or_whole_days(): void
    {
        $this->travelTo(now()->setTime(12, 0));

        $this->assertSame('In 1 minute', Format::timeUntil(now()->addMinute()));
        $this->assertSame('In 45 minutes', Format::timeUntil(now()->addMinutes(45)));
        $this->assertSame('In 1 hour', Format::timeUntil(now()->addMinutes(90)));
        $this->assertSame('In 20 hours', Format::timeUntil(now()->addHours(20)->addMinutes(5)));
        $this->assertSame('In 1 day', Format::timeUntil(now()->addHours(35)));
        $this->assertSame('In 2 days', Format::timeUntil(now()->addHours(69)));
    }
}
