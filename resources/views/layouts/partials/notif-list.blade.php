@php
    $colorMap = [
        'green' => 'bg-green-100 text-green-600',
        'red'   => 'bg-red-100 text-red-600',
        'blue'  => 'bg-blue-100 text-blue-600',
        'amber' => 'bg-amber-100 text-amber-600',
    ];
@endphp

@forelse(($buyerNotifications ?? []) as $notif)
    <a href="{{ $notif['url'] }}"
       class="notif-item flex gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition"
       data-time="{{ $notif['time_iso'] }}">

        <span class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-base {{ $colorMap[$notif['color']] ?? 'bg-gray-100 text-gray-600' }}">
            {{ $notif['icon'] }}
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-800 leading-tight">
                {{ $notif['title'] }}
            </p>
            <p class="text-xs text-gray-600 leading-snug mt-0.5 break-words">
                {{ $notif['message'] }}
            </p>
            <p class="text-[11px] text-gray-400 mt-1">
                {{ $notif['time_human'] }}
            </p>
        </div>
    </a>
@empty
    <div class="px-4 py-10 text-center text-gray-400">
        <div class="text-3xl mb-2">🔔</div>
        <p class="text-sm">Belum ada notifikasi</p>
    </div>
@endforelse
