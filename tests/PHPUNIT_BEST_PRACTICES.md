# PHPUnit Best Practices for Unit Tests

> **Purpose**: Comprehensive unit testing guide with extensive examples, assertions reference, and advanced mocking patterns. For quick reference and common patterns, see [README.md](./README.md).

**Last Updated**: January 2026  
**PHPUnit Version**: 11.x  
**Project**: RD Blog

This document serves as the authoritative reference for:

- **Complete assertion catalog** (equality, types, collections, strings, numbers, exceptions)
- **Advanced mocking patterns** (stubs, spies, mocks, fakes, partial mocks)
- **Test doubles** (when and how to use each type)
- **Dependency injection** (making code testable)
- **Anti-patterns** (comprehensive examples of what to avoid)
- **Testing private methods, abstract classes, value objects**

This guide provides in-depth explanations and extensive examples beyond the quick-reference patterns in the README.

## Table of Contents

1. [Overview](#overview)
2. [Unit Test Principles](#unit-test-principles)
3. [Test Structure](#test-structure)
4. [Naming Conventions](#naming-conventions)
5. [Test Organization](#test-organization)
6. [Mocking and Stubs](#mocking-and-stubs)
7. [Assertions](#assertions)
8. [Data Providers](#data-providers)
9. [Test Doubles](#test-doubles)
10. [Dependencies and Isolation](#dependencies-and-isolation)
11. [Performance Considerations](#performance-considerations)
12. [Common Patterns](#common-patterns)
13. [Anti-Patterns to Avoid](#anti-patterns-to-avoid)

## Overview

Unit tests verify isolated pieces of code without external dependencies (database, filesystem, network, etc.). They are fast, focused, and help ensure individual components work correctly.

### Unit Tests vs Feature Tests

| Aspect | Unit Tests | Feature Tests |
|--------|-----------|---------------|
| **Scope** | Single class/method | Multiple components |
| **Dependencies** | Mocked/stubbed | Real (database, etc.) |
| **Speed** | Very fast (milliseconds) | Slower (seconds) |
| **Database** | Never touches database | Uses test database |
| **Filesystem** | Never touches filesystem | May use fake storage |
| **Network** | Never makes requests | May make HTTP requests |
| **Focus** | Business logic | User workflows |

## Unit Test Principles

### 1. Test One Thing at a Time

Each test should verify a single behavior or outcome.

**Good:**
```php
public function test_calculate_discount_returns_correct_amount(): void
{
    $calculator = new PriceCalculator();
    
    $result = $calculator->calculateDiscount(100, 10);
    
    $this->assertEquals(10, $result);
}

public function test_calculate_discount_handles_zero_percent(): void
{
    $calculator = new PriceCalculator();
    
    $result = $calculator->calculateDiscount(100, 0);
    
    $this->assertEquals(0, $result);
}
```

**Bad:**
```php
public function test_calculate_discount(): void
{
    $calculator = new PriceCalculator();
    
    // Testing multiple scenarios in one test
    $this->assertEquals(10, $calculator->calculateDiscount(100, 10));
    $this->assertEquals(0, $calculator->calculateDiscount(100, 0));
    $this->assertEquals(25, $calculator->calculateDiscount(100, 25));
}
```

### 2. Isolate External Dependencies

Unit tests should not depend on databases, filesystems, APIs, or other external services.

**Good:**
```php
public function test_send_notification_calls_notifier(): void
{
    $notifier = $this->createMock(NotificationService::class);
    $notifier->expects($this->once())
        ->method('send')
        ->with($this->equalTo('test@example.com'));
    
    $service = new UserService($notifier);
    $service->sendWelcomeEmail('test@example.com');
}
```

**Bad:**
```php
public function test_send_notification(): void
{
    $service = new UserService(); // Uses real email service
    $service->sendWelcomeEmail('test@example.com'); // Actually sends email
    
    // How do we verify this worked?
}
```

### 3. Make Tests Independent

Tests should not depend on each other or share state.

**Good:**
```php
public function test_add_item_increases_count(): void
{
    $cart = new ShoppingCart();
    $cart->addItem('item1');
    
    $this->assertEquals(1, $cart->count());
}

public function test_remove_item_decreases_count(): void
{
    $cart = new ShoppingCart();
    $cart->addItem('item1');
    $cart->removeItem('item1');
    
    $this->assertEquals(0, $cart->count());
}
```

**Bad:**
```php
private ShoppingCart $cart;

public function test_add_item_increases_count(): void
{
    $this->cart = new ShoppingCart();
    $this->cart->addItem('item1');
    
    $this->assertEquals(1, $this->cart->count());
}

public function test_remove_item_decreases_count(): void
{
    // Depends on previous test running first!
    $this->cart->removeItem('item1');
    $this->assertEquals(0, $this->cart->count());
}
```

### 4. Test Behavior, Not Implementation

Focus on what the code does, not how it does it.

**Good:**
```php
public function test_format_price_returns_correct_format(): void
{
    $formatter = new PriceFormatter();
    
    $result = $formatter->format(1234.56);
    
    $this->assertEquals('$1,234.56', $result);
}
```

**Bad:**
```php
public function test_format_price_calls_internal_methods(): void
{
    $formatter = $this->getMockBuilder(PriceFormatter::class)
        ->onlyMethods(['addDollarSign', 'addCommas'])
        ->getMock();
    
    // Testing implementation details, not behavior
    $formatter->expects($this->once())->method('addDollarSign');
    $formatter->expects($this->once())->method('addCommas');
    
    $formatter->format(1234.56);
}
```

## Test Structure

### Arrange-Act-Assert (AAA) Pattern

Structure tests with three clear sections:

```php
public function test_calculate_total_with_tax(): void
{
    // Arrange: Set up test data and dependencies
    $calculator = new PriceCalculator();
    $subtotal = 100.00;
    $taxRate = 0.08;
    
    // Act: Execute the behavior being tested
    $result = $calculator->calculateTotal($subtotal, $taxRate);
    
    // Assert: Verify the outcome
    $this->assertEquals(108.00, $result);
}
```

### Given-When-Then (BDD Style)

Alternative structure emphasizing behavior:

```php
public function test_user_receives_discount_after_ten_purchases(): void
{
    // Given: A user with 10 previous purchases
    $user = new User();
    $user->setPurchaseCount(10);
    $discountCalculator = new DiscountCalculator();
    
    // When: They make a new purchase
    $discount = $discountCalculator->calculateDiscount($user);
    
    // Then: They receive a 10% discount
    $this->assertEquals(0.10, $discount);
}
```

### setUp() and tearDown()

Use for common test preparation and cleanup:

```php
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private Calculator $calculator;
    
    protected function setUp(): void
    {
        // Runs before each test
        $this->calculator = new Calculator();
    }
    
    protected function tearDown(): void
    {
        // Runs after each test (cleanup)
        $this->calculator = null;
    }
    
    public function test_add_returns_sum(): void
    {
        $result = $this->calculator->add(2, 3);
        
        $this->assertEquals(5, $result);
    }
}
```

## Naming Conventions

### Test Method Names

Use descriptive names that explain the scenario and expected outcome:

**Good Examples:**
```php
public function test_calculate_discount_with_valid_percentage(): void
public function test_throw_exception_when_percentage_is_negative(): void
public function test_return_zero_when_amount_is_zero(): void
public function test_format_currency_with_two_decimal_places(): void
```

**Alternative Naming Styles:**

1. **Snake Case with `test_` prefix** (Recommended for PHPUnit):
   ```php
   public function test_user_can_be_created_with_valid_data(): void
   ```

2. **Camel Case with `test` prefix**:
   ```php
   public function testUserCanBeCreatedWithValidData(): void
   ```

3. **`@test` Annotation** (allows any name):
   ```php
   /** @test */
   public function user_can_be_created_with_valid_data(): void
   ```

### Class Names

Test class names should mirror the class being tested with `Test` suffix:

- Class: `UserService` → Test: `UserServiceTest`
- Class: `PriceCalculator` → Test: `PriceCalculatorTest`
- Class: `OrderProcessor` → Test: `OrderProcessorTest`

## Test Organization

### File Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── UserServiceTest.php
│   │   ├── OrderServiceTest.php
│   │   └── PaymentServiceTest.php
│   ├── Models/
│   │   ├── UserTest.php
│   │   ├── OrderTest.php
│   │   └── ProductTest.php
│   ├── Helpers/
│   │   ├── StringHelperTest.php
│   │   └── DateHelperTest.php
│   └── Validators/
│       ├── EmailValidatorTest.php
│       └── PhoneValidatorTest.php
└── Feature/
    └── (Feature tests)
```

### Group Related Tests

Use test suites or groups for related tests:

```php
/**
 * @group validators
 * @group email
 */
class EmailValidatorTest extends TestCase
{
    // Tests...
}
```

Run specific groups:
```bash
./vendor/bin/phpunit --group validators
```

## Mocking and Stubs

### When to Mock

Mock external dependencies that:
- Touch the database
- Make network requests
- Access the filesystem
- Send emails or notifications
- Call external APIs
- Have complex setup requirements

### Creating Mocks

**Basic Mock:**
```php
public function test_process_order_saves_to_repository(): void
{
    $repository = $this->createMock(OrderRepository::class);
    $repository->expects($this->once())
        ->method('save')
        ->with($this->isInstanceOf(Order::class));
    
    $processor = new OrderProcessor($repository);
    $processor->process(new Order());
}
```

**Mock with Return Value:**
```php
public function test_get_user_returns_user_from_repository(): void
{
    $user = new User(['id' => 1, 'name' => 'John']);
    
    $repository = $this->createMock(UserRepository::class);
    $repository->method('find')
        ->with(1)
        ->willReturn($user);
    
    $service = new UserService($repository);
    $result = $service->getUser(1);
    
    $this->assertSame($user, $result);
}
```

**Mock with Exception:**
```php
public function test_handle_repository_exception(): void
{
    $repository = $this->createMock(UserRepository::class);
    $repository->method('save')
        ->willThrowException(new DatabaseException());
    
    $service = new UserService($repository);
    
    $this->expectException(ServiceException::class);
    $service->createUser(['name' => 'John']);
}
```

### Stubs vs Mocks

**Stub** - Returns predefined values (state verification):
```php
$stub = $this->createStub(PaymentGateway::class);
$stub->method('charge')->willReturn(true);
```

**Mock** - Verifies methods were called (behavior verification):
```php
$mock = $this->createMock(PaymentGateway::class);
$mock->expects($this->once())
    ->method('charge')
    ->with(100.00);
```

### Partial Mocks

When you need to mock only some methods:

```php
public function test_process_uses_real_validate_but_mocked_save(): void
{
    $processor = $this->getMockBuilder(OrderProcessor::class)
        ->onlyMethods(['save'])  // Only mock save()
        ->getMock();
    
    $processor->expects($this->once())
        ->method('save');
    
    // validate() will use the real implementation
    $processor->process(['valid' => 'data']);
}
```

## Assertions

### Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Strict comparison (===)
$this->assertNotEquals($expected, $actual);

// Truth
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertNotNull($value);

// Types
$this->assertIsArray($value);
$this->assertIsString($value);
$this->assertIsInt($value);
$this->assertIsBool($value);
$this->assertIsObject($value);
$this->assertInstanceOf(User::class, $object);

// Collections
$this->assertCount(3, $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);
$this->assertContains('needle', $haystack);
$this->assertArrayHasKey('key', $array);

// Strings
$this->assertStringContainsString('substring', $string);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Numbers
$this->assertGreaterThan(10, $value);
$this->assertLessThan(100, $value);
$this->assertEqualsWithDelta(1.5, $actual, 0.01); // For floats

// Exceptions
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('Invalid email');
```

### Custom Assertions

Create readable assertions for complex conditions:

```php
class CustomAssertions extends TestCase
{
    protected function assertValidEmail(string $email, string $message = ''): void
    {
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $email,
            $message ?: "Failed asserting that '$email' is a valid email"
        );
    }
    
    protected function assertArrayContainsOnly(string $type, array $array): void
    {
        foreach ($array as $item) {
            $this->assertInstanceOf($type, $item);
        }
    }
}
```

## Data Providers

Use data providers to test the same logic with multiple inputs:

```php
/**
 * @dataProvider validEmailProvider
 */
public function test_validate_email_accepts_valid_formats(string $email): void
{
    $validator = new EmailValidator();
    
    $result = $validator->validate($email);
    
    $this->assertTrue($result);
}

public static function validEmailProvider(): array
{
    return [
        'simple email' => ['test@example.com'],
        'with dots' => ['first.last@example.com'],
        'with plus' => ['user+tag@example.com'],
        'subdomain' => ['user@mail.example.com'],
    ];
}
```

**Multiple Parameters:**
```php
/**
 * @dataProvider discountProvider
 */
public function test_calculate_discount(float $price, float $percent, float $expected): void
{
    $calculator = new PriceCalculator();
    
    $result = $calculator->calculateDiscount($price, $percent);
    
    $this->assertEquals($expected, $result);
}

public static function discountProvider(): array
{
    return [
        '10% of 100' => [100.00, 10, 10.00],
        '25% of 200' => [200.00, 25, 50.00],
        '0% of 100' => [100.00, 0, 0.00],
        '100% of 50' => [50.00, 100, 50.00],
    ];
}
```

## Test Doubles

### Types of Test Doubles

1. **Dummy**: Passed but never used
2. **Stub**: Provides predefined answers
3. **Spy**: Records how it was called
4. **Mock**: Verifies behavior expectations
5. **Fake**: Working implementation (simplified)

### Dummy Objects

```php
public function test_send_email_requires_recipient(): void
{
    $dummyTemplate = $this->createStub(EmailTemplate::class);
    $mailer = new Mailer();
    
    // Template is required but not used in this test
    $this->expectException(InvalidArgumentException::class);
    $mailer->send(null, $dummyTemplate);
}
```

### Fake Objects

```php
class FakeUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    
    public function save(User $user): void
    {
        $this->users[$user->id] = $user;
    }
    
    public function find(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }
}

public function test_user_service_with_fake_repository(): void
{
    $repository = new FakeUserRepository();
    $service = new UserService($repository);
    
    $user = new User(['id' => 1, 'name' => 'John']);
    $service->createUser($user);
    
    $found = $service->getUser(1);
    $this->assertEquals('John', $found->name);
}
```

## Dependencies and Isolation

### Dependency Injection

Make dependencies explicit for easier testing:

**Good (testable):**
```php
class OrderProcessor
{
    public function __construct(
        private OrderRepository $repository,
        private PaymentGateway $gateway
    ) {}
    
    public function process(Order $order): bool
    {
        $charged = $this->gateway->charge($order->total);
        if ($charged) {
            $this->repository->save($order);
            return true;
        }
        return false;
    }
}

// Easy to test with mocks
public function test_process_order(): void
{
    $repository = $this->createMock(OrderRepository::class);
    $gateway = $this->createMock(PaymentGateway::class);
    
    $processor = new OrderProcessor($repository, $gateway);
    // Test with controlled dependencies
}
```

**Bad (hard to test):**
```php
class OrderProcessor
{
    public function process(Order $order): bool
    {
        // Hard-coded dependencies
        $gateway = new StripeGateway();
        $repository = new DatabaseOrderRepository();
        
        // Can't test without real Stripe account and database
        $charged = $gateway->charge($order->total);
        if ($charged) {
            $repository->save($order);
            return true;
        }
        return false;
    }
}
```

### Testing Static Methods

Avoid static methods or make them testable:

**Problematic:**
```php
class Logger
{
    public static function log(string $message): void
    {
        file_put_contents('/var/log/app.log', $message);
    }
}

// Hard to test classes that use Logger::log()
```

**Better:**
```php
interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        file_put_contents('/var/log/app.log', $message);
    }
}

// Now you can inject a mock logger for testing
```

## Performance Considerations

### Keep Unit Tests Fast

- **Target**: < 100ms per test
- **Avoid**: Database, filesystem, network operations
- **Use**: Mocks and stubs for external dependencies
- **Minimize**: Object creation in loops

### Test Performance

```php
public function test_process_large_dataset_efficiently(): void
{
    $processor = new DataProcessor();
    $data = range(1, 10000);
    
    $start = microtime(true);
    $result = $processor->process($data);
    $duration = microtime(true) - $start;
    
    $this->assertLessThan(0.1, $duration); // Should complete in < 100ms
    $this->assertCount(10000, $result);
}
```

## Common Patterns

### Testing Private Methods

Test through public methods (preferred):

```php
class Calculator
{
    public function calculateTotal(array $items): float
    {
        return $this->sum($items) * $this->getTaxRate();
    }
    
    private function sum(array $items): float
    {
        return array_sum($items);
    }
    
    private function getTaxRate(): float
    {
        return 1.08;
    }
}

public function test_calculate_total(): void
{
    $calculator = new Calculator();
    
    // Tests private methods indirectly
    $result = $calculator->calculateTotal([10, 20, 30]);
    
    $this->assertEquals(64.8, $result);
}
```

If you must test private methods directly:

```php
public function test_private_method_directly(): void
{
    $calculator = new Calculator();
    $reflection = new ReflectionClass($calculator);
    $method = $reflection->getMethod('sum');
    $method->setAccessible(true);
    
    $result = $method->invokeArgs($calculator, [[10, 20, 30]]);
    
    $this->assertEquals(60, $result);
}
```

### Testing Abstract Classes

```php
abstract class BaseValidator
{
    abstract protected function getRules(): array;
    
    public function validate(array $data): bool
    {
        foreach ($this->getRules() as $rule) {
            if (!$rule->passes($data)) {
                return false;
            }
        }
        return true;
    }
}

public function test_base_validator_validation_logic(): void
{
    $validator = new class extends BaseValidator {
        protected function getRules(): array
        {
            return [
                new RequiredRule('name'),
                new EmailRule('email'),
            ];
        }
    };
    
    $result = $validator->validate(['name' => 'John', 'email' => 'john@example.com']);
    
    $this->assertTrue($result);
}
```

### Testing Exception Handling

```php
public function test_throw_exception_on_invalid_input(): void
{
    $validator = new EmailValidator();
    
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email format');
    
    $validator->validate('not-an-email');
}

public function test_handle_exception_gracefully(): void
{
    $gateway = $this->createMock(PaymentGateway::class);
    $gateway->method('charge')
        ->willThrowException(new PaymentException('Card declined'));
    
    $processor = new OrderProcessor($gateway);
    
    $result = $processor->process(new Order());
    
    $this->assertFalse($result);
}
```

### Testing Value Objects

```php
public function test_email_value_object_equality(): void
{
    $email1 = new Email('test@example.com');
    $email2 = new Email('test@example.com');
    $email3 = new Email('other@example.com');
    
    $this->assertTrue($email1->equals($email2));
    $this->assertFalse($email1->equals($email3));
}

public function test_money_value_object_arithmetic(): void
{
    $money1 = new Money(100, 'USD');
    $money2 = new Money(50, 'USD');
    
    $result = $money1->add($money2);
    
    $this->assertEquals(150, $result->amount());
    $this->assertEquals('USD', $result->currency());
}
```

## Anti-Patterns to Avoid

### 1. Testing Multiple Behaviors

**Bad:**
```php
public function test_user_operations(): void
{
    $user = new User();
    $user->setName('John');
    $this->assertEquals('John', $user->getName());
    
    $user->setEmail('john@example.com');
    $this->assertEquals('john@example.com', $user->getEmail());
    
    $user->activate();
    $this->assertTrue($user->isActive());
}
```

**Good:** Split into separate tests for each behavior.

### 2. Test Interdependence

**Bad:**
```php
class UserTest extends TestCase
{
    private static User $user;
    
    public function test_01_create_user(): void
    {
        self::$user = new User('John');
        $this->assertNotNull(self::$user);
    }
    
    public function test_02_update_user(): void
    {
        // Depends on test_01 running first!
        self::$user->setName('Jane');
        $this->assertEquals('Jane', self::$user->getName());
    }
}
```

**Good:** Each test creates its own test data.

### 3. Testing Implementation Details

**Bad:**
```php
public function test_internal_cache_is_set(): void
{
    $service = new UserService();
    $service->getUser(1);
    
    // Testing internal implementation
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('cache');
    $property->setAccessible(true);
    
    $this->assertArrayHasKey(1, $property->getValue($service));
}
```

**Good:** Test the behavior (faster subsequent calls), not the cache implementation.

### 4. Excessive Mocking

**Bad:**
```php
public function test_process_order_with_too_many_mocks(): void
{
    $validator = $this->createMock(Validator::class);
    $formatter = $this->createMock(Formatter::class);
    $transformer = $this->createMock(Transformer::class);
    $logger = $this->createMock(Logger::class);
    // ... 10 more mocks
    
    // Test becomes brittle and hard to maintain
}
```

**Good:** Consider if this should be a feature test, or if the class has too many dependencies.

### 5. Ignoring Test Failures

**Bad:**
```php
public function test_flaky_behavior(): void
{
    $result = $this->service->process();
    
    // Sometimes fails, so we just skip it
    $this->markTestSkipped('This test is flaky');
}
```

**Good:** Fix the flakiness or remove the test.

### 6. Testing Framework Code

**Bad:**
```php
public function test_array_sum_works(): void
{
    $result = array_sum([1, 2, 3]);
    $this->assertEquals(6, $result);
}
```

**Good:** Don't test PHP's built-in functions or framework code. Test YOUR code.

## Example: Complete Unit Test

```php
<?php

namespace Tests\Unit\Services;

use App\Services\DiscountCalculator;
use App\Repositories\UserRepository;
use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * @group services
 * @group discounts
 */
class DiscountCalculatorTest extends TestCase
{
    private DiscountCalculator $calculator;
    private UserRepository $userRepository;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->calculator = new DiscountCalculator($this->userRepository);
    }
    
    public function test_calculate_discount_for_new_user(): void
    {
        // Arrange
        $user = new User(['purchase_count' => 0]);
        
        // Act
        $discount = $this->calculator->calculate($user, 100.00);
        
        // Assert
        $this->assertEquals(0.0, $discount);
    }
    
    /**
     * @dataProvider loyaltyDiscountProvider
     */
    public function test_calculate_loyalty_discount(int $purchases, float $expectedPercent): void
    {
        // Arrange
        $user = new User(['purchase_count' => $purchases]);
        $amount = 100.00;
        
        // Act
        $discount = $this->calculator->calculate($user, $amount);
        
        // Assert
        $expectedDiscount = $amount * $expectedPercent;
        $this->assertEquals($expectedDiscount, $discount);
    }
    
    public function test_calculate_discount_throws_exception_for_negative_amount(): void
    {
        // Arrange
        $user = new User(['purchase_count' => 5]);
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');
        
        // Act
        $this->calculator->calculate($user, -100.00);
    }
    
    public static function loyaltyDiscountProvider(): array
    {
        return [
            '5 purchases = 5%' => [5, 0.05],
            '10 purchases = 10%' => [10, 0.10],
            '20 purchases = 15%' => [20, 0.15],
            '50 purchases = 20%' => [50, 0.20],
        ];
    }
}
```

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Test Doubles by Martin Fowler](https://martinfowler.com/bliki/TestDouble.html)
- [FIRST Principles](https://github.com/ghsukumar/SFDC_Best_Practices/wiki/F.I.R.S.T-Principles-of-Unit-Testing)
- [Testing Best Practices by PHPUnit](https://phpunit.readthedocs.io/en/latest/writing-tests-for-phpunit.html)

