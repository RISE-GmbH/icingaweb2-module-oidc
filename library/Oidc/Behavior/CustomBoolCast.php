<?php

namespace Icinga\Module\Oidc\Behavior;

use ipl\Orm\Contract\PropertyBehavior;

class CustomBoolCast extends PropertyBehavior
{
    /** @var mixed Database value for boolean `false` */
    protected $falseValue = 'n';

    /** @var mixed Database value for boolean `true` */
    protected $trueValue = 'y';

    /** @var bool Whether to throw an exception if the value is not equal to the value for false or true */
    protected $strict = true;

    /**
     * Get the database value representing boolean `false`
     *
     * @return mixed
     */
    public function getFalseValue()
    {
        return $this->falseValue;
    }

    /**
     * Set the database value representing boolean `false`
     *
     * @param mixed $falseValue
     *
     * @return $this
     */
    public function setFalseValue($falseValue): self
    {
        $this->falseValue = $falseValue;

        return $this;
    }

    /**
     * Get the database value representing boolean `true`
     *
     * @return mixed
     */
    public function getTrueValue()
    {
        return $this->trueValue;
    }

    /**
     * Get the database value representing boolean `true`
     *
     * @param mixed $trueValue
     *
     * @return $this
     */
    public function setTrueValue($trueValue): self
    {
        $this->trueValue = $trueValue;

        return $this;
    }

    /**
     * Get whether to throw an exception if the value is not equal to the value for false or true
     *
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Set whether to throw an exception if the value is not equal to the value for false or true
     *
     * @param bool $strict
     *
     * @return $this
     */
    public function setStrict(bool $strict): self
    {
        $this->strict = $strict;

        return $this;
    }

    public function fromDb($value, $key, $_)
    {
        if($value == "0" || $value =="n" ){
            return false;
        }
        if($value == "1" || $value =="y" ){
            return true;
        }
        return $value;
    }

    public function toDb($value, $key, $_)
    {
        if(is_bool($value)){
            return $value;
        }
        if($value == "0" || $value =="n" ){
            return '0';
        }
        if($value == "1" || $value =="y" ){
            return '1';
        }
        return null;
    }
}