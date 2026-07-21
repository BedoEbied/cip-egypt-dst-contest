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
// Mirrors Timesheet::setClockInInfo — captures "now" as local wall string
// ---------------------------------------------------------------------------
const TIMESHEET_SAVING_FORMAT = 'Y-m-d H:i';

function cip_capture_clock_in_local_string()
{
    return date(TIMESHEET_SAVING_FORMAT);
}

// ---------------------------------------------------------------------------
// Mirrors clockPortalRecord::_set — stores timestamp/time columns as GMT
// ---------------------------------------------------------------------------
function cip_store_as_gmt($localWallString)
{
    $time = strtotime($localWallString);
    if (!$time) {
        return null;
    }
    // "save the time with GMT timezone"
    return gmdate('Y-m-d H:i:s', $time);
}

// ---------------------------------------------------------------------------
// Mirrors clockPortalRecord::convertTimeToCurrentTimezone
// ---------------------------------------------------------------------------
function cip_convert_gmt_string_to_current_timezone($gmtValueFromDb)
{
    $time = strtotime($gmtValueFromDb);
    $value = date('Y-m-d H:i:s', $time);
    $timezone = date_default_timezone_get();
    $dateTimeObject = new DateTime($value, new DateTimeZone('GMT'));
    $dateTimeObject->setTimezone(new DateTimeZone($timezone));
    return $dateTimeObject;
}

// ---------------------------------------------------------------------------
// Mirrors Timesheet::getClockInView
// ---------------------------------------------------------------------------
function cip_get_clock_in_view($gmtValueFromDb, $format = TIMESHEET_SAVING_FORMAT)
{
    $dt = cip_convert_gmt_string_to_current_timezone($gmtValueFromDb);
    return $dt->format($format);
}

// ---------------------------------------------------------------------------
// Mirrors CIPCalendar.js calcTZDate — fixed numeric hour offset (JS port)
// ---------------------------------------------------------------------------
function cip_calc_tz_date_from_offset($utcTimestamp, $offsetHours)
{
    // JS: utc = d.getTime() + (d.getTimezoneOffset()*60000)
    //     nd  = new Date(utc + (3600000 * offset))
    return $utcTimestamp + (int) round($offsetHours * 3600);
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
