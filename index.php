#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use Shopware\ReleaseSchedule\Command\GenerateReleaseScheduleCommand;
use Shopware\ReleaseSchedule\Command\PublishReleaseScheduleCommand;
use Shopware\ReleaseSchedule\Service\ReleaseSchedule;
use Symfony\Component\Console\Application;
use Psr\Log\NullLogger;
use AsyncAws\S3\S3Client;

$s3 = new S3Client();
$releaseScheduleService = new ReleaseSchedule();

$generateCommand = new GenerateReleaseScheduleCommand($releaseScheduleService);
$publishCommand = new PublishReleaseScheduleCommand($releaseScheduleService, $s3);

$application = new Application('release-schedule', \Composer\InstalledVersions::getVersion('shopware/release-schedule'));
$application->add($generateCommand);
$application->add($publishCommand);

$application->run();