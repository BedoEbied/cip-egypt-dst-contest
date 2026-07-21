<?php
/**
 * Quick reproduction (run: php reproduce.php)
 *
 * Scenario: Wall clock in Cairo summer is 09:00 (UTC+3).
 * Stale CIP timezone rules treat Cairo as UTC+2 ("regular"/winter time).
 * Observed: CIP shows/logs 08:00.
 */

require __DIR__ . '/isolated-bug.php';

echo "=== CIP Egypt summer-time bug reproduction ===\n\n";

$cairo = new ContestTimezone(48, '(GMT+02:00) Cairo', 'Africa/Cairo');
echo "DB label: {$cairo->name}\n";
echo "IANA code: {$cairo->code}\n";
echo "Parsed label value: {$cairo->getTimezoneValue()}\n\n";

// True instant: 2026-07-20 06:00:00 UTC == 09:00 Cairo summer (EEST, +3)
$utcInstant = new DateTime('2026-07-20 06:00:00', new DateTimeZone('UTC'));
echo "True UTC instant: " . $utcInstant->format('Y-m-d H:i:s') . " UTC\n";
echo "True Cairo summer wall: " . $utcInstant->setTimezone(new DateTimeZone('Africa/Cairo'))->format('Y-m-d H:i:s T') . "\n\n";

// --- Path A: modern PHP with correct Africa/Cairo (baseline, should be OK) ---
date_default_timezone_set('Africa/Cairo');
// Freeze "now" by injecting the same local capture logic against a known string:
$localModern = (new DateTime('2026-07-20 06:00:00', new DateTimeZone('UTC')))
    ->setTimezone(new DateTimeZone('Africa/Cairo'))
    ->format('Y-m-d H:i');
$storedModern = cip_store_as_gmt($localModern);
$viewModern = cip_get_clock_in_view($storedModern);
echo "[Modern tzdata Africa/Cairo]\n";
echo "  capture local: $localModern\n";
echo "  stored GMT:    $storedModern\n";
echo "  displayed:     $viewModern\n\n";

// --- Path B: stale fixed +2 (matches reported CIP production symptom) ---
cip_simulate_stale_cairo_fixed_plus2();
$localStale = (new DateTime('2026-07-20 06:00:00', new DateTimeZone('UTC')))
    ->setTimezone(new DateTimeZone('Etc/GMT-2'))
    ->format('Y-m-d H:i');
$storedStale = cip_store_as_gmt($localStale);
$viewStale = cip_get_clock_in_view($storedStale);
echo "[Stale fixed UTC+2 — CIP summer symptom]\n";
echo "  capture local: $localStale   <-- wall was 09:00, CIP thinks 08:00\n";
echo "  stored GMT:    $storedStale\n";
echo "  displayed:     $viewStale    <-- shows 08:00 instead of 09:00\n\n";

echo "Timezone::getOffset() under stale TZ: " . (new DateTimeZone(date_default_timezone_get()))->getOffset(new DateTime('now')) / 3600 . "\n";
echo "JS calcTZDate-style numeric offset path would also use that fixed +2.\n";
