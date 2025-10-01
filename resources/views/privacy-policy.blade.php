@extends('layouts.app')

@section('title', 'Kebijakan Privasi - Rupa Suara Cahaya')

@section('content')
<div class="container mx-auto px-6 lg:px-16 xl:px-24 2xl:px-32 py-10">
    <div class="bg-white shadow-lg rounded-xl p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Kebijakan Privasi</h1>

        <p class="mb-4">
            Kebijakan Privasi ini menjelaskan bagaimana <strong>Rupa Suara Cahaya</strong> (di bawah <strong>CV ULFA MERDEKA</strong>) 
            mengumpulkan, menggunakan, menyimpan, melindungi, dan membagikan informasi pribadi pengguna saat mengakses 
            dan menggunakan layanan website ticketing kami (<strong>RSCTix</strong>).
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">1. Informasi yang Kami Kumpulkan</h2>
        <ul class="list-disc list-inside mb-4">
            <li>Data pribadi: nama, alamat email, nomor telepon, dan alamat penagihan.</li>
            <li>Data pembayaran: metode pembayaran, bukti transaksi (diproses melalui mitra pembayaran seperti Xendit).</li>
            <li>Data teknis: alamat IP, jenis perangkat, browser, dan aktivitas penggunaan situs.</li>
            <li>Data tambahan: preferensi acara, interaksi dengan website, dan data lain yang Anda berikan secara sukarela.</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-3">2. Penggunaan Data</h2>
        <p class="mb-4">Data yang kami kumpulkan digunakan untuk:</p>
        <ul class="list-disc list-inside mb-4">
            <li>Memproses pemesanan tiket dan pembayaran.</li>
            <li>Mengirimkan konfirmasi tiket dan pembaruan terkait acara.</li>
            <li>Memberikan dukungan pelanggan dan menanggapi pertanyaan Anda.</li>
            <li>Meningkatkan keamanan dan kualitas layanan kami.</li>
            <li>Memberikan informasi promosi terkait acara (hanya jika Anda setuju).</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-3">3. Perlindungan Data</h2>
        <p class="mb-4">
            Kami menerapkan langkah-langkah keamanan yang sesuai, termasuk enkripsi dan pembatasan akses data, 
            untuk melindungi informasi pribadi Anda dari akses tidak sah, penyalahgunaan, atau kehilangan.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">4. Berbagi Informasi dengan Pihak Ketiga</h2>
        <p class="mb-4">
            Kami dapat membagikan data Anda kepada:
        </p>
        <ul class="list-disc list-inside mb-4">
            <li>Penyelenggara acara, jika diperlukan untuk validasi tiket.</li>
            <li>Penyedia layanan pembayaran (contoh: Xendit) untuk memproses transaksi.</li>
            <li>Pihak berwenang, jika diwajibkan oleh hukum atau peraturan yang berlaku.</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-3">5. Hak Pengguna</h2>
        <p class="mb-4">Anda memiliki hak untuk:</p>
        <ul class="list-disc list-inside mb-4">
            <li>Mengakses dan memperbarui data pribadi Anda.</li>
            <li>Meminta penghapusan data sesuai peraturan yang berlaku.</li>
            <li>Menolak menerima email promosi kapan saja melalui tautan berhenti berlangganan.</li>
        </ul>

        <h2 class="text-xl font-semibold mt-6 mb-3">6. Penyimpanan Data</h2>
        <p class="mb-4">
            Data pengguna disimpan selama diperlukan untuk keperluan transaksi, layanan pelanggan, 
            dan pemenuhan kewajiban hukum sesuai kebijakan kami.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">7. Cookie dan Teknologi Pelacakan</h2>
        <p class="mb-4">
            Kami menggunakan cookie untuk meningkatkan pengalaman pengguna, menganalisis lalu lintas situs, 
            dan menyesuaikan konten sesuai preferensi Anda. Anda dapat menonaktifkan cookie melalui pengaturan browser.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">8. Tautan ke Situs Pihak Ketiga</h2>
        <p class="mb-4">
            Website kami dapat berisi tautan ke situs pihak ketiga. Kami tidak bertanggung jawab atas kebijakan privasi 
            atau konten dari situs tersebut.
        </p>

        <h2 class="text-xl font-semibold mt-6 mb-3">9. Perubahan Kebijakan</h2>
        <p class="mb-4">
            Kami berhak memperbarui Kebijakan Privasi ini kapan saja. Versi terbaru akan dipublikasikan di halaman ini 
            dengan tanggal pembaruan terbaru di bawah ini.
        </p>

        <div class="mt-6 p-4 bg-gray-100 rounded">
            <strong>Tanggal Pembaruan Terakhir:</strong> {{ date('d F Y') }}
        </div>

        <h2 class="text-xl font-semibold mt-6 mb-3">10. Kontak Kami</h2>
        <p>
            Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, hubungi kami melalui:<br>
            📧 Email: <a href="mailto:rscsosmed@gmail.com" class="text-blue-600 hover:underline">rscsosmed@gmail.com</a><br>
            📞 WhatsApp: +6285230088828
        </p>
    </div>
</div>
@endsection
