<?php

declare(strict_types=1);

namespace SavinMikhail\Tests\CommentsDensity\MissingDocblock;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SavinMikhail\CommentsDensity\DTO\Input\MissingDocblockConfigDTO;
use SavinMikhail\CommentsDensity\MissingDocblock\MissingDocBlockAnalyzer;

use function file_get_contents;

use const DIRECTORY_SEPARATOR;

final class MDBAdvancedCheckForMethod extends TestCase
{
    public static function templateGenericDataProvider(): Generator
    {
        yield 'method with templated class from sources' => [
            <<<'CODE'
<?php

class Foo
{
    private function isTraversableRecursively(\ReflectionClass $reflection): bool
    {
    }
}

CODE
            , 1
        ];

        yield 'method with templated class from test files' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\TemplatedClass;

class Foo
{
    private function isTraversableRecursively(TemplatedClass $reflection): bool
    {
    }
}

CODE
            , 1
        ];
    }

    #[DataProvider('templateGenericDataProvider')]
    public function testTemplateGeneric(string $code, int $expectedCount): void
    {
        $analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false
            )
        );

        $missingDocBlocks = $analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function genericDocblockDataProvider(): Generator
    {
        yield 'non iterable class' => [
            <<<'CODE'
<?php

class Foo
{
    public function foo(): User
    {
        return new User;
    }
}

CODE
            , 0
        ];

        yield 'array with single object' => [
            <<<'CODE'
<?php

class Foo
{
    public function bar(): array
    {
        return [new User()];
    }
}

CODE
            , 1
        ];

        yield 'method with inconsistent array' => [
            <<<'CODE'
<?php

class Foo
{
    public function bar(): array
    {
        return [
            [],
            'asdf' => 12,
            'asdf'
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with mixed array' => [
            <<<'CODE'
<?php

class Foo
{
    public function mixedArray(): array
    {
        return [
            new User(),
            new Order(),
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with empty array' => [
            <<<'CODE'
<?php

class Foo
{
    public function emptyArray(): array
    {
        return [];
    }
}

CODE
            , 1
        ];

        yield 'method with nested generic' => [
            <<<'CODE'
<?php

class Foo
{
    public function nestedArray(): array
    {
        return [
            [new User()],
            [new User()]
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with associative array' => [
            <<<'CODE'
<?php

class Foo
{
    public function associativeArray(): array
    {
        return [
            'user1' => new User(),
            'user2' => new User()
        ];
    }
}

CODE
            , 1
        ];

        yield 'method with other iterable' => [
            <<<'CODE'
<?php

class Foo
{
    public function iterableReturn(): iterable
    {
        return new \ArrayIterator([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method with array of integers' => [
            <<<'CODE'
<?php

class Foo
{
    public function integerArray(): array
    {
        return [1, 2, 3];
    }
}

CODE
            , 1
        ];

        yield 'method with generator' => [
            <<<'CODE'
<?php

class Foo
{
    public function generatorReturn(): \Generator
    {
        yield new User();
    }
}

CODE
            , 1
        ];

        yield 'method with class implementing Iterator' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserCollection;

class Foo
{
    public function userCollectionReturn(): UserCollection
    {
        return new UserCollection([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method returning Iterator' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserCollection;
use Iterator;

class Foo
{
    public function userCollectionReturn(): Iterator
    {
        return new UserCollection([new User()]);
    }
}

CODE
            , 1
        ];

        yield 'method with class implementing ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserArray;

class Foo
{
    public function userArrayReturn(): UserArray
    {
        $array = new UserArray();
        $array[] = new User();
        return $array;
    }
}

CODE
            , 1
        ];

        yield 'method returning ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserArray;

class Foo
{
    public function userArrayReturn(): ArrayAccess
    {
        $array = new UserArray();
        $array[] = new User();
        return $array;
    }
}

CODE
            , 1
        ];

        yield 'function returning ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\UserArray;

function userArrayReturn(): ArrayAccess
{
    $array = new UserArray();
    $array[] = new User();
    return $array;
}

CODE
            , 1
        ];

        yield 'method with iterable argument' => [
            <<<'CODE'
<?php

class Foo
{
    public function process(iterable $items): void
    {
        foreach ($items as $item) {
            // process item
        }
    }
}

CODE
            , 1
        ];

        yield 'method with array argument' => [
            <<<'CODE'
<?php

class Foo
{
    public function process(array $items): void
    {
        foreach ($items as $item) {
            // process item
        }
    }
}

CODE
            , 1
        ];

        yield 'method with Generator argument' => [
            <<<'CODE'
<?php

class Foo
{
    public function process(\Generator $items): void
    {
        foreach ($items as $item) {
            // process item
        }
    }
}

CODE
            , 1
        ];

        yield 'method with Iterator argument' => [
            <<<'CODE'
<?php

use Iterator;

class Foo
{
    public function process(Iterator $items): void
    {
        foreach ($items as $item) {
            // process item
        }
    }
}

CODE
            , 1
        ];

        yield 'method with ArrayAccess argument' => [
            <<<'CODE'
<?php

use ArrayAccess;

class Foo
{
    public function process(ArrayAccess $items): void
    {
        foreach ($items as $item) {
            // process item
        }
    }
}

CODE
            , 1
        ];

        yield 'function with ArrayAccess argument' => [
            <<<'CODE'
<?php

use ArrayAccess;

function process(ArrayAccess $items): void
{
}

CODE
            , 1
        ];

        yield 'method with non iterable scalar argument' => [
            <<<'CODE'
<?php

class Foo
{
    public function process(string $item): void
    {
  
    }
}

CODE
            , 0
        ];

        yield 'method with non iterable class argument' => [
            <<<'CODE'
<?php

class User 
{
    public string $name;
}

class Foo
{
    public function process(User $user): void
    {
  
    }
}

CODE
            , 0
        ];

        yield 'function with non iterable scalar argument' => [
            <<<'CODE'
<?php

function process(string $item): void
{

}

CODE
            , 0
        ];

        yield 'function with non iterable class argument' => [
            <<<'CODE'
<?php

class User 
{
    public string $name;
}

function process(User $user): void
{

}
CODE
            , 0
        ];

        yield 'function with interface that extends iterable' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\InterfaceExtendsIterable;

function foo(InterfaceExtendsIterable $array) 
{
    $user = $array->get();

    $name = $user->name;
}

CODE,
            1
        ];

        yield 'function with interface with template' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\InterfaceExtendsIterable;

function foo(InterfaceExtendsIterable $array) 
{
    $user = $array->get();

    $name = $user->name;
}

CODE,
            1
        ];

        yield 'function with class extended from class that implementing ArrayAccess' => [
            <<<'CODE'
<?php

use SavinMikhail\Tests\CommentsDensity\TestFiles\SubUserArray;

function subArrayReturn(): SubUserArray
{
    $array = new SubUserArray();
    $array[] = new User();
    return $array;
}

CODE
            , 1
        ];
    }

    #[DataProvider('genericDocblockDataProvider')]
    public function testGenericDocblockDetection(string $code, int $expectedCount): void
    {
        $analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false
            )
        );

        $missingDocBlocks = $analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }

    public static function uncaughtExceptionDocblockDataProvider(): Generator
    {
        yield 'method with uncaught exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        throw new Exception();
    }
}

CODE
            , 1
        ];

        yield 'method with caught exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            //do something
        }
    }
}

CODE
            , 0
        ];

        yield 'method with rethrown exception' => [
            <<<'CODE'
<?php

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            //do something
            throw $e;
        }
    }
}

CODE
            , 1
        ];

        yield 'method caught another exception' => [
            <<<'CODE'
<?php
class MyException extends Exception {}
use Mockery\Exception;
class Foo
{
    public function baz(): void
    {
        try {
            throw new \Exception();
        } catch (MyException $e) {
            //do something
        } catch (Exception $exception) {
        
        }
    }
}

CODE
            , 1
        ];

        yield 'method caught throwable' => [
            <<<'CODE'
<?php
class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (Throwable $th) {
            //do something
        }
    }
}

