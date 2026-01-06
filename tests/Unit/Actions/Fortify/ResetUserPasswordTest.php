<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->action = new ResetUserPassword();
    $this->user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);
});

describe('password reset', function () {
    test('resets user password successfully', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input);
        
        // Assert
        $this->user->refresh();
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('hashes new password before storing', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input);
        
        // Assert
        $this->user->refresh();
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
        $this->action->reset($this->user, $input);
        
        // Assert: Password is updated even though it's not mass assignable
        $this->user->refresh();
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('saves user after password update', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act
        $this->action->reset($this->user, $input);
        
        // Assert: Password is persisted in database
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);
        
        $freshUser = User::find($this->user->id);
        expect(Hash::check('NewPassword123!', $freshUser->password))->toBeTrue();
    });
});

describe('password validation', function () {
    test('validates password is required', function () {
        // Arrange
        $input = [
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input))
            ->toThrow(ValidationException::class);
    });

    test('validates password meets minimum requirements', function () {
        // Arrange: Password too short
        $input = [
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input))
            ->toThrow(ValidationException::class);
    });

    test('validates password confirmation matches', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];
        
        // Act & Assert: Should not throw
        $this->action->reset($this->user, $input);
        
        $this->user->refresh();
        expect(Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
    });

    test('rejects password confirmation mismatch', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input))
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
        $this->action->reset($this->user, $input);
        
        // Assert: Password is still valid (hashed again)
        $this->user->refresh();
        expect(Hash::check('OldPassword123!', $this->user->password))->toBeTrue();
    });

    test('password validation uses password rules trait', function () {
        // Arrange: Test that PasswordValidationRules trait is applied
        $input = [
            'password' => 'weakpw', // Too short, will fail minimum length
            'password_confirmation' => 'weakpw',
        ];
        
        // Act & Assert: Should validate using trait rules
        expect(fn() => $this->action->reset($this->user, $input))
            ->toThrow(ValidationException::class);
    });

    test('rejects empty password', function () {
        // Arrange
        $input = [
            'password' => '',
            'password_confirmation' => '',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input))
            ->toThrow(ValidationException::class);
    });

    test('rejects missing password confirmation', function () {
        // Arrange
        $input = [
            'password' => 'NewPassword123!',
        ];
        
        // Act & Assert
        expect(fn() => $this->action->reset($this->user, $input))
            ->toThrow(ValidationException::class);
    });
});
