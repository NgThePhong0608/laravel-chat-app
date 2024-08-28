<?php

namespace App\Http\Controllers;

use App\Models\MessageGroup;
use App\Models\MessageGroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data['name'] = $request->name;
        $data['user_id'] = Auth::id();

        $messageGroup = MessageGroup::create($data);

        if ($messageGroup) {
            if (isset($request->user_id) && !empty($request->user_id)) {
                foreach ($request->user_id as $userId) {
                    $memberData['user_id'] = $userId;
                    $memberData['message_group_id'] = $messageGroup->id;
                    $memberData['status'] = 0;

                    MessageGroupMember::create($memberData);
                }
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $users = User::where('id', '!=', auth()->id())->get();
        $myInfo = User::findOrFail(auth()->id());
        $groups = MessageGroup::all();
        $currentGroup = MessageGroup::where('id', $id)->with('message_group_member.user')->first();
        return view('message_groups.conversation', compact('users', 'myInfo', 'groups', 'currentGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
