<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/Contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $to = config('mail.from.address');
        $body = "Von: {$data['name']} <{$data['email']}>\n\n{$data['message']}";

        Mail::raw($body, function ($mail) use ($to, $data) {
            $mail->to($to)
                ->subject('Kontaktformular: '.$data['name'])
                ->replyTo($data['email'], $data['name']);
        });

        return back();
    }
}
