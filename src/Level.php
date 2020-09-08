<?php

namespace LicCheck;

use InvalidArgumentException;

final class Level
{
    /** @var string */
    public static $STANDARD = 'STANDARD';
    /** @var string */
    public static $CAUTIOUS = 'CAUTIOUS';
    /** @var string */
    public static $PARANOID = 'PARANOID';

    /** @var string */
    private $member;

    /**
     * @param $member string
     */
    public function __construct($member)
    {
        if (!in_array($member, self::member())) {
            throw new InvalidArgumentException('Invalid Level');
        }

        $this->member = $member;
    }

    /**
     * Return level starting with value (case-insensitive)
     *
     * @param $value string
     * @return self
     * @throws InvalidArgumentException
     */
    public static function starting($value)
    {
        $prefix = strtoupper($value);
        foreach (self::member() as $member) {
            if (0 === strncmp($member, $prefix, strlen($prefix))) {
                return new self($member);
            }
        }
        throw new InvalidArgumentException(sprintf('No level starting with %s', $value));
    }

    /** @return string */
    public function __toString()
    {
        return $this->member;
    }

    /** @return string[] */
    public static function member()
    {
        return [self::$STANDARD, self::$CAUTIOUS, self::$PARANOID];
    }
}
