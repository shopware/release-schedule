<?php

// Run with: php tests/ReleaseScheduleTest.php
require __DIR__ . '/../src/Service/ReleaseSchedule.php';

use Shopware\ReleaseSchedule\Service\ReleaseSchedule;

date_default_timezone_set('Europe/Berlin');

// Local generation does not require the GitHub client or network access.
$reflection = new ReflectionClass(ReleaseSchedule::class);
$schedule = $reflection->newInstanceWithoutConstructor();
$svg = $schedule->generateReleaseCalendar(__DIR__ . '/releases.json');
$releases = json_decode(file_get_contents(__DIR__ . '/releases.json'), true);
$byVersion = array_column($releases, null, 'version');
$period = $reflection->getMethod('getSchedulePeriod')->invoke($schedule);
$scale = 700 / ($period->getEndDate()->getTimestamp() - $period->getStartDate()->getTimestamp());

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function maintainedWidth(string $svg, string $version): float
{
    preg_match('/<rect class="stable" data-release-version="' . preg_quote($version, '/') . '"[^>]* width="([^"]+)"/', $svg, $match);
    check(isset($match[1]), 'Missing maintained bar for ' . $version);
    return (float) $match[1];
}

function checkDuration(string $svg, string $version, string $start, string $end, float $scale): void
{
    $expected = (strtotime($end) - strtotime($start)) * $scale;
    check(abs(maintainedWidth($svg, $version) - $expected) < 0.000001, 'Wrong maintained period for ' . $version);
}

function checkSegmentStart(string $svg, string $version, string $class, string $start, int $periodStart, float $scale): void
{
    preg_match('/<rect class="' . $class . '" data-release-version="' . preg_quote($version, '/') . '" x="([^"]+)"/', $svg, $match);
    check(isset($match[1]), 'Missing ' . $class . ' bar for ' . $version);
    $expected = ((strtotime($start) - $periodStart) * $scale) + 100;
    check(abs((float) $match[1] - $expected) < 0.000001, 'Wrong ' . $class . ' start for ' . $version);
}

checkDuration($svg, '6.4', '2021-05-04', '2023-05-03', $scale);
checkDuration($svg, '6.5', '2023-05-03', '2024-03-21', $scale);
checkDuration($svg, '6.6', '2024-03-21', '2025-06-17', $scale);
checkDuration($svg, '6.7', '2025-06-17', '2027-06-01', $scale);
checkDuration($svg, '6.6.9.0', '2024-12-03', '2025-02-03', $scale);
checkDuration($svg, '6.7.15.0', '2026-10-05', '2026-11-05', $scale);
checkSegmentStart($svg, '6.5', 'lts', '2024-03-21', $period->getStartDate()->getTimestamp(), $scale);
checkSegmentStart($svg, '6.6', 'lts', '2025-06-17', $period->getStartDate()->getTimestamp(), $scale);
checkSegmentStart($svg, '6.7', 'lts', '2027-06-01', $period->getStartDate()->getTimestamp(), $scale);

$openEndWidth = ($period->getEndDate()->getTimestamp() - strtotime('2027-06-01')) * $scale;
check(abs(maintainedWidth($svg, '6.8') - $openEndWidth) < 0.000001, 'Unknown branch boundary must use the viewport edge');

foreach (['calendar-years', 'calendar-legend', 'branch-6-4', 'branch-6-5', 'branch-6-6', 'branch-6-7', 'branch-6-8', 'minors-6-5', 'minors-6-6', 'minors-6-7', 'minors-6-8'] as $id) {
    check(str_contains($svg, '<view id="' . $id . '"'), 'Missing SVG view ' . $id);
}
check(str_contains($svg, 'id="minors-6-7" viewBox="0 '), 'Minor view must have an explicit viewBox');
check(!preg_match('/class="(?:lts|security)" data-release-version="6\.7\.15\.0"/', $svg), 'Minors must not duplicate the branch support bars');
check(!preg_match('/width="-/', $svg), 'SVG contains a negative width');
check(str_contains($svg, 'viewBox="0 0 800 360"'), 'Overview must contain only branch rows');

$label = $reflection->getMethod('getBranchRowsSVG')->invoke(
    $schedule,
    [$byVersion['6.7.15.0']],
    $period,
    $scale,
    'November 6, 2026',
    true
);
check(str_contains($label, 'class="neutral branch-labels" data-label-version="6.7.15.0"'), 'A superseded minor must not be labeled LTS or EOL');

$branches = array_values(array_filter($releases, fn (array $release) => ($release['type'] ?? null) === 'branch'));
foreach ([
    'May 31, 2027' => ['6.5' => 'security', '6.6' => 'lts', '6.7' => 'stable', '6.8' => 'unstable'],
    'June 1, 2027' => ['6.5' => 'eol', '6.6' => 'security', '6.7' => 'lts', '6.8' => 'stable'],
    'June 2, 2027' => ['6.5' => 'eol', '6.6' => 'security', '6.7' => 'lts', '6.8' => 'stable'],
    'June 1, 2029' => ['6.5' => 'eol', '6.6' => 'eol', '6.7' => 'security'],
] as $date => $expectedLabels) {
    $labels = $reflection->getMethod('getBranchRowsSVG')->invoke($schedule, $branches, $period, $scale, $date);
    foreach ($expectedLabels as $version => $class) {
        check(
            str_contains($labels, 'class="' . $class . ' branch-labels" data-label-version="' . $version . '"'),
            'Wrong branch label for ' . $version . ' on ' . $date
        );
    }
}

$legacy = tempnam(sys_get_temp_dir(), 'release-schedule-');
file_put_contents($legacy, json_encode([
    ['version' => '6.7.15.0', 'release_date' => '2026-10-05', 'extended_eol' => false, 'security_eol' => '2029-06-01'],
    ['version' => '6.8.0.0', 'release_date' => '2027-06-01', 'extended_eol' => false, 'security_eol' => '2031-06-01'],
]));
try {
    $legacySvg = $schedule->generateReleaseCalendar($legacy);
    check(!str_contains($legacySvg, '<view '), 'Legacy data must retain the existing renderer');
    check(str_contains($legacySvg, 'viewBox="0 0 800 240"'), 'Legacy row count changed');
} finally {
    unlink($legacy);
}

echo "ReleaseSchedule regression checks passed.\n";

