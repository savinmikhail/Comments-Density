<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\TestFiles;

use Iterator;

class UserCollection implements Iterator
{
    private int $position = 0;

    public function __construct(private array $users)
    {
    }

    public function current(): User
    {
        return $this->users[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->users[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}

class User
{

}