<?php

namespace canisunit\library\helpers;

use canis\helpers\Date;
use canisunit\TestCase;

/**
 * @group helpers
 */
class DateTest extends TestCase
{
    public function testTime()
    {
        Date::now("24 hours");
        $this->assertEquals(strtotime("+24 hours"), Date::time());
        Date::now(null);
    }

    public function testInPast()
    {
        Date::now(null);
        $this->assertEquals(true, Date::inPast("yesterday"));
        $this->assertEquals(false, Date::inPast("tomorrow"));

        $t1 = strtotime("yesterday");
        $t2 = strtotime("tomorrow");
        $t3 = time();

        $this->assertEquals(true, Date::inPast($t1));
        $this->assertEquals(false, Date::inPast($t2));
        $this->assertEquals(false, Date::inPast($t3));

        Date::now(strtotime("July 1, 2012 12:00:00"));
        $this->assertEquals(true, Date::inPast("June 30, 2012"));
        $this->assertEquals(true, Date::inPast("July 1, 2012 11:59:00"));
        $this->assertEquals(false, Date::inPast("July 1, 2012 12:00:00"));
        $this->assertEquals(false, Date::inPast("July 1, 2012 12:00:01"));
        Date::now(null);
    }

    public function testInFuture()
    {
        Date::now(null);
        $this->assertEquals(false, Date::inFuture("yesterday"));
        $this->assertEquals(true, Date::inFuture("tomorrow"));

        $t1 = strtotime("yesterday");
        $t2 = strtotime("tomorrow");
        $t3 = time();

        $this->assertEquals(false, Date::inFuture($t1));
        $this->assertEquals(true, Date::inFuture($t2));
        $this->assertEquals(false, Date::inFuture($t3));

        Date::now(strtotime("July 1, 2012 12:00:00"));
        $this->assertEquals(false, Date::inFuture("June 30, 2012"));
        $this->assertEquals(false, Date::inFuture("July 1, 2012 11:59:00"));
        $this->assertEquals(false, Date::inFuture("July 1, 2012 12:00:00"));
        $this->assertEquals(true, Date::inFuture("July 1, 2012 12:00:01"));
        Date::now(null);
    }

    public function testIsPresent()
    {
        Date::now(null);
        $this->assertEquals(false, Date::isPresent("yesterday"));
        $this->assertEquals(false, Date::isPresent("tomorrow"));
        $this->assertEquals(true, Date::isPresent("now"));

        $t1 = strtotime("yesterday");
        $t2 = strtotime("tomorrow");
        $t3 = time();

        $this->assertEquals(false, Date::isPresent($t1));
        $this->assertEquals(false, Date::isPresent($t2));
        $this->assertEquals(true, Date::isPresent($t3));

        Date::now(strtotime("July 1, 2012 12:00:00"));
        $this->assertEquals(false, Date::isPresent("June 30, 2012"));
        $this->assertEquals(false, Date::isPresent("July 1, 2012 11:59:00"));
        $this->assertEquals(true, Date::isPresent("July 1, 2012 12:00:00"));
        $this->assertEquals(false, Date::isPresent("July 1, 2012 12:00:01"));
        Date::now(null);
    }

    public function testRelativeDate()
    {
        Date::now(null);
        $t = strtotime("50 hours ago");
        $this->assertEquals(date('F j, Y \a\t g:i A', $t), Date::relativeDate($t));

        $nd = strtotime("July 1, 2012 22:00:00");
        Date::now($nd);

        $this->assertEquals("now", Date::relativeDate($nd));

        $t = strtotime("1210 minutes ago", $nd);
        $this->assertEquals("20 hours ago", Date::relativeDate($t));

        $t = strtotime("80 minutes ago", $nd);
        $this->assertEquals("1 hour ago", Date::relativeDate($t));

        $t = strtotime("89 minutes ago", $nd);
        $this->assertEquals("1 hour ago", Date::relativeDate($t));

        $t = strtotime("90 minutes ago", $nd);
        $this->assertEquals("2 hours ago", Date::relativeDate($t));

        $t = strtotime("24 hours ago", $nd);
        $this->assertEquals("yesterday", Date::relativeDate($t));

        $t = strtotime("tomorrow", $nd);
        $this->assertEquals("tomorrow", Date::relativeDate($t));

        $t = strtotime("+12 hours", $nd);

        $this->assertEquals("tomorrow at " . Date::date("g:iA", $t), Date::relativeDate($t, null, true));

        $t = strtotime("+30 seconds", $nd);
        $this->assertEquals("in 30 seconds", Date::relativeDate($t));

        $t = strtotime("+30 minutes", $nd);
        $this->assertEquals("in 30 minutes", Date::relativeDate($t));
        Date::now(null);
    }
}
