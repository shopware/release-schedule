<?php

namespace Shopware\ReleaseSchedule\Service;

use DateInterval;
use DatePeriod;
use DateTime;
use Github\Client;
use Symfony\Component\HttpClient\HttplugClient;

// Ported from: https://gitlab.shopware.com/-/snippets/210
class ReleaseSchedule
{
    private Client $client;

    public function __construct()
    {
        $this->client = Client::createWithHttpClient(new HttplugClient());
    }

    public function generateReleaseCalendar(): string
    {
        $releases = $this->getReleases();

        // Current date for "Today" marker
        $today = date('F j, Y');

        // Total scale of the SVG
        $totalWidth = 800;
        $totalHeight = (count($releases) * 40) + 160;

        // Width of the label sidebar
        $branchLabelWidth = 100;

        $period = $this->getSchedulePeriod();
        $years = $this->getYears($period);

        // Calculate the total width available for the calendar, accounting for space needed by branch labels
        $calendarWidth = $totalWidth - $branchLabelWidth;
        $calendarHeight = count($releases) * 40;

        $calendarScale = $calendarWidth / ($period->getEndDate()->getTimestamp() - $period->getStartDate()->getTimestamp());
        $yearScale = $calendarWidth / count($years);

        $svgStyle = $this->getStyleSVG();
        $yearsSVG = $this->getYearsSVG($years, $yearScale, $calendarHeight, $branchLabelWidth);
        $releasesSVG = $this->getReleasesSVG($releases, $period, $calendarScale, $branchLabelWidth);
        $labelsSVG = $this->getReleaseLabelsSVG($releases, $today);
        $markerSVG = $this->getTodayMarkerSVG($today, $period, $calendarScale, $calendarHeight, $branchLabelWidth);
        $legendSVG = $this->getLegendSVG($calendarHeight);

        // Generate SVG content
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 $totalWidth $totalHeight">
  <!-- this chart was originally inspired by https://symfony.com/releases -->

  $svgStyle

  $yearsSVG

  $releasesSVG

  $labelsSVG

  $markerSVG

  $legendSVG
SVG;
    }

    private function getReleases(int $projectId = 1, string $branch = 'trunk'): array
    {
        $fileInfo = $this->client->api('repo')->contents()->show('shopware', 'shopware', 'releases.json', 'trunk');

        $releases = json_decode(base64_decode($fileInfo['content']), true);

        return $releases;
    }

    private function getSchedulePeriod(): DatePeriod
    {
        $startDate = new DateTime('2021-01-01');
        $endDate = new DateTime('+2 years');

        $startDate->modify('first day of January');
        $endDate->modify('last day of December');

        return new DatePeriod(
            $startDate,
            new DateInterval('P1Y'),
            $endDate
        );
    }

    private function getYears(DatePeriod $period): array
    {
        $years = [];

        foreach ($period as $date) {
            $years[] = $date->format('Y');
        }

        return $years;
    }

