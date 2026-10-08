<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\NotDisposableEmail;
use App\Rules\SafeEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                new SafeEmail,
                new NotDisposableEmail,
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // Те же данные, что даёт вход через Google (имя и почта); телефон продавец укажет в профиле
        // или при создании магазина.
        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
        $user->password_set_at = now();
        $user->save();

        return $user;
    }
}
