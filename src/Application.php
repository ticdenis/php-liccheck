<?php

namespace LicCheck;

final class Application
{
    /**
     * @param $argv array
     * @param CommandLine $command
     * @return int
     */
    public function run($argv, CommandLine $command)
    {
        if (in_array('-h', $argv, true) || in_array('--help', $argv, true)) {
            return $command->usage();
        }

        $args = $command->defaultOptions();

        $args['sfile'] = $this->searchOptionValue($argv, '-s', $args['sfile']);
        $args['sfile'] = $this->searchOptionValue($argv, '-sfile', $args['sfile']);

        $args['level'] = $this->searchOptionValue($argv, '-l', $args['level']);
        $args['level'] = $this->searchOptionValue($argv, '--level', $args['level']);

        $args['rfile'] = $this->searchOptionValue($argv, '-r', $args['rfile']);
        $args['rfile'] = $this->searchOptionValue($argv, '--rfile', $args['rfile']);

        $args['reporting'] = $this->searchOptionValue($argv, '-R', $args['reporting']);
        $args['reporting'] = $this->searchOptionValue($argv, '--reporting', $args['reporting']);

        $args['no-deps'] = $this->searchOptionValue($argv, '--no-deps', $args['no-deps']);

        return $command->execute($args);
    }

    /**
     * @param $argv array
     * @param $key string
     * @param $defaultValue mixed
     * @return mixed
     */
    private function searchOptionValue($argv, $key, $defaultValue)
    {
        $index = (int)array_search($key, $argv, true);
        return ($index > 0 && isset($argv[$index + 1])) ? $argv[$index + 1] : $defaultValue;
    }
}
