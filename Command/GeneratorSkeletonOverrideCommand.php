<?php
declare(strict_types=1);

namespace K3ssen\BaseAdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class GeneratorSkeletonOverrideCommand extends Command
{
    protected static $defaultName = 'admin:generator-skeleton-override';

    protected $projectDir;

    protected $bundles;

    public function __construct(?string $name = null, string $projectDir, array $bundles)
    {
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
        parent::__construct($name);
    }

    protected function configure()
    {
        if (!array_key_exists('EntityGeneratorBundle', $this->bundles)) {
            $this->setHidden(true);
        }
        $this
            ->setDescription('Add files to override the generator skeleton files')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite files only if newer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $overwrite = $input->getOption('overwrite') ?? false;

        $fs = new Filesystem();

        if (array_key_exists('CrudGeneratorBundle', $this->bundles)) {
            $originDir = __DIR__ . '/../Resources/CrudGeneratorBundle/skeleton_overrides/';
            $targetDir = $this->projectDir  . '/templates/bundles/CrudGeneratorBundle/skeleton/';
            $fs->mirror($originDir, $targetDir,null, ['override' => $overwrite]);
            $io->success(sprintf('Created files in %s', $targetDir));
        }

        if (array_key_exists('EntityGeneratorBundle', $this->bundles)) {
            $originDir = __DIR__ . '/../Resources/EntityGeneratorBundle/skeleton_overrides/';
            $targetDir = $this->projectDir  . '/templates/bundles/EntityGeneratorBundle/skeleton/';
            $fs->mirror($originDir, $targetDir,null, ['override' => $overwrite]);
            $io->success(sprintf('Created files in %s', $targetDir));
        }

    }
}
