<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

/**
 * @group actions
 * @group fortify
 * @group password-reset
 * @group unit
 */

beforeEach(function () {
    $this->action = new ResetUserPassword();
    $this->user = new User();
    $this->user->password = Hash::make('OldPassword123!');
    $this->persister = fn($user) => null; // No-op persister for testing
});

describe('password reset', function () {
    test('resets user password successfully', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input, $this->persister);
        
        // Assert
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('hashes new password before storing', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input, $this->persister);
        
        // Assert
        expect($this->user->password)
            ->not->toBe('NewPassword123!')
            ->and(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('uses force fill to bypass mass assignment', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input, $this->persister);
        
        // Assert: Password is updated even though it's not mass assignable
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('calls persister callback when provided', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        $persisted = false;
        $persister = function($user) use (&$persisted) {
            $persisted = true;
        };
        
        // Act
        $this->action->reset($this->user, $input, $persister);
        
        // Assert: Persister was called
        expect($persisted)->toBeTrue();
    });
});

describe('password validation', function () {
    test('validates password is required', function () {
        // Arrange
        $input = [
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });

    test('validates password meets minimum requirements', function () {
        // Arrange: Password too short
        $input = [
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });

    test('validates password confirmation matches', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act & Assert: Should not throw
        $this->action->reset($this->user, $input, $this->persister);
        
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('rejects password confirmation mismatch', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });
});

describe('edge cases', function () {
    test('handles same password as current', function () {
        // Arrange: Use same password as existing
        $input = [
            'password' => 'OldPassword123!',
            'password_confirmation' => 'OldPassword123!',
        ];
        
        // Act: Should allow resetting to same password
        $this->action->reset($this->user, $input, $this->persister);
        
        // Assert: Password is still valid (hashed again)
        expect(Hash::check('OldPassword123!', $this->user->password))->toBeTrue();
    });

    test('password validation uses password rules trait', function () {
        // Arrange: Test that PasswordValidationRules trait is applied
        $input = [
            'password' => 'weakpw', // Too short, will fail minimum length
            'password_confirmation' => 'weakpw',
        ];
        
        // Act & Assert: Should validate using trait rules
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });

    test('rejects empty password', function () {
        // Arrange
        $input = [
            'password' => '',
            'password_confirmation' => '',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });

    test('handles user with no existing password', function () {
        // Arrange: User with null password (edge case)
        $newUser = new User();
        $newUser->password = null;
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act: Reset password
        $this->action->reset($newUser, $input, $this->persister);
        
        // Assert: Password is set correctly
        expect(Hash::check('NewPassword123!', $newUser->password))->toBeTrue();
    });

    test('rejects missing password confirmation', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input, $this->persister))
            ->toThrow(ValidationException::class);
    });
    
    test('accepts password with various special characters', function () {
        // Arrange: Password with multiple special characters
        $input = [
            'password' => 'C0mpl3x!P@ssw#rd$2026',
            'password_confirmation' => 'C0mpl3x!P@ssw#rd$2026',
        ];
        
        // Act: Reset with complex password
        $this->action->reset($this->user, $input, $this->persister);
        
        // Assert: Complex password is accepted and hashed
        expect(Hash::check('C0mpl3x!P@ssw#rd$2026', $this->user->password))->toBeTrue()
            ->and($this->user->password)->not->toBe('C0mpl3x!P@ssw#rd$2026');
    });
});
