<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator\Constraints\Custom;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * CustomTest class
 *
 * Unit tests for the Custom constraint class.
 */
class CustomTest extends TestCase
{
    #[Test]
    public function constructorAcceptsCallable(): void
    {
        $custom = new Custom(function (mixed $value, Closure $fail): void {
            // noop
        });

        $this->assertInstanceOf(Custom::class, $custom);
    }

    #[Test]
    public function constructorAcceptsArrayWithCallbackKey(): void
    {
        $custom = new Custom([
            'callback' => function (mixed $value, Closure $fail): void {
                // noop
            },
            'message' => 'Custom message',
        ]);

        $this->assertSame('Custom message', $custom->message);
    }

    #[Test]
    public function constructorAcceptsStringMessageWithCallbackParam(): void
    {
        $callback = function (mixed $value, Closure $fail): void {
            // noop
        };

        $custom = new Custom('Error occurred', $callback);

        $this->assertSame('Error occurred', $custom->message);
    }

    #[Test]
    public function constructorThrowsWhenNoValidCallbackProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Custom(['message' => 'No callback']);
    }

    #[Test]
    public function constructorThrowsWhenStringWithoutCallback(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Custom('Error message');
    }

    #[Test]
    public function validateExecutesCallback(): void
    {
        $executed = false;
        $custom = new Custom(function (mixed $value, Closure $fail) use (&$executed): void {
            $executed = true;
        });

        $custom->withValue('test');
        $custom->performValidation();

        $this->assertTrue($executed);
    }

    #[Test]
    public function validatePassesValueToCallback(): void
    {
        $received = null;
        $custom = new Custom(function (mixed $value, Closure $fail) use (&$received): void {
            $received = $value;
        });

        $custom->withValue('hello');
        $custom->performValidation();

        $this->assertSame('hello', $received);
    }

    #[Test]
    public function validateFailSetsMessage(): void
    {
        $custom = new Custom(function (mixed $value, Closure $fail): void {
            $fail('Value is bad');
        });

        $custom->withValue('x');
        $result = $custom->performValidation();

        $this->assertFalse($result);
        $this->assertSame('Value is bad', $custom->getFailMessage());
    }

    #[Test]
    public function validatePassesWhenFailNotCalled(): void
    {
        $custom = new Custom(function (mixed $value, Closure $fail): void {
            // all good
        });

        $custom->withValue('valid');
        $result = $custom->performValidation();

        $this->assertTrue($result);
    }

    #[Test]
    public function constructorAcceptsGroups(): void
    {
        $custom = new Custom(
            function (mixed $value, Closure $fail): void {
            },
            groups: ['registration']
        );

        $this->assertContains('registration', $custom->groups);
    }

    #[Test]
    public function defaultMessageIsSet(): void
    {
        $custom = new Custom(function (mixed $value, Closure $fail): void {
        });

        $this->assertSame('Invalid: {{ value }}.', $custom->message);
    }

    #[Test]
    public function arrayOptionsOverrideDefaultMessage(): void
    {
        $custom = new Custom([
            'callback' => function (mixed $value, Closure $fail): void {
            },
            'message' => 'Overridden',
        ]);

        $this->assertSame('Overridden', $custom->message);
    }
}
