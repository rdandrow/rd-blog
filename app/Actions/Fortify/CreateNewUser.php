<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * A callable responsible for validating the input array.
     * Signature: function(array $input): void
     */
    protected $validateInput;

    /**
     * A callable responsible for persisting a User from provided attributes.
     * Signature: function(array $attributes): User
     */
    protected $persistUser;

    /**
     * Create a new CreateNewUser action instance.
     *
     * @param  callable(array): void|null  $validateInput  Optional validation strategy (receives input, throws ValidationException on failure)
     * @param  callable(array): User|null  $persistUser    Optional persistence strategy (receives attributes, returns User instance)
     */
    public function __construct(?callable $validateInput = null, ?callable $persistUser = null)
    {
        $this->validateInput = $validateInput ?: function (array $input): void {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                ],
                'password' => $this->passwordRules(),
            ])->validate();
        };

        $this->persistUser = $persistUser ?: function (array $attributes): User {
            return User::create($attributes);
        };
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validate = $this->validateInput;
        $validate($input);

        $attributes = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ];

        $persist = $this->persistUser;
        return $persist($attributes);
    }
}
