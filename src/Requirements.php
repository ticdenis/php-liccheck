<?php

namespace LicCheck;

use InvalidArgumentException;

final class Requirements
{
    /**
     * @param $composer string
     * @param $noDeps bool
     * @return string[][]
     * @throws InvalidArgumentException
     */
    public static function parse($composer, $noDeps)
    {
        $command = sprintf('%s licenses %s --format json', $composer, $noDeps ? '--no-dev' : '');
        exec($command, $returnContent, $returnCode);
        if (0 !== ((int)$returnCode)) {
            throw new InvalidArgumentException($returnContent, $returnCode);
        }

        $requirements = json_decode(join('', $returnContent), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException('Error decoding JSON');
        } else if (!array_key_exists('dependencies', $requirements) || !is_array($requirements['dependencies'])) {
            throw new InvalidArgumentException('"dependencies" must be present as array!');
        }

        return $requirements['dependencies'];
    }
}
