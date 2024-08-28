<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageEvent;
use App\Events\SendMesageEvent;
use App\Models\Message;
use App\Models\MessageGroup;
use App\Models\User;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function conversation($id)
    {
        $users = User::where('id', '!=', auth()->id())->get();
        $friendInfo = User::findOrFail($id);
        $myInfo = auth()->user();

        $groups = MessageGroup::all();
        return view('messages.conversation', compact('id', 'users', 'friendInfo', 'myInfo', 'groups'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'receiver_id' => 'required'
        ]);

        $sender_id = auth()->id();
        $receiver_id = $request->receiver_id;

        $message = new Message();
        $message->message = $request->message;

        if ($message->save()) {
            try {
                $message->users()->attach($sender_id, ['receiver_id' => $receiver_id]);
                $sender = User::where('id', '=', $sender_id)->first();

                $data = [
                    'sender_id' => $sender_id,
                    'sender_name' => $sender->name,
                    'receiver_id' => $receiver_id,
                    'content' => $message->message,
                    'created_at' => $message->created_at,
                    'message_id' => $message->id
                ];

                event(new SendMesageEvent($data));

                return response()->json([
                    'data' => $data,
                    'success' => true,
                    'message' => 'Message sent successfully'
                ]);
            } catch (\Exception $e) {
                $message->delete();
            }
        }
    }

    public function sendGroupMessage(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'message_group_id' => 'required'
        ]);

        $sender_id = auth()->id();
        $messageGroupId = $request->message_group_id;

        $message = new Message();
        $message->message = $request->message;

        if ($message->save()) {
            try {
                $message->users()->attach($sender_id, ['message_group_id' => $messageGroupId]);
                $sender = User::where('id', '=', $sender_id)->first();

                $data = [];
                $data['sender_id'] = $sender_id;
                $data['sender_name'] = $sender->name;
                $data['content'] = $message->message;
                $data['created_at'] = $message->created_at;
                $data['message_id'] = $message->id;
                $data['group_id'] = (int)$messageGroupId;
                $data['type'] = 2;

                event(new GroupMessageEvent($data));


                return response()->json([
                    'data' => $data,
                    'success' => true,
                    'message' => 'Message sent successfully'
                ]);
            } catch (\Exception $e) {
                $message->delete();
            }
        }
    }
}
