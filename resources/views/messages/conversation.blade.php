@extends('layouts.app')

<style>
    .select2-container {
        width: 100% !important;
    }
</style>
@section('content')
    <div class="row chat-row">
        <div class="col-md-3">
            <div class="users">
                <h5>Users</h5>
                <ul class="list-group list-chat-item">
                    @if ($users->count())
                        @foreach ($users as $user)
                            <li class="chat-user-list @if ($user->id == $friendInfo->id) active @endif">
                                <a href="{{ route('messages.conversation', $user->id) }}">
                                    <div class="chat-image">
                                        {!! makeImageFromName($user->name) !!}
                                        <i class="fa fa-circle user-status-icon user-icon-{{ $user->id }}"
                                            title="away"></i>
                                    </div>
                                    <div class="chat-name font-weight-bold">
                                        {{ $user->name }}
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>

            <div class="groups">
                <h5>Groups <i class="fa fa-plus btn-add-group ml-3"></i></h5>
                <ul class="list-group list-chat-item">
                    @if ($groups->count())
                        @foreach ($groups as $group)
                            <li class="chat-user-list">
                                <a href="{{ route('message-groups.show', $group->id) }}">
                                    <div class="chat-image">
                                        {!! makeImageFromName($group->name) !!}
                                        <i class="fa fa-circle user-status-icon user-icon-{{ $group->id }}"
                                            title="away"></i>
                                    </div>
                                    <div class="chat-name font-weight-bold">
                                        {{ $group->name }}
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-md-9 chat-section">
            <div class="chat-header">
                <div class="chat-image">
                    {!! makeImageFromName($friendInfo->name) !!}
                    <i class="fa fa-circle user-status-icon user-icon-{{ $friendInfo->id }}"
                        id="userStatusHead{{ $friendInfo->id }}" title="away"></i>
                </div>
                <div class="chat-name font-weight-bold">
                    {{ $friendInfo->name }}
                </div>
            </div>
            <div class="chat-body" id="chatBody">
                <div class="message-listing" id="messageWrapper">

                </div>
            </div>

            <div class="chat-box">
                <div class="chat-input bg-white" id="chatInput" contenteditable="true">

                </div>
            </div>

            <div class="chat-input-toolbar">
                <button title="Add File" class="btn btn-light btn-sm btn-file-upload">
                    <i class="fa fa-paperclip"></i>
                </button>
                <button title="Bold" class="btn btn-light btn-sm tool-items"
                    onclick="document.execCommand('bold', false, '')">
                    <i class="fa fa-bold tool-icon"></i>
                </button>
                <button title="Italic" class="btn btn-light btn-sm btn-link-share"
                    onclick="document.execCommand('italic', false, '')">
                    <i class="fa fa-italic tool-icon"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="addGroupModal">
        <div class="modal-dialog modal-lg modal-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('message-groups.store') }}" method="post">
                    {{ csrf_field() }}
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">Group Name</label>
                            <input type="text" class="form-control" name="name">
                        </div>

                        <div class="form-group">
                            <label for="">Select Member</label>
                            <select id="selectMember" class="form-control" name="user_id[]" multiple="multiple">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {

            let $chatInput = $('#chatInput');
            let $chatInputToolbar = $('.chat-input-toolbar');
            let $chatBody = $('.chat-body');
            let $messageWrapper = $("#messageWrapper");


            let user_id = '{{ Auth::user()->id }}';
            let friendId = '{{ $friendInfo->id }}';
            let ip = '127.0.0.1';
            let socketPort = '3000';
            let socket = io(ip + ':' + socketPort);


            socket.on('connect', function() {
                socket.emit('user_connected', user_id);
            });

            socket.on('updateUserStatus', function(data) {
                let $userStatusIcon = $('.user-status-icon');
                $userStatusIcon.removeClass('text-success');
                $userStatusIcon.attr('title', 'Away');
                $.each(data, function(key, val) {
                    if (val !== null && val !== 0) {
                        let $userIcon = $('.user-icon-' + key);
                        $userIcon.addClass('text-success');
                        $userIcon.attr('title', 'Online');
                    } else {
                        let $userIcon = $('.user-icon-' + key);
                        $userIcon.addClass('text-danger');
                        $userIcon.attr('title', 'Offline');
                    }
                });
            });

            $chatInput.keypress(function(e) {
                let message = $(this).html();
                if (e.key === 'Enter' && !e.shiftKey) {
                    $chatInput.html("");
                    sendMessage(message);
                    return false;
                }
            });

            function sendMessage(message) {
                let url = "{{ route('messages.send') }}";
                let form = $(this);
                let formData = new FormData();
                let token = "{{ csrf_token() }}";

                formData.append('message', message);
                formData.append('_token', token);
                formData.append('receiver_id', friendId);

                appendMessageToSender(message);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.success) {
                            console.log(response.data);
                        }
                    }
                });
            }

            function appendMessageToSender(message) {
                let name = '{{ $myInfo->name }}';
                let image = '{!! makeImageFromName($myInfo->name) !!}';

                let userInfo = '<div class="col-md-12 user-info">\n' +
                    '<div class="chat-image">\n' + image +
                    '</div>\n' +
                    '\n' +
                    '<div class="chat-name font-weight-bold">\n' +
                    name +
                    '<span class="small time text-gray-500" title="' + getCurrentDateTime() + '">\n' +
                    getCurrentTime() + '</span>\n' +
                    '</div>\n' +
                    '</div>\n';

                let messageContent = '<div class="col-md-12 message-content">\n' +
                    '                            <div class="message-text">\n' + message +
                    '                            </div>\n' +
                    '                        </div>';


                let newMessage = '<div class="row message align-items-center mb-2">' +
                    userInfo + messageContent +
                    '</div>';

                $messageWrapper.append(newMessage);
            }

            function appendMessageToReceiver(message) {
                let name = '{{ $friendInfo->name }}';
                let image = '{!! makeImageFromName($friendInfo->name) !!}';

                let userInfo = '<div class="col-md-12 user-info">\n' +
                    '<div class="chat-image">\n' + image +
                    '</div>\n' +
                    '\n' +
                    '<div class="chat-name font-weight-bold">\n' +
                    name +
                    '<span class="small time text-gray-500" title="' + dateFormat(message.created_at) + '">\n' +
                    timeFormat(message.created_at) + '</span>\n' +
                    '</div>\n' +
                    '</div>\n';

                let messageContent = '<div class="col-md-12 message-content">\n' +
                    '                            <div class="message-text">\n' + message.content +
                    '                            </div>\n' +
                    '                        </div>';


                let newMessage = '<div class="row message align-items-center mb-2">' +
                    userInfo + messageContent +
                    '</div>';

                $messageWrapper.append(newMessage);
            }

            socket.on("private-channel:App\\Events\\SendMesageEvent", function(message) {
                appendMessageToReceiver(message);
            });

            let $addGroupModal = $("#addGroupModal");

            $('.btn-add-group').click(function() {
                $addGroupModal.modal('show');
            });

            $("#selectMember").select2();
        });
    </script>
@endpush