CODE
            , 0
        ];

        yield 'method with multiple catches' => [
            <<<'CODE'
<?php
class MyException extends Exception {}

class Foo
{
    public function baz(): void
    {
        try {
            throw new Exception();
        } catch (MyException $e) {
            //do something
        } catch (Exception $e) {
            //do something
        }
    }
}

CODE
            , 0
        ];

        yield 'method without catching method that throws and has throws tag in the same class' => [
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../TestFiles/ClassWithMethodThatThrowsAndHasTag.php') //made for class_exists() function work
            , 1
        ];

        yield 'method without catching method that throws and has throws tag in the different class' => [
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../TestFiles/ClassWithCallForThrowingMethodInDifferentClass.php') //made for class_exists() function work
            , 1
        ];

        yield 'method that catching method that throws and has throws tag in the different class' => [
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../TestFiles/ClassWithCallForThrowingMethodInDifferentClassInTryCatch.php') //made for class_exists() function work
            , 0
        ];

        yield 'method that not catching method that throws and has throws tag in the different class' => [
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../TestFiles/ClassThatNotCatchingExceptionFromCallMethodOfAnotherClass.php') //made for class_exists() function work
            , 1
        ];

        yield 'method that catching method that throws and does not have throws tag in the different class' => [
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '../TestFiles/ClassThatCatchingMethodCallOfAnotherClassWhichDoesNotHaveDocblock.php') //made for class_exists() function work
            , 0
        ];
    }

    #[DataProvider('uncaughtExceptionDocblockDataProvider')]
    public function testExceptionDocblockDetection(string $code, int $expectedCount): void
    {
        $analyzer = new MissingDocBlockAnalyzer(
            new MissingDocblockConfigDTO(
                false,
                false,
                false,
                false,
                true,
                false,
                false,
                false
            )
        );

        $missingDocBlocks = $analyzer->getMissingDocblocks($code, 'test.php');
        $this->assertCount($expectedCount, $missingDocBlocks);
    }
}