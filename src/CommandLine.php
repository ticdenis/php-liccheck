<?php

namespace LicCheck;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CommandLine extends Command
{
    /** @var InputInterface|null */
    private $input;
    /** @var OutputInterface|null */
    private $output;

    /** @return void */
    protected function configure()
    {
        $this
            ->setName('liccheck')
            ->setDescription('Check license of packages and their dependencies.')
            ->addOption(
                'sfile',
                's',
                InputOption::VALUE_OPTIONAL,
                'strategy json file',
                null
            )
            ->addOption(
                'level',
                'l',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    '%s%s%s%s',
                    'Level for testing compliance of packages, where:' . PHP_EOL,
                    '  Standard - At least one authorized license (default);' . PHP_EOL,
                    '  Cautious - Per standard but no unauthorized licenses;' . PHP_EOL,
                    '  Paranoid - All licenses must by authorized.'
                ),
                Level::$STANDARD
            )
            ->addOption(
                'rfile',
                'r',
                InputOption::VALUE_OPTIONAL,
                'path/to/composer binary',
                'composer'
            )
            ->addOption(
                'reporting',
                'R',
                InputOption::VALUE_OPTIONAL,
                'path/to/reporting.txt file',
                null
            )
            ->addOption(
                'no-deps',
                null,
                InputOption::VALUE_OPTIONAL,
                'don\'t check dependencies',
                true
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        try {
            $strategy = $this->readStrategy($input->getOption('sfile'));
            if (is_null($strategy)) {
                return 1;
            }
            return $this->process(
                (string)$input->getOption('rfile'),
                $strategy,
                (string)$input->getOption('level'),
                $input->getOption('reporting'),
                (bool)$input->getOption('no-deps')
            );
        } catch (Exception $err) {
            $this->output->writeln($err->getMessage());
            return 1;
        }
    }

    /**
     * @param string|null $strategyFile
     * @return Strategy
     * @throws InvalidArgumentException
     */
    private function readStrategy($strategyFile)
    {
        try {
            return Strategy::fromComposerJSON();
        } catch (NoValidConfigurationInComposerJson $err) {
            // pass
        }
        if (is_null($strategyFile)) {
            $this->output->writeln('Need to either configure composer.json or provide a strategy file');
            return null;
        }
        return Strategy::fromConfig($strategyFile);
    }

    /**
     * @param $composer string
     * @param $strategy Strategy
     * @param $level string
     * @param $reportingFile string|null
     * @param $noDeps bool
     * @return int
     */
    private function process($composer, $strategy, $level, $reportingFile, $noDeps)
    {
        $this->output->write('gathering licenses...');
        $pkgInfo = $this->getPackagesInfo($composer, $noDeps);
        $this->output->writeln(sprintf(
            '%s package%s%s.',
            count($pkgInfo),
            count($pkgInfo) <= 1 ? '' : 's',
            $noDeps ? '' : ' and dependencies'
        ));
        $groups = array_fill_keys(Reason::member(), []);
        foreach ($pkgInfo as $pkg) {
            $groups[$this->checkPackage($strategy, $pkg, $level)][] = $pkg;
        }
        $ret = 0;

        if (null !== $reportingFile) {
            $packages = [];
            foreach ($groups as $reason => $pkgs) {
                foreach ($pkgs as $pkg) {
                    $packages[] = [
                        'name'    => $pkg['name'],
                        'version' => $pkg['version'],
                        'license' => count($pkg['licenses']) > 0 ? $pkg['licenses'][0] : 'UNKNOWN',
                        'status'  => $reason,
                    ];
                }
            }
            usort($packages, static function ($pkg1, $pkg2) {
                return strcasecmp($pkg1['name'], $pkg2['name']);
            });
            $data = [];
            foreach ($packages as $package) {
                $data[] = sprintf(
                    '%s %s %s %s%s',
                    $package['name'],
                    $package['version'],
                    $package['license'],
                    $package['status'],
                    PHP_EOL
                );
            }
            file_put_contents($reportingFile, join('', $data), FILE_APPEND);
        }

        /**
         * @param $packages array
         * @return string
         */
        function format($packages)
        {
            return sprintf('%s package%s.', count($packages), count($packages) <= 1 ? '' : 's');
        }

        if (true === array_key_exists(Reason::$OK, $groups)) {
            $this->output->write('check authorized packages...');
            $this->output->writeln(format($groups[Reason::$OK]));
        }

        if (true === array_key_exists(Reason::$UNAUTHORIZED, $groups)) {
            $this->output->write('check unauthorized packages...');
            $this->output->writeln(format($groups[Reason::$UNAUTHORIZED]));
            $this->writePackages($groups[Reason::$UNAUTHORIZED]);
            $ret = 1;
        }

        if (true === array_key_exists(Reason::$UNKNOWN, $groups)) {
            $this->output->write('check unknown packages...');
            $this->output->writeln(format($groups[Reason::$UNKNOWN]));
            $this->writePackages($groups[Reason::$UNKNOWN]);
            $ret = 1;
        }

        return $ret;
    }

    /**
     * @param $composer string
     * @param $noDeps bool
     * @return array
     * @throws InvalidArgumentException
     */
    private function getPackagesInfo($composer, $noDeps)
    {
        $requirements = Requirements::parse($composer, $noDeps);
        $packages     = [];
        foreach ($requirements as $name => $requirement) {
            $packages[$name] = [
                'name'     => $name,
                'version'  => $requirement['version'],
                'licenses' => $requirement['license'],
            ];
        }
        ksort($packages, SORT_REGULAR);
        return $packages;
    }

    /**
     * @param $strategy Strategy
     * @param $pkg array
     * @param $level string
     * @return string
     */
    private function checkPackage($strategy, $pkg, $level)
    {
        if (true === in_array($pkg['name'], $strategy->authorizedPackages(), true)) {
            return Reason::$OK;
        }

        $atLeastOneUnauthorized = false;
        $countAuthorized        = 0;
        foreach ($pkg['licenses'] as $license) {
            if (true === in_array($license, $strategy->unauthorizedLicenses())) {
                $atLeastOneUnauthorized = true;
            }
            if (true === in_array($license, $strategy->authorizedLicenses())) {
                $countAuthorized += 1;
            }
        }

        if ($countAuthorized > 0 && (
                (Level::$STANDARD === $level) ||
                (false === $atLeastOneUnauthorized && Level::$CAUTIOUS === $level) ||
                ($countAuthorized === count($pkg['licenses']) && Level::$PARANOID === $level)
            )
        ) {
            return Reason::$OK;
        }

        if (true === $atLeastOneUnauthorized) {
            return Reason::$UNAUTHORIZED;
        }

        return Reason::$UNKNOWN;
    }

    /**
     * @param $packages array
     * @return void
     */
    private function writePackages($packages)
    {
        foreach ($packages as $package) {
            $this->output->writeln(sprintf(
                '    %s (%s): %s',
                $package['name'],
                $package['version'],
                count($package['licenses']) > 0 ? join(', ', $package['licenses']) : 'UNKNOWN'
            ));
        }
    }
}
