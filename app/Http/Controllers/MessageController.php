<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        return Message::with('user')->latest()->limit(50)->get()->reverse();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:500'
        ]);

        $message = Message::create([
            'user_id' => auth()->id() ?? 1,
            'body' => $validated['body']
        ]);

        MessageSent::dispatch($message);

        return $message->load('user');
    }
}
