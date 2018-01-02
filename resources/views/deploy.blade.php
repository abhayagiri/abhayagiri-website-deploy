@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <dl class="row">
                <dt class="col-sm-3">Revision:</dt>
                <dd>{{ $deploy->revision }}</dd>
                <dt class="col-sm-3">Site:</dt>
                <dd>{{ $deploy->site->name }}</dd>
                <dt class="col-sm-3">Started:</dt>
                <dd>{{ $deploy->started_at }}</dd>
                <dt class="col-sm-3">Ended:</dt>
                <dd>{{ $deploy->ended_at ? $deploy->ended_at : 'N/A' }}</dd>
                <dt class="col-sm-3">Success:</dt>
                <dd>@include('success')</dd>
            </dl>
            <pre>{{ $deploy->log }}</pre>
        </div>
    </div>
</div>
@endsection
