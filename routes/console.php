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
use Illuminate\Support\Facades\Mail;
use App\Mail\NewUser;
use App\User;

Artisan::command('app:add-user {email} {name?}', function () {

    $email = $this->argument('email');
    $name = $this->argument('name');
    $name = $name ? $name : $email;
    $password = substr(md5(openssl_random_pseudo_bytes(100)), 0, 8);

    $user = User::updateOrCreate([ 'email' => $email ],
    [
        'name' => $name,
        'password' => Hash::make($password),
    ]);

    $this->comment('Created user:');
    $this->comment('  email: ' . $email);
    $this->comment('  name: ' . $name);
    $this->comment('  password: ' . $password);

    Mail::to($user)->send(new NewUser($email, $name, $password));

})->describe('Add a user');
