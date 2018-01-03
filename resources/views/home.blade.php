@extends('layouts.app')

@section('content')
<div class="container">

    <table class="table">
        <thead>
            <tr>
                <th scope="col">Revision</th>
                <th scope="col">Site</th>
                <th scope="col">Started</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deploys as $deploy)
                <tr>
                    <td scope="row">
                        <a href="{{ route('deploy.show', $deploy->id) }}">
                            {{ $deploy->revision }}
                        </a>
                    </td>
                    <td><a href="{{ $deploy->site->url }}">
                        {{ $deploy->site->name }}</a></td>
                    <td>{{ $deploy->started_at }}</td>
                    <td>@include('status')</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach ($sites as $site)
        <div style="display: inline-block">
            <form action="{{ route('deploy.start', $site->id) }}" method="POST">
                {{ csrf_field() }}
                <input type="submit" class="btn btn-primary" value="Deploy {{ $site->name }}" {{ $site->locked_at === null ? '' : 'disabled="disabled"' }}>
            </form>
        </div>
    @endforeach

</div>
@endsection
