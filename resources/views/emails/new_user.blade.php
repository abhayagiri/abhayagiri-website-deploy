@component('mail::message')

<p>Hi {{ $name }},</p>

<p>This is information for accessing the {{ config('app.name') }} at:</p>

<p><a href="{{ $homeUrl }}">{{ $homeUrl }}</a></p>

<p>You have been assigned the following credentials:</p>

<dl>
    <dt>Email:</dt><dd>{{ $email }}</dd>
    <dt>Password:</dt><dd>{{ $password }}</dd>
</dl>

<p>If you would like to change your password, you can reset it by using this form:</p>

<p><a href="{{ $changePasswordUrl }}">{{ $changePasswordUrl }}</a></p>

<p>Thanks,<br>
Abhayagiri Website Team</p>

@endcomponent
