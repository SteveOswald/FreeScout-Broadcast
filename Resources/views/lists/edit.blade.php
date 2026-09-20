@extends('layouts.app')

@section('title', $list->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <div class="panel panel-default panel-wizard">
                <div class="panel-body">
                    <div class="wizard-header">
                        <h1>{{ $list->name }}</h1>
                    </div>
                    <div class="wizard-body">
                        @include('partials/flash_messages')

                        <form class="form-horizontal margin-top" method="POST" action="{{ route('broadcast.lists.update', ['id' => $list->id]) }}">
                            @include('broadcast::lists.form', ['list' => $list, 'members' => $members])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="broadcast-list-delete-form" method="POST" action="" style="display:none">
    {{ csrf_field() }}
    {{ method_field('DELETE') }}
</form>
@endsection

@section('javascript')
    @parent
    $(function() {
        $('#broadcast-list-delete').on('click', function() {
            if (!confirm({{ json_encode(__('Delete this recipient list? This cannot be undone.')) }})) {
                return;
            }
            $('#broadcast-list-delete-form').attr('action', $(this).data('url')).trigger('submit');
        });
    });
@endsection
