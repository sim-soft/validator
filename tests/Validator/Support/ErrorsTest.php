<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Validator\Support\Errors;

/**
 * ErrorsTest class
 *
 * Unit tests for the Errors support class.
 */
class ErrorsTest extends TestCase
{
    #[Test]
    public function isEmptyReturnsTrueWhenNoErrors(): void
    {
        $errors = new Errors();
        $this->assertTrue($errors->isEmpty());
    }

    #[Test]
    public function isEmptyReturnsFalseAfterAddingError(): void
    {
        $errors = new Errors();
        $errors->add('email', 'Email is required');
        $this->assertFalse($errors->isEmpty());
    }

    #[Test]
    public function addStoresErrorUnderAttribute(): void
    {
        $errors = new Errors();
        $errors->add('name', 'Name is required');

        $this->assertSame(['name' => ['Name is required']], $errors->all());
    }

    #[Test]
    public function addDeduplicatesIdenticalMessages(): void
    {
        $errors = new Errors();
        $errors->add('email', 'Invalid email');
        $errors->add('email', 'Invalid email');

        $this->assertSame(['email' => ['Invalid email']], $errors->all());
    }

    #[Test]
    public function addAccumulatesMultipleDistinctMessages(): void
    {
        $errors = new Errors();
        $errors->add('password', 'Too short');
        $errors->add('password', 'Missing digit');

        $this->assertSame(['password' => ['Too short', 'Missing digit']], $errors->all());
    }

    #[Test]
    public function firstReturnsFirstMessageForAttribute(): void
    {
        $errors = new Errors();
        $errors->add('email', 'First error');
        $errors->add('email', 'Second error');

        $this->assertSame('First error', $errors->first('email'));
    }

    #[Test]
    public function firstReturnsNullForNonexistentAttribute(): void
    {
        $errors = new Errors();
        $this->assertNull($errors->first('missing'));
    }

    #[Test]
    public function hasReturnsTrueWhenAttributeHasErrors(): void
    {
        $errors = new Errors();
        $errors->add('field', 'Error');

        $this->assertTrue($errors->has('field'));
    }

    #[Test]
    public function hasReturnsFalseWhenAttributeHasNoErrors(): void
    {
        $errors = new Errors();
        $this->assertFalse($errors->has('field'));
    }

    #[Test]
    public function allReturnsEmptyArrayWhenNoErrors(): void
    {
        $errors = new Errors();
        $this->assertSame([], $errors->all());
    }

    #[Test]
    public function getYieldsMessagesForExistingAttribute(): void
    {
        $errors = new Errors();
        $errors->add('name', 'Too short');
        $errors->add('name', 'Invalid chars');

        $messages = iterator_to_array($errors->get('name'));
        $this->assertSame(['Too short', 'Invalid chars'], $messages);
    }

    #[Test]
    public function getYieldsNothingForNonexistentAttribute(): void
    {
        $errors = new Errors();
        $messages = iterator_to_array($errors->get('missing'));
        $this->assertSame([], $messages);
    }

    #[Test]
    public function iteratorTraversesAllAttributes(): void
    {
        $errors = new Errors();
        $errors->add('email', 'Invalid');
        $errors->add('name', 'Required');

        $result = [];
        foreach ($errors as $key => $messages) {
            $result[$key] = $messages;
        }

        $this->assertSame([
            'email' => ['Invalid'],
            'name' => ['Required'],
        ], $result);
    }

    #[Test]
    public function iteratorWorksOnEmptyErrors(): void
    {
        $errors = new Errors();
        $result = [];
        foreach ($errors as $key => $messages) {
            $result[$key] = $messages;
        }

        $this->assertSame([], $result);
    }

    #[Test]
    public function constructorAcceptsPreloadedErrors(): void
    {
        $errors = new Errors(['email' => ['Bad email']]);

        $this->assertFalse($errors->isEmpty());
        $this->assertSame('Bad email', $errors->first('email'));
    }

    #[Test]
    public function addReturnsSelfForChaining(): void
    {
        $errors = new Errors();
        $result = $errors->add('field', 'msg');

        $this->assertSame($errors, $result);
    }

    #[Test]
    public function resetClearsAllErrors(): void
    {
        $errors = new Errors();
        $errors->add('field', 'error');
        $errors->reset();

        $this->assertTrue($errors->isEmpty());
        $this->assertSame([], $errors->all());
    }

    #[Test]
    public function countReturnsNumberOfAttributesWithErrors(): void
    {
        $errors = new Errors();
        $this->assertSame(0, count($errors));

        $errors->add('email', 'Invalid');
        $this->assertSame(1, count($errors));

        $errors->add('name', 'Required');
        $this->assertSame(2, count($errors));

        $errors->add('email', 'Too long');
        $this->assertSame(2, count($errors));
    }

    #[Test]
    public function toArrayReturnsAllErrors(): void
    {
        $errors = new Errors();
        $errors->add('email', 'Invalid');
        $errors->add('name', 'Required');

        $this->assertSame([
            'email' => ['Invalid'],
            'name' => ['Required'],
        ], $errors->toArray());
    }
}
