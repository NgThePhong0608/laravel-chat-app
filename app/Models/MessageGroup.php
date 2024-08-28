<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public function message_group_member()
    {
        return $this->hasMany(MessageGroupMember::class);
    }

    public function user_messages()
    {
        return $this->hasMany(UserMessage::class);
    }
}
