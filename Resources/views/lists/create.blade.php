@extends('layouts.app')

@section('title', __('New Recipient List'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <div class="panel panel-default panel-wizard">
                <div class="panel-body">
                    <div class="wizard-header">
                        <h1>{{ __('New Recipient List') }}</h1>
                    </div>
                    <div class="wizard-body">
                        @include('partials/flash_messages')

                        <form class="form-horizontal margin-top" method="POST" action="{{ route('broadcast.lists.store') }}">
                            @include('broadcast::lists.form', ['list' => new \Modules\Broadcast\Entities\BroadcastList()])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
