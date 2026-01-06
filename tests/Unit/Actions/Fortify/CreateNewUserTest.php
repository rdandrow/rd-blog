<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->action = new CreateNewUser();
});

describe('user creation', function () {
    test('creates user with valid input', function () {
        // Arrange: Valid user input
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: User created with correct attributes
        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com')
            ->and($user->exists)->toBeTrue();
    });

    test('hashes password before storing', function () {
        // Arrange: Valid user input with known password
        $input = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'mypassword123',
            'password_confirmation' => 'mypassword123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: Password is hashed (not plain text)
        expect($user->password)->not->toBe('mypassword123')
            ->and(Hash::check('mypassword123', $user->password))->toBeTrue();
    });

    test('assigns default member role', function () {
        // Arrange: Valid user input without role specified
        $input = [
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        $user->refresh(); // Refresh to get default value from database
        
        // Assert: Role defaults to member
        expect($user->role)->toBe('member');
    });

    test('stores all required user attributes', function () {
        // Arrange: Valid user input
        $input = [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => 'securepass123',
            'password_confirmation' => 'securepass123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: All attributes stored correctly
        $this->assertDatabaseHas('users', [
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'role' => 'member',
        ]);
        
        expect($user->id)->not->toBeNull()
            ->and($user->created_at)->not->toBeNull()
            ->and($user->updated_at)->not->toBeNull();
    });
});

describe('name validation', function () {
    test('validates name is required', function () {
        // Arrange: Input without name
        $input = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates name max length 255', function () {
        // Arrange: Input with name exceeding 255 characters
        $input = [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('handles whitespace in name', function () {
        // Arrange: Input with name containing whitespace
        $input = [
            'name' => '  John   Doe  ',
            'email' => 'whitespace@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: Name stored as-is (no automatic trimming by action)
        expect($user->name)->toBe('  John   Doe  ');
    });
});

describe('email validation', function () {
    test('validates email is required', function () {
        // Arrange: Input without email
        $input = [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates email format', function () {
        // Arrange: Input with invalid email format
        $input = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates email max length 255', function () {
        // Arrange: Input with email exceeding 255 characters
        $longEmail = str_repeat('a', 247) . '@test.com'; // 257 chars total
        $input = [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates email is unique', function () {
        // Arrange: Create existing user
        User::factory()->create(['email' => 'existing@example.com']);
        
        $input = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act & Assert: Expect validation exception for duplicate email
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('handles uppercase in email', function () {
        // Arrange: Input with uppercase email
        $input = [
            'name' => 'John Doe',
            'email' => 'UPPERCASE@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: Email stored as provided (no automatic lowercase conversion)
        expect($user->email)->toBe('UPPERCASE@EXAMPLE.COM');
    });
});

describe('password validation', function () {
    test('validates password is required', function () {
        // Arrange: Input without password
        $input = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates password confirmation matches', function () {
        // Arrange: Input with mismatched password confirmation
        $input = [
            'name' => 'John Doe',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('validates password meets minimum requirements', function () {
        // Arrange: Input with weak password (too short)
        $input = [
            'name' => 'John Doe',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
        
        // Act & Assert: Expect validation exception
        expect(fn() => $this->action->create($input))
            ->toThrow(ValidationException::class);
    });

    test('accepts password meeting requirements', function () {
        // Arrange: Input with valid password
        $input = [
            'name' => 'John Doe',
            'email' => 'valid@example.com',
            'password' => 'validpassword123',
            'password_confirmation' => 'validpassword123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: User created successfully
        expect($user->exists)->toBeTrue();
    });
});

describe('edge cases', function () {
    test('accepts valid name at max length', function () {
        // Arrange: Input with name at exactly 255 characters
        $input = [
            'name' => str_repeat('a', 255),
            'email' => 'maxname@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: User created successfully
        expect($user->exists)->toBeTrue()
            ->and(strlen($user->name))->toBe(255);
    });

    test('accepts valid email at max length', function () {
        // Arrange: Input with email at exactly 254 characters (actual valid max)
        $longEmail = str_repeat('a', 245) . '@test.com'; // 254 chars total
        $input = [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create user
        $user = $this->action->create($input);
        
        // Assert: User created successfully
        expect($user->exists)->toBeTrue()
            ->and(strlen($user->email))->toBe(254);
    });

    test('creates multiple users with different emails', function () {
        // Arrange: Multiple valid inputs
        $user1Input = [
            'name' => 'User One',
            'email' => 'user1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        $user2Input = [
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        
        // Act: Create both users
        $user1 = $this->action->create($user1Input);
        $user2 = $this->action->create($user2Input);
        
        // Assert: Both users created successfully
        expect($user1->exists)->toBeTrue()
            ->and($user2->exists)->toBeTrue()
            ->and($user1->id)->not->toBe($user2->id);
    });
});
