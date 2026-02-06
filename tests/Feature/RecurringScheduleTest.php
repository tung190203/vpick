<?php

namespace Tests\Feature;

use App\Enums\RecurringType;
use App\Models\Club\ClubActivity;
use App\Models\Club\Club;
use App\Models\User;
use Tests\TestCase;

class RecurringScheduleTest extends TestCase
{

    /**
     * Test parse recurring date with dd/MM/yyyy format
     */
    public function test_parse_recurring_date_dd_mm_yyyy(): void
    {
        $result = RecurringType::parseRecurringDate('15/02/2026');

        $this->assertNotNull($result);
        $this->assertEquals(15, $result['day']);
        $this->assertEquals(2, $result['month']);
        $this->assertEquals(2026, $result['year']);
    }

    /**
     * Test parse recurring date with dd-MM-yyyy format
     */
    public function test_parse_recurring_date_dd_dash_mm_yyyy(): void
    {
        $result = RecurringType::parseRecurringDate('25-12-2026');

        $this->assertNotNull($result);
        $this->assertEquals(25, $result['day']);
        $this->assertEquals(12, $result['month']);
        $this->assertEquals(2026, $result['year']);
    }

    /**
     * Test parse recurring date with yyyy-MM-dd format
     */
    public function test_parse_recurring_date_yyyy_mm_dd(): void
    {
        $result = RecurringType::parseRecurringDate('2026-10-01');

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['day']);
        $this->assertEquals(10, $result['month']);
        $this->assertEquals(2026, $result['year']);
    }

    /**
     * Test parse invalid date
     */
    public function test_parse_invalid_date(): void
    {
        $this->assertNull(RecurringType::parseRecurringDate('invalid-date'));
        $this->assertNull(RecurringType::parseRecurringDate('32/13/2026'));
    }

    /**
     * Test format recurring date for monthly
     */
    public function test_format_recurring_date_monthly(): void
    {
        $result = RecurringType::formatRecurringDate('monthly', 15);

        $this->assertEquals('ngày 15 hàng tháng', $result);
    }

    /**
     * Test format recurring date for quarterly
     */
    public function test_format_recurring_date_quarterly(): void
    {
        $result = RecurringType::formatRecurringDate('quarterly', 10);

        $this->assertEquals('ngày 10 tháng đầu tiên hàng quý', $result);
    }

    /**
     * Test format recurring date for yearly
     */
    public function test_format_recurring_date_yearly(): void
    {
        $result = RecurringType::formatRecurringDate('yearly', 25, 12);

        $this->assertEquals('ngày 25/12 hàng năm', $result);
    }

    /**
     * Test ClubActivity model isRecurring method
     */
    public function test_club_activity_is_recurring_method(): void
    {
        $activity = new ClubActivity();

        // Test with null
        $activity->recurring_schedule = null;
        $this->assertFalse($activity->isRecurring());

        // Test with schedule
        $activity->recurring_schedule = ['period' => 'weekly'];
        $this->assertTrue($activity->isRecurring());
    }

    /**
     * Test recurring type labels
     */
    public function test_recurring_type_labels(): void
    {
        $this->assertEquals('Hàng tuần', RecurringType::Weekly->label());
        $this->assertEquals('Hàng tháng', RecurringType::Monthly->label());
        $this->assertEquals('Hàng quý', RecurringType::Quarterly->label());
        $this->assertEquals('Hàng năm', RecurringType::Yearly->label());
    }

    /**
     * Test recurring type values
     */
    public function test_recurring_type_values(): void
    {
        $values = RecurringType::values();

        $this->assertContains('weekly', $values);
        $this->assertContains('monthly', $values);
        $this->assertContains('quarterly', $values);
        $this->assertContains('yearly', $values);
    }
}
