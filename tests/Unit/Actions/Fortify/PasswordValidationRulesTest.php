<?php

use App\Actions\Fortify\PasswordValidationRules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

uses(Tests\TestCase::class);

/**
 * @group actions
 * @group fortify
 * @group password-validation
 * @group unit
 */

beforeEach(function () {
    // Create anonymous class that uses the trait for testing
    $this->class = new class {
        use PasswordValidationRules;
        
        public function getRules(): array
        {
            return $this->passwordRules();
        }
    };
});

describe('password rules structure', function () {
    test('password rules returns array', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Returns array
        expect($rules)->toBeArray()
            ->and($rules)->not->toBeEmpty();
    });

    test('password rules includes required rule', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Contains 'required'
        expect($rules)->toContain('required');
    });

    test('password rules includes string rule', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Contains 'string'
        expect($rules)->toContain('string');
    });

    test('password rules includes password default rule', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Contains Password::default() instance
        $hasPasswordRule = collect($rules)->contains(function ($rule) {
            return $rule instanceof Password;
        });
        
        expect($hasPasswordRule)->toBeTrue();
    });

    test('password rules includes confirmed rule', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Contains 'confirmed'
        expect($rules)->toContain('confirmed');
    });

    test('password rules contains exactly four elements', function () {
        // Act: Get password rules
        $rules = $this->class->getRules();
        
        // Assert: Has exactly 4 rules
        expect($rules)->toHaveCount(4);
    });
});

describe('validation behavior', function () {
    test('password rules enforces minimum length', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator with too-short password
        $validator = Validator::make([
            'password' => 'short',
            'password_confirmation' => 'short',
        ], $rules);
        
        // Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('password rules enforces confirmation match', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator with mismatched confirmation
        $validator = Validator::make([
            'password' => 'validpassword123',
            'password_confirmation' => 'differentpassword',
        ], $rules);
        
        // Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('password rules can be used in validator', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator with valid input
        $validator = Validator::make([
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ], $rules);
        
        // Assert: Validation passes
        expect($validator->passes())->toBeTrue()
            ->and($validator->errors()->isEmpty())->toBeTrue();
    });

    test('password rules rejects missing password', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator without password
        $validator = Validator::make([
            'password_confirmation' => 'somepassword',
        ], $rules);
        
        // Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('password rules rejects missing confirmation', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator without confirmation
        $validator = Validator::make([
            'password' => 'validpassword123',
        ], $rules);
        
        // Assert: Validation fails for missing confirmation
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('password rules accepts valid password with confirmation', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Create validator with all valid input
        $validator = Validator::make([
            'password' => 'MySecurePassword123',
            'password_confirmation' => 'MySecurePassword123',
        ], $rules);
        
        // Assert: Validation passes
        expect($validator->passes())->toBeTrue();
    });
});

describe('integration tests', function () {
    test('trait can be used in multiple classes', function () {
        // Arrange: Create two different classes using the trait
        $class1 = new class {
            use PasswordValidationRules;
            public function getRules() { return $this->passwordRules(); }
        };
        
        $class2 = new class {
            use PasswordValidationRules;
            public function getRules() { return $this->passwordRules(); }
        };
        
        // Act: Get rules from both classes
        $rules1 = $class1->getRules();
        $rules2 = $class2->getRules();
        
        // Assert: Both return same structure
        expect($rules1)->toHaveCount(4)
            ->and($rules2)->toHaveCount(4)
            ->and($rules1)->toContain('required', 'string', 'confirmed')
            ->and($rules2)->toContain('required', 'string', 'confirmed');
    });

    test('password rules work with complex validation scenarios', function () {
        // Arrange: Complex validation rules including password
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => $this->class->getRules(),
        ];
        
        // Act: Validate complex data
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ], $rules);
        
        // Assert: All validation passes
        expect($validator->passes())->toBeTrue();
    });

    test('password rules fail appropriately in complex validation', function () {
        // Arrange: Complex validation rules including password
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => $this->class->getRules(),
        ];
        
        // Act: Validate with invalid password
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ], $rules);
        
        // Assert: Password validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeFalse()
            ->and($validator->errors()->has('email'))->toBeFalse();
    });
});

describe('edge cases', function () {
    test('password rules handle empty string', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Validate empty password
        $validator = Validator::make([
            'password' => '',
            'password_confirmation' => '',
        ], $rules);
        
        // Assert: Validation fails (required rule)
        expect($validator->fails())->toBeTrue();
    });

    test('password rules handle null value', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Validate null password
        $validator = Validator::make([
            'password' => null,
            'password_confirmation' => null,
        ], $rules);
        
        // Assert: Validation fails (required rule)
        expect($validator->fails())->toBeTrue();
    });

    test('password rules handle non-string value', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Validate numeric password
        $validator = Validator::make([
            'password' => 12345678,
            'password_confirmation' => 12345678,
        ], $rules);
        
        // Assert: Validation fails (string rule)
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue();
    });

    test('password rules are case sensitive for confirmation', function () {
        // Arrange: Get password rules
        $rules = ['password' => $this->class->getRules()];
        
        // Act: Validate with different case in confirmation
        $validator = Validator::make([
            'password' => 'SecurePassword123',
            'password_confirmation' => 'securepassword123',
        ], $rules);
        
        // Assert: Validation fails (exact match required)
        expect($validator->fails())->toBeTrue();
    });
});
