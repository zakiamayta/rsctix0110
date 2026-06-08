<form method="POST" action="{{ route('event.request-reschedule', $event->id) }}">
    @csrf
    @method('PUT')

    {{-- CURRENT SCHEDULE --}}
    <div>
        <label class="block text-sm font-medium mb-1">
            Jadwal Saat Ini
        </label>

        <input type="text"
               value="{{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y H:i') }}"
               disabled
               class="w-full rounded-xl border bg-gray-100 px-3 py-2">
    </div>

    {{-- NEW DATE --}}
    <div>
        <label class="block text-sm font-medium mb-1">
            Jadwal Baru
        </label>

        <input type="datetime-local"
               name="proposed_date"
               required
               class="w-full rounded-xl border px-3 py-2">
    </div>

    {{-- REASON --}}
    <div>
        <label class="block text-sm font-medium mb-1">
            Alasan Reschedule
        </label>

        <textarea name="reschedule_reason"
                  rows="5"
                  required
                  class="w-full rounded-xl border px-3 py-2"></textarea>
    </div>

    {{-- SUBMIT --}}
    <button type="submit"
            class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
        Kirim Pengajuan Reschedule
    </button>

</form>