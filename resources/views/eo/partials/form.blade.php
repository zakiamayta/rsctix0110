<div class="space-y-6">

    {{-- =====================
        INFORMASI UTAMA
    ===================== --}}
    <div>
        <label class="block font-semibold mb-1">Nama Event *</label>
        <input type="text" name="title"
            value="{{ old('title', $event->title ?? '') }}"
            class="w-full rounded-xl border px-4 py-3"
            required>
    </div>

    <div>
        <label class="block font-semibold mb-1">Website / URL Event</label>
        <input type="text" name="event_url"
            placeholder="https://instagram.com/event"
            value="{{ old('event_url', $event->event_url ?? '') }}"
            class="w-full rounded-xl border px-4 py-3">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold mb-1">Organizer</label>
            <input type="text" name="organizer"
                value="{{ old('organizer', $event->organizer ?? '') }}"
                class="w-full rounded-xl border px-4 py-3">
        </div>

        <div>
            <label class="block font-semibold mb-1">Instagram</label>
            <input type="text" name="instagram"
                placeholder="@namaevent"
                value="{{ old('instagram', $event->instagram ?? '') }}"
                class="w-full rounded-xl border px-4 py-3">
        </div>
    </div>

    <div>
        <label class="block font-semibold mb-1">Lineup / Pengisi Acara</label>
        <input type="text" name="lineup"
            placeholder="Band A, DJ B, Guest Star"
            value="{{ old('lineup', $event->lineup ?? '') }}"
            class="w-full rounded-xl border px-4 py-3">
    </div>

    <div>
        <label class="block font-semibold mb-1">Lokasi Event *</label>
        <input type="text" name="location"
            value="{{ old('location', $event->location ?? '') }}"
            class="w-full rounded-xl border px-4 py-3"
            required>
    </div>

    {{-- =====================
        WAKTU & ATURAN
    ===================== --}}
    <div>
        <label class="block font-semibold mb-1">Tanggal & Waktu Event *</label>
        <input type="datetime-local" name="date"
            value="{{ old('date', isset($event) ? \Carbon\Carbon::parse($event->date)->format('Y-m-d\TH:i') : '') }}"
            class="w-full rounded-xl border px-4 py-3"
            required>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold mb-1">Mulai Penjualan Tiket</label>
            <input type="datetime-local" name="ticket_sale_start"
                value="{{ old('ticket_sale_start', isset($event->ticket_sale_start) ? \Carbon\Carbon::parse($event->ticket_sale_start)->format('Y-m-d\TH:i') : '') }}"
                class="w-full rounded-xl border px-4 py-3">
        </div>

        <div>
            <label class="block font-semibold mb-1">Mulai Penukaran Tiket</label>
            <input type="datetime-local" name="ticket_redeem_start"
                value="{{ old('ticket_redeem_start', isset($event->ticket_redeem_start) ? \Carbon\Carbon::parse($event->ticket_redeem_start)->format('Y-m-d\TH:i') : '') }}"
                class="w-full rounded-xl border px-4 py-3">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold mb-1">Usia Minimum</label>
            <input type="number" name="min_age"
                min="0"
                value="{{ old('min_age', $event->min_age ?? '') }}"
                class="w-full rounded-xl border px-4 py-3">
        </div>

        <div>
            <label class="block font-semibold mb-1">Max Tiket per Email *</label>
            <input type="number" name="max_tickets_per_email"
                min="1"
                value="{{ old('max_tickets_per_email', $event->max_tickets_per_email ?? 3) }}"
                class="w-full rounded-xl border px-4 py-3"
                required>
        </div>
    </div>

    {{-- =====================
        DESKRIPSI
    ===================== --}}
    <div>
        <label class="block font-semibold mb-1">Deskripsi Event</label>
        <textarea name="description" rows="5"
            class="w-full rounded-xl border px-4 py-3">{{ old('description', $event->description ?? '') }}</textarea>
    </div>

    {{-- =====================
        POSTER
    ===================== --}}
    <div>
        <label class="block font-semibold mb-2">Poster Event</label>

        @if (!empty($event->poster))
            <div class="mb-3">
                <img src="{{ asset('storage/' . $event->poster) }}"
                    alt="Poster Event"
                    class="h-56 rounded-xl shadow border">
            </div>
        @endif

        <input type="file" name="poster"
            accept="image/*"
            class="w-full rounded-xl border px-4 py-3">
        <p class="text-sm text-gray-500 mt-1">
            JPG / PNG, max 2MB
        </p>
    </div>

</div>
