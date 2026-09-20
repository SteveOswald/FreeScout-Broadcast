{{-- Visibility (admin, or a non-admin with edit rights on at least one list) is already decided by the menu.manage.append hook before this view is rendered. --}}
<li class="{{ Route::is('broadcast.*') ? 'active' : '' }}"><a href="{{ route('broadcast.lists') }}">{{ __('Recipient Lists') }}</a></li>
