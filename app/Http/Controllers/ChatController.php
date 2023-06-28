<?php

namespace App\Http\Controllers;

use App\Events\ChatEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use OpenAI;

class ChatController extends Controller
{
    public function index()
    {

        Session::forget('chatMessages');

        return Inertia::render('Chat');

    }

    public function chat(Request $request)
    {
        $yourApiKey = getenv('OPENAI_API_KEY');
        $client = OpenAI::client($yourApiKey);

        $message = $request->input('userMessage');
        $uuid = $request->input('uuid');

        $chatMessages = Session::get('chatMessages');

        $chatMessages[] = [
            'role' => 'user',
            'content' => $message,
        ];


        Session::put('chatMessages', $chatMessages);

        $stream = $client->chat()->createStreamed([
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => $chatMessages,
        ]);


        foreach ($stream as $response) {
            event(new ChatEvent($response->choices[0]->toArray(), $uuid));
        }
    }
}
