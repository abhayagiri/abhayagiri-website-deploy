<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

use Illuminate\Support\Facades\Hash;
use App\User;

Artisan::command('app:add-user {email} {name?}', function () {
    $email = $this->argument('email');
    $name = $this->argument('name');
    $name = $name ? $name : '';
    $password = substr(md5(openssl_random_pseudo_bytes(100)), 0, 8);
    User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);
    $this->comment('Created user:');
    $this->comment('  name: ' . $name);
    $this->comment('  email: ' . $email);
    $this->comment('  password: ' . $password);
})->describe('Add a user');
