<?php

declare(strict_types=1);

namespace F4\Tests\Core;
use PHPUnit\Framework\TestCase;

use F4\Core\Validator;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ArrayOf;
use F4\Core\Validator\CastBool;
use F4\Core\Validator\CastBoolean;
use F4\Core\Validator\CastFloat;
use F4\Core\Validator\CastInt;
use F4\Core\Validator\CastInteger;
use F4\Core\Validator\DefaultValue;
use F4\Core\Validator\Fields;
use F4\Core\Validator\Filter;
use F4\Core\Validator\IsBool;
use F4\Core\Validator\IsBoolean;
use F4\Core\Validator\IsEmail;
use F4\Core\Validator\IsFloat;
use F4\Core\Validator\IsInt;
use F4\Core\Validator\IsInteger;
use F4\Core\Validator\IsNotEmpty;
use F4\Core\Validator\IsOneOf;
use F4\Core\Validator\IsRegExpMatch;
use F4\Core\Validator\IsUrl;
use F4\Core\Validator\IsUuid;
use F4\Core\Validator\Max;
use F4\Core\Validator\Min;
use F4\Core\Validator\OneOf;
use F4\Core\Validator\RegExp;
use F4\Core\Validator\SanitizedString;
use F4\Core\Validator\Trim;
use F4\Core\Validator\UnsafeString;

use InvalidArgumentException;
use TypeError;

final class ValidatorTest extends TestCase
{

    public function testStringSanitizationByDefault(): void
    {
        $validator = new Validator(flags: Validator::SANITIZE_STRINGS_BY_DEFAULT);
        $arguments = $validator->getFilteredArguments(
            function (string $arg): void {
            },
            [
                'arg' => '<p>Unsafe string</p>'
            ]
        );

        $this->assertSame('&lt;p&gt;Unsafe string&lt;/p&gt;', $arguments['arg']);
    }

    public function testNoStringSanitizationByDefault(): void
    {
        $validator = new Validator(flags: ~Validator::SANITIZE_STRINGS_BY_DEFAULT);
        $arguments = $validator->getFilteredArguments(
            function (string $arg): void {
            },
            [
                'arg' => '<p>Unsafe string</p>'
            ]
        );

        $this->assertSame('<p>Unsafe string</p>', $arguments['arg']);
    }