    private function getStyleSVG()
    {
        $style = <<<STYLE
  <style>
    :root {
    --font-family-base: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    --font-size-md: 16px;
    --text-color: #1f2937;
    --link-color: #273a4c;
    --chart-grid-horizontal-line-color: #d1d5db;
    --chart-grid-vertical-line-color: #9ca3af;
    --chart-yellow-color: #facc15;
    --chart-orange-color: #fa903e;
    --chart-green-color: #24cc6a;
    --chart-blue-color: #189eff;
    }

    text {
        fill: var(--text-color);
        font-family: var(--font-family-base);
        font-size: var(--font-size-md);
    }

    .legend text {
        fill: var(--text-color) !important;
        font-size: 15px;
    }

    .legend rect { rx: 2; }

    .branches rect.stable { fill: var(--chart-blue-color); }
    .branches rect.lts { fill: var(--chart-green-color); }
    .branches rect.security { fill: var(--chart-yellow-color); }

    g.stable rect { fill: var(--chart-blue-color); }
    g.lts rect { fill: var(--chart-green-color); }
    g.security rect { fill: var(--chart-yellow-color); }
    g.eol rect { fill: var(--chart-orange-color); }

    g.stable text { fill: #fff; }
    g.lts text { fill: #fff; }
    g.security text { fill: #fff; }
    g.eol text { fill: #fff; }

    g.unstable rect,
    .branches rect.unstable {
        fill: #fff;
        stroke: var(--chart-grid-horizontal-line-color);
        stroke-width: 2px;
    }
    g.unstable text { fill: #18171b; }
    .legend g.unstable text { fill: var(--text-color); }

    .branch-labels rect { rx: 5; }
    .branch-labels text { dominant-baseline: central; text-anchor: middle; }
    .branches line { stroke: var(--chart-grid-horizontal-line-color); stroke-width: 1px; stroke-dasharray: 4, 6; }

    .today line { stroke: var(--text-color); stroke-dasharray: 10, 15; stroke-width: 4px; stroke-linecap: round; }
    .today text { fill: var(--text-color); text-anchor: middle; }

    .years line { stroke: var(--chart-grid-vertical-line-color); }
    .years text { font-weight: bold; text-anchor: middle; }

    .branches a:hover,
    .branch-labels a:hover,
    .branch-labels a:hover g.unstable rect {
        cursor: pointer;
        stroke-width: 2px;
        stroke: var(--link-color);
        stroke-linecap: round;
        text-decoration: none;
    }
    .branch-labels a:hover text { stroke-width: 0; }
  </style>
STYLE;

        return $style;
    }

    private function getYearsSVG($years, $yearScale, $calendarHeight, $offset)
    {
        $yearsSVG = <<<SVG
    <g class="years">
SVG;

        $yearX = $offset;
        $lineY = $calendarHeight + 40;

        foreach ($years as $year) {

            $yearsSVG .= <<<SVG
    <line x1="$yearX" y1="30" x2="$yearX" y2="$lineY"></line>
    <text x="$yearX" y="20">
      $year
    </text>
SVG;

            $yearX += $yearScale;
        }

        $yearsSVG .= <<<SVG
  </g>
SVG;

        return $yearsSVG;
    }

    private function getReleasesSVG($releases, $period, $calendarScale, $offset)
    {
        $releasesSVG = <<<SVG
  <g class="branches">
SVG;

        // Because of our one-month release cycle the time-frame for each release is 31 days by default.
        $basicReleaseDuration = 31;

        // Calculate coordinates for each release
        $yCoordinate = 40; // Initial y-coordinate for the first release

        $length = count($releases);

        for ($i = 0; $i < $length; ++$i) {
            $release = $releases[$i];

            if (array_key_exists($i + 1, $releases)) {
                $nextRelease = $releases[$i + 1];
            } else {
                $nextRelease = null;
            }

            // Calculate "x" and "width" attributes based on start date and duration
            $startDate = strtotime($release['release_date']);

            if (isset($nextRelease)) {
                $endDate = strtotime($nextRelease['release_date']);
            } else {
                $endDate = strtotime("+" . $basicReleaseDuration . " days", $startDate);
            }


            $releaseX = (($startDate - $period->getStartDate()->getTimestamp()) * $calendarScale) + $offset;
            $releaseWidth = ($endDate - $startDate) * $calendarScale;

            if ($release['extended_eol'] !== false) {

                $ltsEndDate = strtotime($release['extended_eol']);

                $ltsX = (($endDate - $period->getStartDate()->getTimestamp()) * $calendarScale) + $offset;
                $ltsWidth = ($ltsEndDate - $endDate) * $calendarScale;

                $securityX = (($ltsEndDate - $period->getStartDate()->getTimestamp()) * $calendarScale) + $offset;
                $securityWidth = (strtotime($release['security_eol']) - $ltsEndDate) * $calendarScale;
            } else {
                $ltsX = 0;
                $ltsWidth = 0;

                $securityX = (($endDate - $period->getStartDate()->getTimestamp()) * $calendarScale) + $offset;
                $securityWidth = (strtotime($release['security_eol']) - $endDate) * $calendarScale;
            }

            $versionLink = 'https://github.com/shopware/shopware/releases/tag/v' . $release['version'];

            $rectYCoordinate = $yCoordinate + 5;

            $releasesSVG .= <<<SVG
    <line x1="0" y1="$yCoordinate" x2="855" y2="$yCoordinate"></line>
    <a xlink:href="$versionLink" target="_blank">
      <rect class="stable" x="$releaseX" y="$rectYCoordinate" width="$releaseWidth" height="30"></rect>
      <rect class="lts" x="$ltsX" y="$rectYCoordinate" width="$ltsWidth" height="30"></rect>
      <rect class="security" x="$securityX" y="$rectYCoordinate" width="$securityWidth" height="30"></rect>
    </a>
SVG;

            // Increment the y-coordinate for the next release
            $yCoordinate += 40;
        }

        $releasesSVG .= <<<SVG
  </g>
SVG;

        return $releasesSVG;
    }

    private function getReleaseLabelsSVG($releases, $today)
    {
        $labelsSVG = <<<SVG
  <g class="branch-labels">
SVG;

        // Calculate coordinates for each release label
        $yCoordinate = 60; // Initial y-coordinate for the first release label

        $todayTime = strtotime($today);

        $length = count($releases);

        for ($i = 0; $i < $length; ++$i) {
            $release = $releases[$i];

            if (array_key_exists($i + 1, $releases)) {
                $nextRelease = $releases[$i + 1];
            } else {
                $nextRelease = null;
            }

            // Calculate the y-coordinate for the label to match the corresponding release bar
            $labelYCoordinate = $yCoordinate;
            $labelYRectCoordinate = $yCoordinate - 15;

            $basicReleaseDuration = 31;

            $startDate = strtotime($release['release_date']);

            if (isset($nextRelease)) {
                $endDate = strtotime($nextRelease['release_date']);
            } else {
                $endDate = strtotime("+" . $basicReleaseDuration . " days", $startDate);
            }

            $securityEndDate = strtotime($release['security_eol']);

            $class = 'unstable';

            if ($todayTime >= $startDate && $todayTime <= $endDate) {
                $class = 'stable';
            } else if ($release['extended_eol'] !== false && $todayTime >= $endDate && $todayTime <= strtotime($release['extended_eol'])) {
                $class = 'lts';
            } else if ($todayTime >= $endDate && $todayTime <= $securityEndDate) {
                $class = 'security';
            } else if ($todayTime > $securityEndDate) {
                $class = 'eol';
            }

            $versionLink = 'https://github.com/shopware/shopware/releases/tag/v' . $release['version'];

            $labelsSVG .= <<<LABEL
    <a xlink:href="$versionLink" target="_blank">
      <g class="$class">
        <rect x="1" y="$labelYRectCoordinate" width="70" height="30"></rect>
        <text x="35" y="$labelYCoordinate">
          {$release['version']}
        </text>
      </g>
    </a>
LABEL;

            // Increment the y-coordinate for the next release label
            $yCoordinate += 40;
        }

        $labelsSVG .= <<<SVG
  </g>
SVG;

        return $labelsSVG;
    }

    private function getTodayMarkerSVG($today, $period, $calendarScale, $calendarHeight, $offset)
    {
        $todayX = ((strtotime($today) - $period->getStartDate()->getTimestamp()) * $calendarScale) + $offset;

        $lineY = $calendarHeight + 60;
        $textY = $calendarHeight + 75;

        return <<<SVG

  <g class="today">
    <line x1="$todayX" y1="30" x2="$todayX" y2="$lineY"></line>
    <text x="$todayX" y="$textY">
      Today: $today
    </text>
  </g>
SVG;
    }

    private function getLegendSVG($calendarHeight)
    {

        $legendY = $calendarHeight + 110;
        $textY = $legendY + 11;

        return <<<SVG
  <g class="legend">
    <g class="stable">
      <rect x="5" y="$legendY" width="12" height="12"></rect>
      <text x="23" y="$textY">Maintained version</text>
    </g>

    <g class="lts">
      <rect x="170" y="$legendY" width="12" height="12"></rect>
      <text x="188" y="$textY">Extended support</text>
    </g>

    <g class="security">
      <rect x="330" y="$legendY" width="12" height="12"></rect>
      <text x="348" y="$textY">Security fixes only</text>
    </g>

    <g class="eol">
      <rect x="490" y="$legendY" width="12" height="12"></rect>
      <text x="508" y="$textY">End of life version</text>
    </g>

    <g class="unstable">
      <rect x="650" y="$legendY" width="12" height="12"></rect>
      <text x="668" y="$textY">Unreleased version</text>
    </g>
  </g>
</svg>
SVG;
    }
}
