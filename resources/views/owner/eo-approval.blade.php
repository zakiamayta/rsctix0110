@extends('layouts.owner')

@section('content')
<div>

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Approval EO
            </h1>

            <p class="text-sm text-gray-500">
                Approval Event Organizer
            </p>
        </div>
    </div>



    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left">Nama</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-6 py-3 text-left">Badan Usaha</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y">

                    @foreach($eoList as $eo)

                    <tr class="hover:bg-gray-50 transition">

                        <td class="px-6 py-4 font-semibold">
                            {{ $eo->name }}
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ $eo->email }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $eo->nama_badan_usaha }}
                        </td>

                        <!-- STATUS -->
                        <td class="px-6 py-4">

                            @if($eo->status == 'pending')

                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">
                                    Pending
                                </span>

                            @elseif($eo->status == 'approved')

                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">
                                    Approved
                                </span>

                            @else

                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs">
                                    Rejected
                                </span>

                            @endif

                        </td>

                        <!-- AKSI -->
                        <td class="px-6 py-4 text-center space-x-2">

                            <!-- DETAIL -->
                            <button
                                onclick="openModal({{ $eo->id }})"
                                class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">

                                Detail
                            </button>

                            @if($eo->status == 'pending')

                            <!-- APPROVE -->
                            <form
                                method="POST"
                                action="{{ route('owner.eo.approve', $eo->id) }}"
                                class="inline">

                                @csrf

                                <button
                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">

                                    Approve
                                </button>
                            </form>

                            <!-- REJECT -->
                            <form
                                method="POST"
                                action="{{ route('owner.eo.reject', $eo->id) }}"
                                class="inline">

                                @csrf

                                <button
                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">

                                    Reject
                                </button>
                            </form>

                            @endif

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ========================= -->
<!-- MODAL DETAIL -->
<!-- ========================= -->

@foreach($eoList as $eo)

<div
    id="modal-{{ $eo->id }}"
    class="hidden fixed inset-0 z-50 bg-black/60 overflow-y-auto">

    <div class="min-h-screen flex items-center justify-center p-4">

        <!-- MODAL CONTENT -->
        <div
            onclick="event.stopPropagation()"
            class="bg-white w-full max-w-4xl rounded-2xl shadow-xl relative max-h-[90vh] overflow-y-auto">

            <!-- CLOSE -->
            <button
                onclick="closeModal({{ $eo->id }})"
                class="absolute top-4 right-4 text-gray-500 hover:text-black text-2xl z-10">

                ✕
            </button>

            <div class="p-6">

                <!-- TITLE -->
                <h2 class="text-2xl font-bold mb-6">
                    Detail EO
                </h2>

                <!-- INFO -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                    <p>
                        <b>Nama:</b>
                        {{ $eo->name }}
                    </p>

                    <p>
                        <b>Email:</b>
                        {{ $eo->email }}
                    </p>

                    <p>
                        <b>Badan Usaha:</b>
                        {{ $eo->nama_badan_usaha }}
                    </p>

                    <p>
                        <b>Penanggung Jawab:</b>
                        {{ $eo->penanggung_jawab }}
                    </p>

                    <div class="md:col-span-2">
                        <p><b>Alamat:</b></p>

                        <p class="text-gray-600">
                            {{ $eo->alamat_badan_usaha }}
                        </p>
                    </div>

                </div>

                <!-- REKENING -->
                <div class="mt-6">

                    <p class="font-semibold mb-3">
                        Informasi Rekening
                    </p>

                    <div class="border rounded-xl p-4 bg-gray-50">

                        <div class="mb-3">
                            <p class="text-xs text-gray-500">
                                Nama Bank
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $eo->bank_name ?? '-' }}
                            </p>
                        </div>

                        <div class="mb-3">
                            <p class="text-xs text-gray-500">
                                Nomor Rekening
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $eo->account_number ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">
                                Nama Pemilik Rekening
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $eo->account_name ?? '-' }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- PDF -->
                <div class="mt-6">

                    <p class="font-semibold mb-2">
                        Dokumen Badan Usaha (PDF)
                    </p>

                    <iframe
                        src="{{ asset($eo->dokumen_badan_usaha) }}"
                        class="w-full h-[500px] border rounded-lg">
                    </iframe>

                    <a
                        href="{{ asset($eo->dokumen_badan_usaha) }}"
                        target="_blank"
                        class="text-blue-500 text-sm underline mt-2 inline-block">

                        Buka di tab baru
                    </a>

                </div>

                <!-- KTP -->
                <div class="mt-6">

                    <p class="font-semibold mb-2">
                        KTP Penanggung Jawab
                    </p>

                    <img
                        src="{{ asset($eo->ktp_penanggung_jawab) }}"
                        class="rounded-lg border max-h-[500px]">

                    <a
                        href="{{ asset($eo->ktp_penanggung_jawab) }}"
                        target="_blank"
                        class="text-blue-500 text-sm underline mt-2 inline-block">

                        Buka di tab baru
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endforeach

<!-- SCRIPT -->
<script>

function openModal(id) {

    document
        .getElementById('modal-' + id)
        .classList.remove('hidden');

    document.body.classList.add('overflow-hidden');
}

function closeModal(id) {

    document
        .getElementById('modal-' + id)
        .classList.add('hidden');

    document.body.classList.remove('overflow-hidden');
}

</script>

@endsection