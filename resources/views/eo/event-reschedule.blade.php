<form method="POST"
      action="{{ route('eo.event.request-reschedule', $event->id) }}">
    @csrf
    @method('PUT')

    {{-- CURRENT SCHEDULE --}}
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">
            Jadwal Saat Ini
        </label>

        <input type="text"
               value="{{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y H:i') }}"
               disabled
               class="w-full rounded-xl border bg-gray-100 px-3 py-2">
    </div>

    {{-- NEW DATE --}}
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">
            Jadwal Baru
        </label>

        <input type="datetime-local"
               name="proposed_date"
               value="{{ old('proposed_date') }}"
               min="{{ now()->format('Y-m-d\TH:i') }}"
               required
               class="w-full rounded-xl border px-3 py-2">

        @error('proposed_date')
            <p class="text-red-500 text-sm mt-1">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- REASON --}}
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">
            Alasan Reschedule
        </label>

        <textarea name="reschedule_reason"
                  rows="5"
                  required
                  class="w-full rounded-xl border px-3 py-2">{{ old('reschedule_reason') }}</textarea>

        @error('reschedule_reason')
            <p class="text-red-500 text-sm mt-1">
                {{ $message }}
            </p>
        @enderror
    </div>

    <button type="submit"
            class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700">
        Kirim Pengajuan Reschedule
    </button>
</form>