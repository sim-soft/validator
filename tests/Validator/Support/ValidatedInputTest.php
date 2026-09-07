<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator\Support\ValidatedInput;

/**
 * ValidatedInputTest class
 *
 * Unit tests for the ValidatedInput support class.
 */
class ValidatedInputTest extends TestCase
{
    #[Test]
    public function isEmptyReturnsTrueWhenNoData(): void
    {
        $input = new ValidatedInput();
        $this->assertTrue($input->isEmpty());
    }

    #[Test]
    public function isEmptyReturnsFalseAfterAdd(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'John');
        $this->assertFalse($input->isEmpty());
    }

    #[Test]
    public function addAndGetStoreAndRetrieveValue(): void
    {
        $input = new ValidatedInput();
        $input->add('email', 'test@example.com');

        $this->assertSame('test@example.com', $input->get('email'));
    }

    #[Test]
    public function getReturnsNullForNonexistentAttribute(): void
    {
        $input = new ValidatedInput();
        $this->assertNull($input->get('missing'));
    }

    #[Test]
    public function allReturnsAllData(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('age', 30);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $input->all());
    }

    #[Test]
    public function allReturnsEmptyArrayWhenEmpty(): void
    {
        $input = new ValidatedInput();
        $this->assertSame([], $input->all());
    }

    #[Test]
    public function onlyReturnsSubsetOfData(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('email', 'alice@test.com');
        $input->add('age', 25);

        $this->assertSame(
            ['name' => 'Alice', 'email' => 'alice@test.com'],
            $input->only(['name', 'email'])
        );
    }

    #[Test]
    public function onlyReturnsAllDataWhenEmptyArray(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');

        $this->assertSame(['name' => 'Alice'], $input->only([]));
    }

    #[Test]
    public function onlyIgnoresNonexistentKeys(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');

        $this->assertSame(['name' => 'Alice'], $input->only(['name', 'missing']));
    }

    #[Test]
    public function exceptExcludesSpecifiedKeys(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('email', 'alice@test.com');
        $input->add('age', 25);

        $this->assertSame(
            ['name' => 'Alice', 'age' => 25],
            $input->except(['email'])
        );
    }

    #[Test]
    public function exceptReturnsAllDataWhenEmptyArray(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');

        $this->assertSame(['name' => 'Alice'], $input->except([]));
    }

    #[Test]
    public function exceptIgnoresNonexistentKeys(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');

        $this->assertSame(['name' => 'Alice'], $input->except(['missing']));
    }

    #[Test]
    public function addOverwritesExistingKey(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('name', 'Bob');

        $this->assertSame('Bob', $input->get('name'));
    }

    #[Test]
    public function addHandlesNullValue(): void
    {
        $input = new ValidatedInput();
        $input->add('field', null);

        $this->assertNull($input->get('field'));
        $this->assertFalse($input->isEmpty());
    }

    #[Test]
    public function iteratorTraversesAllData(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('email', 'alice@test.com');

        $result = [];
        foreach ($input as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(['name' => 'Alice', 'email' => 'alice@test.com'], $result);
    }

    #[Test]
    public function iteratorWorksOnEmptyData(): void
    {
        $input = new ValidatedInput();
        $result = [];
        foreach ($input as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([], $result);
    }

    #[Test]
    public function constructorAcceptsInitialData(): void
    {
        $input = new ValidatedInput(['key' => 'value']);

        $this->assertSame('value', $input->get('key'));
        $this->assertFalse($input->isEmpty());
    }

    #[Test]
    public function iteratorHandlesFalsyValues(): void
    {
        $input = new ValidatedInput();
        $input->add('zero', 0);
        $input->add('empty', '');
        $input->add('null', null);
        $input->add('false', false);

        $result = [];
        foreach ($input as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame([
            'zero' => 0,
            'empty' => '',
            'null' => null,
            'false' => false,
        ], $result);
    }

    #[Test]
    public function resetClearsAllData(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->reset();

        $this->assertTrue($input->isEmpty());
        $this->assertSame([], $input->all());
    }

    #[Test]
    public function hasReturnsTrueForExistingAttribute(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');

        $this->assertTrue($input->has('name'));
    }

    #[Test]
    public function hasReturnsFalseForMissingAttribute(): void
    {
        $input = new ValidatedInput();

        $this->assertFalse($input->has('missing'));
    }

    #[Test]
    public function hasReturnsTrueForNullValue(): void
    {
        $input = new ValidatedInput();
        $input->add('field', null);

        $this->assertTrue($input->has('field'));
    }

    #[Test]
    public function countReturnsNumberOfAttributes(): void
    {
        $input = new ValidatedInput();
        $this->assertSame(0, count($input));

        $input->add('name', 'Alice');
        $this->assertSame(1, count($input));

        $input->add('email', 'alice@test.com');
        $this->assertSame(2, count($input));
    }

    #[Test]
    public function toArrayReturnsAllData(): void
    {
        $input = new ValidatedInput();
        $input->add('name', 'Alice');
        $input->add('age', 30);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $input->toArray());
    }
}
