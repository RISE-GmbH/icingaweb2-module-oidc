<?php

namespace Icinga\Module\Oidc\Behavior;

use DateTime;
use DateTimeZone;
use Exception;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Exception\ValueConversionException;

class SecondTimestamp extends PropertyBehavior
{
    public function fromDb($value, $key, $context)
    {
        if ($value === null) {
            return null;
        }

        $datetime = DateTime::createFromFormat('U.u', sprintf('%F', $value));
        $datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $datetime;
    }

    public function toDb($value, $key, $context)
    {
        if (is_numeric($value)) {
            return (int) ($value);
        }

        if (! $value instanceof DateTime) {
            try {
                $value = new DateTime($value);
            } catch (Exception $err) {
                throw new ValueConversionException(sprintf('Invalid date time format provided: %s', $value));
            }
        }

        return (int) ($value->format('U.u'));
    }
}
