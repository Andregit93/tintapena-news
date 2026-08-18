<?php

namespace App\Http\Controllers;

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
}
