@extends('layouts.app')

@section('title', __('Recipient Lists'))

@section('content')
<div class="container">
    <div class="flexy-container">
        <div class="flexy-item">
            <span class="heading">{{ __('Recipient Lists') }}@if (count($lists)) <small>({{ count($lists) }})</small>@endif</span>
        </div>
        @if (Auth::user()->isAdmin())
            <div class="flexy-item margin-left">
                <a href="{{ route('broadcast.lists.create') }}" class="btn btn-bordered">{{ __('New List') }}</a>
            </div>
        @endif
        <div class="flexy-block"></div>
    </div>

    @include('partials/flash_messages')

    @if (!count($lists))
        @if (Auth::user()->isAdmin())
            <p class="text-help margin-top">{{ __('No recipient lists yet. Create one to be able to send a broadcast email to a group of recipients without exposing their addresses to each other.') }}</p>
        @else
            <p class="text-help margin-top">{{ __('No recipient lists yet. Ask an administrator to grant you access to one.') }}</p>
        @endif
    @else
        <div class="card-list margin-top">
            @foreach ($lists as $list)
                <a href="{{ route('broadcast.lists.edit', ['id' => $list->id]) }}" class="card hover-shade">
                    <h4>{{ $list->name }}</h4>
                    <p class="text-truncate">{{ trans_choice(':count recipient|:count recipients', $list->members_count, ['count' => $list->members_count]) }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
