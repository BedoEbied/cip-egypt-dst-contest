<?php
/**
 * CIP Contest Isolate — Egypt Summer Time / Clock Display Bug
 *
 * Extracted & simplified from Softxpert CIP (legacy time-tracking portal).
 * These snippets mirror the real clock-in → GMT store → local display path.
 *
 * DO NOT treat this as a drop-in CIP patch file — it is a contest harness.
 */

// ---------------------------------------------------------------------------
// Seed data (from CIP timezone seed)
// ---------------------------------------------------------------------------
// id=48, name='(GMT+02:00) Cairo', code='Africa/Cairo', difference_from_gmt=2
// (difference_from_gmt column was later dropped; the display name remained.)

final class ContestTimezone
{
    public $id;
    public $name;
    public $code;

    public function __construct($id, $name, $code)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
    }

    /** Mirrors Timezone::getOffset() — numeric hours from IANA zone "now". */
    public function getOffset()
    {
        $dateTimeZone = new DateTimeZone($this->code);
        return $dateTimeZone->getOffset(new DateTime('now')) / 3600;
    }

    /** Mirrors Timezone::getTimezoneValue() — parses label left of ')'. */
    public function getTimezoneValue()
    {
        $matches = explode(')', $this->name);
        return trim($matches[0], '(');
    }
}

// ---------------------------------------------------------------------------
// Mirrors CompanyNameFilter — sets PHP default TZ from person/company
// ---------------------------------------------------------------------------
function cip_set_request_timezone($ianaCode)
{
    date_default_timezone_set($ianaCode);
}

// ---------------------------------------------------------------------------
// Mirrors Timesheet::setClockInInfo — captures an instant, not a wall string
// ---------------------------------------------------------------------------
const TIMESHEET_SAVING_FORMAT = 'Y-m-d H:i';
const CIP_GMT_STORAGE_FORMAT = 'Y-m-d H:i:s';

function cip_capture_clock_in_instant($instant = null)
{
    $utc = new DateTimeZone('UTC');

    if ($instant === null) {
        return new DateTimeImmutable('now', $utc);
    }

    if (!($instant instanceof DateTimeInterface)) {
        return null;
    }

    return (new DateTimeImmutable('@' . $instant->getTimestamp()))
        ->setTimezone($utc);
}

// ---------------------------------------------------------------------------
// Mirrors clockPortalRecord::_set — stores timestamp/time columns as GMT
// ---------------------------------------------------------------------------
function cip_store_as_gmt($instant)
{
    $utcInstant = cip_capture_clock_in_instant($instant);
    if ($utcInstant === null) {
        return null;
    }

    return $utcInstant->format(CIP_GMT_STORAGE_FORMAT);
}

// ---------------------------------------------------------------------------
// Mirrors clockPortalRecord::convertTimeToCurrentTimezone
// ---------------------------------------------------------------------------
function cip_convert_gmt_string_to_current_timezone(
    $gmtValueFromDb,
    $ianaCode
) {
    if (!is_string($gmtValueFromDb) || !is_string($ianaCode)) {
        return null;
    }

    try {
        $utc = new DateTimeZone('UTC');
        $targetTimezone = new DateTimeZone($ianaCode);
    } catch (Exception $exception) {
        return null;
    }

    $dateTimeObject = DateTimeImmutable::createFromFormat(
        '!' . CIP_GMT_STORAGE_FORMAT,
        $gmtValueFromDb,
        $utc
    );

    if (
        $dateTimeObject === false
        || $dateTimeObject->format(CIP_GMT_STORAGE_FORMAT) !== $gmtValueFromDb
    ) {
        return null;
    }

    return $dateTimeObject->setTimezone($targetTimezone);
}

// ---------------------------------------------------------------------------
// Mirrors Timesheet::getClockInView
// ---------------------------------------------------------------------------
function cip_get_clock_in_view(
    $gmtValueFromDb,
    $ianaCode,
    $format = TIMESHEET_SAVING_FORMAT
) {
    $dateTime = cip_convert_gmt_string_to_current_timezone(
        $gmtValueFromDb,
        $ianaCode
    );

    return $dateTime === null ? null : $dateTime->format($format);
}

// ---------------------------------------------------------------------------
// IANA-aware replacement for CIPCalendar.js fixed-offset arithmetic
// ---------------------------------------------------------------------------
function cip_calc_tz_date_from_zone($utcTimestamp, $ianaCode)
{
    if (!is_int($utcTimestamp) || !is_string($ianaCode)) {
        return null;
    }

    try {
        $targetTimezone = new DateTimeZone($ianaCode);
    } catch (Exception $exception) {
        return null;
    }

    return (new DateTimeImmutable('@' . $utcTimestamp))
        ->setTimezone($targetTimezone);
}

// ---------------------------------------------------------------------------
// Mirrors DateTimeUtil fragment — static offset map has NO Africa/Cairo
// ---------------------------------------------------------------------------
$CIP_ZONE_LIST = array(
    'Europe/Minsk' => 2.00,   // +2.00 maps here — not Cairo
    'Asia/Kuwait'  => 3.00,   // +3.00 maps here — not Cairo summer
);

// ---------------------------------------------------------------------------
// Reproduction helper: simulate STALE Africa/Cairo (fixed UTC+2 year-round)
// Real Egypt summer (post-2023) is UTC+3. Legacy CIP servers / old tzdata
// often still behaved like fixed +2 — matching the UI label "(GMT+02:00)".
// ---------------------------------------------------------------------------
function cip_simulate_stale_cairo_fixed_plus2()
{
    // Etc/GMT-2 means UTC+2 (POSIX sign inversion). Use as stand-in for
    // outdated Africa/Cairo rules that ignored restored Egypt DST.
    date_default_timezone_set('Etc/GMT-2');
}
