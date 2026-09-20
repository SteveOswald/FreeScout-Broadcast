@if (Auth::user() && Auth::user()->isAdmin())
    <li class="{{ Route::is('broadcast.*') ? 'active' : '' }}"><a href="{{ route('broadcast.lists') }}">{{ __('Recipient Lists') }}</a></li>
@endif