    public function testCasting(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[CastBool] bool $bool1, #[CastBool] bool $bool2, #[CastBoolean] bool $bool3, #[CastBoolean] bool $bool4, #[CastInt] bool $int1, #[CastInt] bool $int2, #[CastInteger] bool $int3, #[CastInteger] bool $int4, #[CastInteger] bool $int5, #[CastFloat] float $float1, #[CastFloat] float $float2, #[CastFloat] float $float3): void {
            },
            [
                'bool1' => 1,
                'bool2' => 0,
                'bool3' => 'true',
                'bool4' => 'false',

                'int1' => '1',
                'int2' => '-1',
                'int3' => '2',
                'int4' => '-2',
                'int5' => '0',

                'float1' => '-2.2',
                'float2' => '5.67',
                'float3' => '0',
            ]
        );

        $this->assertSame(true, $arguments['bool1']);
        $this->assertSame(false, $arguments['bool2']);
        $this->assertSame(true, $arguments['bool3']);
        $this->assertSame(false, $arguments['bool4']);

        $this->assertSame(1, $arguments['int1']);
        $this->assertSame(-1, $arguments['int2']);
        $this->assertSame(2, $arguments['int3']);
        $this->assertSame(-2, $arguments['int4']);
        $this->assertSame(0, $arguments['int5']);

        $this->assertSame(-2.2, $arguments['float1']);
        $this->assertSame(5.67, $arguments['float2']);
        $this->assertSame(0.0, $arguments['float3']);
    }

    public function testValidValidationAttributes(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (
                #[IsInt] int $int1, 
                #[IsInt] int $int2, 
                #[IsInt] int $int3, 
                #[IsInteger] int $int4, 
                #[IsInteger] int $int5, 
                #[IsInteger] int $int6, 
                #[IsBool] bool $bool1, 
                #[IsBoolean] bool $bool2, 
                #[IsFloat] float $float1, 
                #[IsFloat] float $float2, 
                #[IsFloat] float $float3, 
                #[IsEmail] string $email1, 
                #[IsNotEmpty] string $notempty1, 
                #[IsUrl] string $url1, 
                #[IsUuid] string $uuid1, 
                #[IsOneOf(['1', '2', 3])] string $oneof1, 
                #[IsOneOf(['1', '2', 3])] int $oneof2, 
            ): void {
            },
            [
                'int1' => 5,
                'int2' => -5,
                'int3' => 0,
                'int4' => 4,
                'int5' => 5,
                'int6' => 6,
                'bool1' => true,
                'bool2' => false,
                'float1' => 1.1,
                'float2' => 0.0,
                'float3' => 2.3,
                'email1' => 'valid@email.test',
                'notempty1' => 'non-empty value',
                'url1' => 'https://test.com/?param=value',
                'uuid1' => '9c5b94b1-35ad-49bb-b118-8e8fc24abf80',
                'oneof1' => '2',
                'oneof2' => 3,
            ]
        );

        $this->assertSame(5, $arguments['int1']);
        $this->assertSame(-5, $arguments['int2']);
        $this->assertSame(0, $arguments['int3']);
        $this->assertSame(4, $arguments['int4']);
        $this->assertSame(5, $arguments['int5']);
        $this->assertSame(6, $arguments['int6']);

        $this->assertSame(true, $arguments['bool1']);
        $this->assertSame(false, $arguments['bool2']);

        $this->assertSame(1.1, $arguments['float1']);
        $this->assertSame(0.0, $arguments['float2']);
        $this->assertSame(2.3, $arguments['float3']);

        $this->assertSame('valid@email.test', $arguments['email1']);

        $this->assertSame('non-empty value', $arguments['notempty1']);

        $this->assertSame('https://test.com/?param=value', $arguments['url1']);

        $this->assertSame('9c5b94b1-35ad-49bb-b118-8e8fc24abf80', $arguments['uuid1']);


        $this->assertSame('2', $arguments['oneof1']);
        $this->assertSame(3, $arguments['oneof2']);

    }
    public function testValidSanitizationAttributes(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (
                #[UnsafeString] string $unsafe1, 
                #[SanitizedString] string $sanitized1, 
                #[Trim] string $trim1, 
                #[Min(10)] int $min1, 
                #[Max(20)] int $max1, 
                #[Filter(FILTER_VALIDATE_BOOLEAN)] bool $filter1, 
                #[Filter(FILTER_VALIDATE_BOOLEAN)] bool $filter2, 
                #[OneOf(['a', 'b', 'c'])] string $oneof3, 
                #[OneOf(['a', 'b', 'c'])] string $oneof4 = 'd', 
                #[RegExp('/a([a-z0-9]+)g/', 1)] string $regexp1 = '', 
                #[DefaultValue('default')] string $default1 = 'non-default-value', ): void {
            },
            [
                'unsafe1' => '<p>Unsafe string</p>',
                'sanitized1' => '<p>Unsafe string</p>',
                'trim1' => '   trimmed     ',
                'min1' => 8,
                'max1' => 22,
                'filter1' => 'true',
                'filter2' => 'false',
                'oneof3' => 'b',
                'oneof4' => 'e',
                'regexp1' => 'abcdefg',
            ]
        );

        $this->assertSame('<p>Unsafe string</p>', $arguments['unsafe1']);
        $this->assertSame('&lt;p&gt;Unsafe string&lt;/p&gt;', $arguments['sanitized1']);

        $this->assertSame('trimmed', $arguments['trim1']);

        $this->assertSame(10, $arguments['min1']);
        $this->assertSame(20, $arguments['max1']);

        $this->assertSame(true, $arguments['filter1']);
        $this->assertSame(false, $arguments['filter2']);

        $this->assertSame('b', $arguments['oneof3']);
        $this->assertSame('d', $arguments['oneof4']);

        $this->assertSame('bcdef', $arguments['regexp1']);

        $this->assertSame('default', $arguments['default1']);

    }
    public function testSequenceOfAttributes(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[OneOf(['a', 'b', 'c'])] #[DefaultValue('c')] string $oneof1 = 'd', ): void {
            },
            [
                'oneof1' => '',
            ]
        );
        $this->assertSame('c', $arguments['oneof1']);
    }

    public function testFields(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[Fields(['name' => new SanitizedString, 'email' => new IsEmail])] array $fields1 = [], ): void {
            },
            [
                'fields1' => [
                    'name' => '<b>name</b>',
                    'email' => 'valid@email.test'
                ],
            ]
        );
        $this->assertSame(['name' => '&lt;b&gt;name&lt;/b&gt;', 'email' => 'valid@email.test'], $arguments['fields1']);
    }

    public function testFieldsIncorrectArguments(): void
    {
        $this->expectException(TypeError::class);
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[Fields(['key' => 'invalid-value'])] string $fields1, ): void {
            },
            [
                'fields1' => [],
            ]
        );
        $this->assertSame('c', $arguments['fields1']);
    }
    public function testFieldsIncorrectArguments2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[Fields(['string'])] string $fields1, ): void {
            },
            [
                'fields1' => [],
            ]
        );
        $this->assertSame('c', $arguments['fields1']);
    }
    public function testArrayOfIncorrectArguments(): void
    {
        $this->expectException(TypeError::class);
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[ArrayOf('invalid-string')] string $fields1, ): void {
            },
            [
                'fields1' => [],
            ]
        );
        // $this->assertSame('c', $arguments['fields1']);
    }


    public function testArrayOf(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[ArrayOf(new SanitizedString())] array $array1 = [], ): void {
            },
            [
                'array1' => ['a', '<b></b>'],
            ]
        );
        $this->assertSame(['a', '&lt;b&gt;&lt;/b&gt;'], $arguments['array1']);
    }

    public function testFallbackToDefaultValue(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[OneOf(['a', 'b', 'c'])] string $oneof1 = 'd', ): void {
            },
            [
                'oneof1' => '',
            ]
        );
        $this->assertSame('d', $arguments['oneof1']);
    }
    public function testDefaultValueAsFallback(): void
    {
        $validator = new Validator();
        $closure = function (#[DefaultValue('a')] string $default1, ): void {
        };
        $arguments = $validator->getFilteredArguments(
            $closure,
            [
                'oneof1' => null,
            ]
        );
        $this->assertSame('a', $arguments['default1']);
    }

    public function testNullAsDefaultValueAttributes(): void
    {
        $validator = new Validator();
        $arguments = $validator->getFilteredArguments(
            function (#[DefaultValue(null)] string $default1, ): void {
            },
            [
                // 'oneof1' => 'b',
            ]
        );
        $this->assertSame(null, $arguments['default1']);
    }

    public function testStrictTypeCheckWithNull(): void
    {
        $validator = new Validator();
        $closure = function (#[OneOf(['a', 'b', 'c'])] ?string $oneof1, ): void {
        };
        $arguments = $validator->getFilteredArguments(
            $closure,
            [
                'oneof1' => 'd',
            ]
        );
        $this->assertSame(null, $arguments['oneof1']);
    }

    public function testStrictTypeCheck(): void
    {
        $this->expectException(TypeError::class);
        $validator = new Validator();
        $closure = function (#[OneOf(['a', 'b', 'c'])] string $oneof1, ): void {
        };
        $arguments = $validator->getFilteredArguments(
            $closure,
            [
                'oneof1' => '',
            ]
        );
        $this->assertSame(null, $arguments['oneof1']);
        $closure->call($this, $arguments); // null cannot be passed as string, and there's no other default available for $oneof1
    }

    public function testInvalidInteger(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsInt] int $int1): void {
            },
            [
                'int1' => 'not-an-integer'
            ]
        );
    }

    public function testInvalidBoolean(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsBool] int $bool1): void {
            },
            [
                'bool1' => 'not-a-boolean'
            ]
        );
    }

    public function testInvalidFloat(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsFloat] int $float1): void {
            },
            [
                'float1' => 'not-a-float'
            ]
        );
    }

    public function testInvalidEmail(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsEmail] int $email1): void {
            },
            [
                'email1' => 'not-an-email'
            ]
        );
    }

    public function testInvalidOneOf(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsOneOf(['a', 'b', 'c'])] string $oneof1): void {
            },
            [
                'oneof1' => 'd'
            ]
        );
    }

    public function testInvalidUuid(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsUuid] int $uuid1): void {
            },
            [
                'uuid1' => 'not-a-uuid'
            ]
        );
    }
    public function testInvalidRegExp(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsRegExpMatch('/^[a-z0-9_]+$/')] string $regexp1): void {
            },
            [
                'regexp1' => 'invalid value'
            ]
        );
    }
    public function testInvalidUrl(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsUrl] string $regexp1): void {
            },
            [
                'regexp1' => 'invalid url'
            ]
        );
    }
    public function testInvalidNotEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);
        $validator = new Validator();
        $validator->getFilteredArguments(
            function (#[IsNotEmpty] string $regexp1): void {
            },
            [
                'regexp1' => ''
            ]
        );
    }

}