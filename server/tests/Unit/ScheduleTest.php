<?php

namespace Tests\Unit;

use App\Support\Schedule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    public function test_daily_picks_the_next_time_today_or_tomorrow(): void
    {
        $now = Carbon::parse('2026-10-17 08:30:00', 'UTC');
        $this->assertSame('2026-10-17 09:00', Schedule::next(['09:00', '18:00'], 'daily', [], 'UTC', $now)->format('Y-m-d H:i'));

        $late = Carbon::parse('2026-10-17 19:00:00', 'UTC');
        $this->assertSame('2026-10-18 09:00', Schedule::next(['18:00', '09:00'], 'daily', [], 'UTC', $late)->format('Y-m-d H:i'));
    }

    public function test_time_zone_is_used_and_result_is_utc(): void
    {
        // 09:00 in Tehran (+03:30) = 05:30 UTC
        $now = Carbon::parse('2026-10-17 00:00:00', 'UTC');
        $next = Schedule::next(['09:00'], 'daily', [], 'Asia/Tehran', $now);
        $this->assertSame('2026-10-17 05:30', $next->format('Y-m-d H:i'));
        $this->assertSame('UTC', $next->timezoneName);
    }

    public function test_weekly(): void
    {
        // 2026-10-17 is a Saturday (6). Next Monday (1) is 2026-10-19.
        $now = Carbon::parse('2026-10-17 10:00:00', 'UTC');
        $this->assertSame('2026-10-19 07:00', Schedule::next(['07:00'], 'weekly', [1, 3], 'UTC', $now)->format('Y-m-d H:i'));
    }

    public function test_monthly_day_after_month_end_runs_on_last_day(): void
    {
        $now = Carbon::parse('2026-11-01 00:00:00', 'UTC');
        $this->assertSame('2026-11-30 12:00', Schedule::next(['12:00'], 'monthly', [31], 'UTC', $now)->format('Y-m-d H:i'));
    }

    public function test_empty_schedule_gives_null(): void
    {
        $now = Carbon::now();
        $this->assertNull(Schedule::next([], 'daily', [], 'UTC', $now));
        $this->assertNull(Schedule::next(['09:00'], 'weekly', [], 'UTC', $now));
    }
}
