<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'honeypot' => ['nullable', 'max:0'],
            'cf-turnstile-response' => ['required', 'string'],
        ]);

        if (! empty($validated['honeypot'])) {
            return back()->with('success', 'Message sent!');
        }

        // Verify Cloudflare Turnstile
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret'),
            'response' => $validated['cf-turnstile-response'],
            'remoteip' => $request->ip(),
        ]);

        if (! $response->json('success')) {
            return back()->withErrors(['captcha' => 'Captcha verification failed. Please try again.']);
        }

        Mail::to('mvarre@kishmish.com')
            ->send(new ContactFormMail(
                senderName: $validated['name'],
                senderEmail: $validated['email'],
                body: $validated['message'],
            ));

        return back()->with('success', 'Message sent! We\'ll get back to you soon.');
    }
}
