<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Setting;

class ContactController extends Controller
{
    public function show()
    {
        $settings = Setting::whereIn('setting_key', [
            'contact.email',
            'contact.whatsapp',
            'contact.address',
            'contact.hours',
        ])->get()->keyBy('setting_key');

        return view('contact.show', [
            'email' => $settings->get('contact.email')?->value,
            'whatsapp' => $settings->get('contact.whatsapp')?->value,
            'address' => $settings->get('contact.address')?->value,
            'hours' => $settings->get('contact.hours')?->value,
        ]);
    }

    public function store(StoreContactMessageRequest $request)
    {
        $validated = $request->validated();

        $message = new ContactMessage;
        $message->name = $validated['name'];
        $message->email = $validated['email'];
        $message->subject = $validated['subject'];
        $message->message = $validated['message'];
        $message->status = ContactStatus::Unread;
        $message->save();

        return redirect()->route('contact.show')->with('success', 'Pesan Anda telah berhasil dikirim. Terima kasih telah menghubungi TINTAPENA.');
    }
}
