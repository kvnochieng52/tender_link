<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        // Optionally mail the contact message to the admin
        // Mail::to(config('mail.from.address'))->send(new \App\Mail\ContactMessage($validated));

        return back()->with('success', 'Message sent successfully.');
    }
}
