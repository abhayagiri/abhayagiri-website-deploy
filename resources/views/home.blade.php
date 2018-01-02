@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Revision</th>
                        <th scope="col">Site</th>
                        <th scope="col">Started</th>
                        <th scope="col">Success</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($deploys as $deploy)
                        <tr>
                            <td scope="row">
                                <a href="{{ route('deploy', $deploy->id) }}">
                                    {{ $deploy->revision }}
                                </a>
                            </td>
                            <td>{{ $deploy->site->name }}</td>
                            <td>{{ $deploy->started_at }}</td>
                            <td>@include('success')</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
