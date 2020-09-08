<?php

namespace LicCheck;

use Exception;
use InvalidArgumentException;

final class CommandLine
{
    /** @return int */
    public function usage()
    {
        echo(
            "usage: liccheck [-h] [-s STRATEGY_JSON_FILE] [-l {STANDARD,CAUTIOUS,PARANOID}] [-r [COMPOSER_BINARY]] [-R [REPORTING_TXT_FILE]] [--no-deps]

Check license of packages and their dependencies.
    
optional arguments:
  -h, --help            show this help message and exit
  -s STRATEGY_JSON_FILE, --sfile STRATEGY_JSON_FILE
                        strategy json file
  -l {STANDARD,CAUTIOUS,PARANOID}, --level {STANDARD,CAUTIOUS,PARANOID}
                        Level for testing compliance of packages, where:
                          Standard - At least one authorized license (default);
                          Cautious - Per standard but no unauthorized licenses;
                          Paranoid - All licenses must by authorized.
  -r [COMPOSER_BINARY], --rfile [COMPOSER_BINARY]
                        path/to/composer binary
  -R [REPORTING_TXT_FILE], --reporting [REPORTING_TXT_FILE]
                        path/to/reporting.txt file
  --no-deps             don't check dependencies" . PHP_EOL
        );
        return 0;
    }

    /** @return array */
    public function defaultOptions()
    {
        return [
            'sfile'     => null,
            'level'     => Level::$STANDARD,
            'rfile'     => 'composer',
            'reporting' => null,
            'no-deps'   => true,
        ];
    }

    /**
     * @param $args array
     * @return int
     */
    public function execute($args)
    {
        try {
            $strategy = $this->readStrategy($args['sfile']);
            if (is_null($strategy)) {
                return 1;
            }
            return $this->process(
                (string)$args['rfile'],
                $strategy,
                (string)$args['level'],
                $args['reporting'],
                (bool)$args['no-deps']
            );
        } catch (Exception $err) {
            echo($err->getMessage() . PHP_EOL);
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
            echo('Need to either configure composer.json or provide a strategy file' . PHP_EOL);
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
        echo('gathering licenses...');
        $pkgInfo = $this->getPackagesInfo($composer, $noDeps);
        echo(sprintf(
                '%s package%s%s.',
                count($pkgInfo),
                count($pkgInfo) <= 1 ? '' : 's',
                $noDeps ? '' : ' and dependencies'
            ) . PHP_EOL);
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
            echo('check authorized packages...');
            echo(format($groups[Reason::$OK]) . PHP_EOL);
        }

        if (true === array_key_exists(Reason::$UNAUTHORIZED, $groups)) {
            echo('check unauthorized packages...');
            echo(format($groups[Reason::$UNAUTHORIZED]) . PHP_EOL);
            $this->writePackages($groups[Reason::$UNAUTHORIZED]);
            $ret = 1;
        }

        if (true === array_key_exists(Reason::$UNKNOWN, $groups)) {
            echo('check unknown packages...');
            echo(format($groups[Reason::$UNKNOWN]) . PHP_EOL);
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
            echo(sprintf(
                    '    %s (%s): %s',
                    $package['name'],
                    $package['version'],
                    count($package['licenses']) > 0 ? join(', ', $package['licenses']) : 'UNKNOWN'
                ) . PHP_EOL);
        }
    }
}
