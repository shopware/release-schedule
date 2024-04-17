<?php

namespace Shopware\ReleaseSchedule\Command;

use AsyncAws\S3\S3Client;
use Shopware\ReleaseSchedule\Service\ReleaseSchedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'publish',
    description: <<<EOF
    This command can be used to update the release schedule for the release
    policy site [1].
    
    [1]: <info>https://developer.shopware.com/release-notes/#shopware-release-policy</info>
EOF
)]
class PublishReleaseScheduleCommand extends Command
{
    private SymfonyStyle $io;

    private bool $dryRun = false;

    public function __construct(
        protected ReleaseSchedule $releaseSchedule,
        protected S3Client $s3
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Dry run. Don\'t execute any write-requests.'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->dryRun = (bool) $input->getOption('dry-run');

        if ($this->dryRun) {
            $this->io->warning('Dry run, skipping state-changing requests.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $svg = $this->releaseSchedule->generateReleaseCalendar();

        $this->uploadReleaseCalendar($svg);

        return Command::SUCCESS;
    }

    private function uploadReleaseCalendar(string $svg): void
    {
        $args = [
            'Bucket' => 'shopware-platform-assets',
            'Key' => 'release-schedule/schedule.svg',
            'Body' => $svg,
            'ACL' => 'public-read',
            'ContentType' => 'image/svg+xml',
        ];

        if ($this->dryRun) {
            $this->io->info(var_export($args, true));

            return;
        } else {
            $response = $this->s3->putObject($args);
        }

        $this->io->success(sprintf('Release schedule available at: %s', $response->get('ObjectURL')));
    }
}
