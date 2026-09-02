<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $user = $request->user();

        $conversation = $user->conversations()->findOrFail($conversationId);

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'body'    => $request->input('body'),
        ]);

        $conversation->touch();

        $formattedMessage = [
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'authorId'        => (string) $message->user_id,
            'text'            => $message->body,
            'createdAt'       => $message->created_at->timestamp * 1000,
        ];

        broadcast(new MessageSent($formattedMessage, $conversationId))->toOthers();

        return response()->json([
            'status'  => 'success',
            'message' => $formattedMessage,
        ]);
    }
}
