<?php
/**
 * Deterministic regression harness (run: php reproduce.php).
 */

require __DIR__ . '/isolated-bug.php';

$passes = 0;
$failures = 0;

function cip_expect_same($label, $expected, $actual)
{
    global $passes, $failures;

    if ($expected === $actual) {
        ++$passes;
        echo "PASS: $label\n";
        return;
    }

    ++$failures;
    echo "FAIL: $label\n";
    echo "  expected: " . var_export($expected, true) . "\n";
    echo "  actual:   " . var_export($actual, true) . "\n";
}

$cairoCode = 'Africa/Cairo';
$utc = new DateTimeZone('UTC');

// A wrong process default must not affect an explicit IANA conversion.
cip_simulate_stale_cairo_fixed_plus2();

$cases = array(
    'winter' => array(
        'utc' => '2026-01-20 06:00:00',
        'local' => '2026-01-20 08:00:00 +02:00',
    ),
    'summer' => array(
        'utc' => '2026-07-20 06:00:00',
        'local' => '2026-07-20 09:00:00 +03:00',
    ),
);

foreach ($cases as $label => $case) {
    $source = new DateTimeImmutable($case['utc'], $utc);
    $instant = cip_capture_clock_in_instant($source);
    $stored = cip_store_as_gmt($instant);
    $view = cip_get_clock_in_view(
        $stored,
        $cairoCode,
        'Y-m-d H:i:s P'
    );

    cip_expect_same("$label stores UTC", $case['utc'], $stored);
    cip_expect_same("$label renders Cairo", $case['local'], $view);
}

cip_expect_same(
    'invalid capture input fails safely',
    null,
    cip_capture_clock_in_instant('not-an-instant')
);
cip_expect_same('invalid storage input fails safely', null, cip_store_as_gmt('bad'));
cip_expect_same(
    'invalid database value fails safely',
    null,
    cip_get_clock_in_view('not-a-date', $cairoCode)
);
cip_expect_same(
    'invalid IANA zone fails safely',
    null,
    cip_get_clock_in_view('2026-07-20 06:00:00', 'Invalid/Zone')
);

echo "\nTimezone database: " . timezone_version_get() . "\n";
echo "Result: $passes passed, $failures failed\n";
exit($failures === 0 ? 0 : 1);
