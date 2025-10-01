<x-mail::message>
# Halo {{ $transaction->email }}

Terima kasih telah membeli merchandise kami 🎉  

Berikut adalah detail pembelian Anda:

- **Kode Unik:** {{ $transaction->kode_unik }}
- **Total:** Rp {{ number_format($transaction->amount, 0, ',', '.') }}
- **Status:** {{ $transaction->status }}

Invoice + QR Code sudah kami lampirkan dalam format PDF.  
Silakan simpan sebagai bukti pembayaran dan gunakan QR saat pengambilan merch.

Terima kasih,  
**RSCTix Merch**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
