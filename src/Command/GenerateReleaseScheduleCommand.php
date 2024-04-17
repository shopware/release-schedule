<?php

namespace Shopware\ReleaseSchedule\Command;

use Shopware\ReleaseSchedule\Service\ReleaseSchedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generate',
    description: <<<EOF
    This command can be used to generate the release schedule for the release
    policy site [1]. It will output the SVG to stdout.

    [1]: <info>https://developer.shopware.com/release-notes/#shopware-release-policy</info>
EOF
)]
class GenerateReleaseScheduleCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        protected ReleaseSchedule $releaseSchedule
    ) {
        parent::__construct();
    }

    protected function configure(): int
    {
        $this->setDescription('Generate a release calendar for Shopware 6')
            ->setHelp('This command generates a release calendar for Shopware 6');

        return Command::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $calendar = $this->releaseSchedule->generateReleaseCalendar();

        $this->io->text($calendar);

        return Command::SUCCESS;
    }
}
