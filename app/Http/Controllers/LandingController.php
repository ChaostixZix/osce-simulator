<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Landing');
    }

    public function privacyPolicy(): Response
    {
        return Inertia::render('Landing/PrivacyPolicy');
    }

    public function contact(): Response
    {
        return Inertia::render('Landing/Contact');
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return Redirect::back()->with('success', 'Thank you for your message! We\'ll get back to you soon.');
    }

    public function madeBy(): Response
    {
        return Inertia::render('Landing/MadeBy');
    }
}
