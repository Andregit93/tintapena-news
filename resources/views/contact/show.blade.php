@extends('layouts.public')

@php
    $whatsappClean = null;
    if (filled($whatsapp)) {
        $whatsappRaw = trim((string) $whatsapp);
        $isPhoneLike = $whatsappRaw !== '' && preg_match('/^\+?[0-9\s().-]+$/', $whatsappRaw);

        if ($isPhoneLike) {
            $whatsappClean = preg_replace('/[^0-9]/', '', $whatsappRaw);
            if (str_starts_with($whatsappClean, '0')) {
                $whatsappClean = '62' . substr($whatsappClean, 1);
            }
            if (strlen($whatsappClean) < 8 || strlen($whatsappClean) > 15) {
                $whatsappClean = null;
            }
        }
    }

    $isValidEmail = filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
@endphp

@section('title', 'Kontak - TINTAPENA')
@section('meta_description', 'Hubungi redaksi TINTAPENA untuk memberikan saran, kritik, atau informasi kerja sama.')
@section('canonical', route('contact.show'))

@section('content')
<div class="bg-white md:bg-transparent">
    <div class="max-w-[800px] mx-auto pt-4 md:pt-0 px-4 md:px-0 mb-12">
        <h1 class="text-2xl md:text-[40px] leading-tight md:leading-[1.2] font-bold text-[#17191D] mb-8 border-b border-[#E1E4E8] pb-4">
            Kontak
        </h1>

        <div class="prose prose-lg max-w-none text-[#17191D] mb-8">
            <p>
                Kami menyambut baik setiap pertanyaan, saran, kritik, maupun informasi dari pembaca setia TINTAPENA.
                Silakan hubungi kami melalui kontak di bawah ini.
            </p>
        </div>

        @if(blank($email) && blank($whatsapp) && blank($address) && blank($hours))
            <div class="bg-[#F6F7F9] p-8 text-center rounded-lg border border-[#E1E4E8]">
                <p class="text-[#5D6470] font-medium">Informasi kontak belum tersedia saat ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                @if(filled($address))
                <div class="bg-white p-6 rounded-lg border border-[#E1E4E8] shadow-sm">
                    <h3 class="text-lg font-bold text-[#17191D] mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#1A2BC4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Alamat Redaksi
                    </h3>
                    <p class="text-[#5D6470] whitespace-pre-line">{{ $address }}</p>
                </div>
                @endif

                <div class="flex flex-col gap-6">
                    @if(filled($email))
                    <div class="bg-white p-6 rounded-lg border border-[#E1E4E8] shadow-sm">
                        <h3 class="text-lg font-bold text-[#17191D] mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#1A2BC4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Email
                        </h3>
                        @if($isValidEmail)
                            <a href="mailto:{{ $email }}" class="text-[#1A2BC4] hover:underline font-medium break-all block min-h-11 items-center md:min-h-0 md:inline-flex pt-1 md:pt-0">{{ $email }}</a>
                        @else
                            <p class="text-[#5D6470] break-all">{{ $email }}</p>
                        @endif
                    </div>
                    @endif

                    @if(filled($whatsapp))
                    <div class="bg-white p-6 rounded-lg border border-[#E1E4E8] shadow-sm">
                        <h3 class="text-lg font-bold text-[#17191D] mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#25D366]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            WhatsApp
                        </h3>
                        @if($whatsappClean)
                            <a href="https://wa.me/{{ $whatsappClean }}" target="_blank" rel="noopener noreferrer" class="text-[#1A2BC4] hover:underline font-medium block min-h-11 items-center md:min-h-0 md:inline-flex pt-1 md:pt-0">{{ $whatsapp }}</a>
                        @else
                            <p class="text-[#5D6470]">{{ $whatsapp }}</p>
                        @endif
                    </div>
                    @endif

                    @if(filled($hours))
                    <div class="bg-white p-6 rounded-lg border border-[#E1E4E8] shadow-sm">
                        <h3 class="text-lg font-bold text-[#17191D] mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#1A2BC4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Jam Operasional
                        </h3>
                        <p class="text-[#5D6470] whitespace-pre-line">{{ $hours }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
