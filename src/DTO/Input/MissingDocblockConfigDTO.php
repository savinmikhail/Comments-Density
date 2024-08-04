<?php

declare(strict_types=1);

namespace SavinMikhail\CommentsDensity\DTO\Input;

final class MissingDocblockConfigDTO
{
    /**
     * @readonly
     */
    public bool $class;
    /**
     * @readonly
     */
    public bool $interface;
    /**
     * @readonly
     */
    public bool $trait;
    /**
     * @readonly
     */
    public bool $enum;
    /**
     * @readonly
     */
    public bool $function;
    /**
     * @readonly
     */
    public bool $property;
    /**
     * @readonly
     */
    public bool $constant;
    /**
     * @readonly
     */
    public bool $requireForAllMethods;
    public function __construct(bool $class, bool $interface, bool $trait, bool $enum, bool $function, bool $property, bool $constant, bool $requireForAllMethods)
    {
        $this->class = $class;
        $this->interface = $interface;
        $this->trait = $trait;
        $this->enum = $enum;
        $this->function = $function;
        $this->property = $property;
        $this->constant = $constant;
        $this->requireForAllMethods = $requireForAllMethods;
    }
}
