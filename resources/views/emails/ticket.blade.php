{{-- resources/views/emails/ticket.blade.php --}}
<x-mail::message>

# Halo {{ $buyerName ?? $transaction->email }}

Terima kasih telah melakukan pembayaran! 🎉  
Berikut adalah detail tiket Anda:

<x-mail::panel>
**Event:** {{ $event->title }}  
**Tanggal:** {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y H:i') }}  
**Lokasi:** {{ $event->location }}
</x-mail::panel>

📎 QR Code tiket Anda ada pada lampiran email ini (format **PDF**).  
Silakan tunjukkan QR Code tersebut saat memasuki venue.

Salam hangat,  
**RSCTix**

<x-mail::subcopy>
Jika Anda memiliki pertanyaan, hubungi kami di {{ config('mail.from.address') }}.
</x-mail::subcopy>

</x-mail::message>
