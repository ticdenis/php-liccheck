<?php

namespace LicCheck;

use InvalidArgumentException;

final class Reason
{
    /** @var string */
    public static $OK = 'OK';
    /** @var string */
    public static $UNAUTHORIZED = 'UNAUTHORIZED';
    /** @var string */
    public static $UNKNOWN = 'UNKNOWN';

    /** @var string */
    private $member;

    /**
     * @param $member string
     */
    public function __construct($member)
    {
        if (!in_array($member, self::member())) {
            throw new InvalidArgumentException('Invalid Reason');
        }

        $this->member = $member;
    }

    /** @return string */
    public function __toString()
    {
        return $this->member;
    }

    /** @return string[] */
    public static function member()
    {
        return [self::$OK, self::$UNAUTHORIZED, self::$UNKNOWN];
    }
}
