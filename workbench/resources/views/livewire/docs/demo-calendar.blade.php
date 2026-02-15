<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-3">
            <div class="text-sm font-semibold text-slate-900">Interactive Demo</div>
            <div class="mt-1 text-xs text-slate-600">
                Click events, drag them, resize them, or select time slots. The panel on the right shows what Livewire received.
            </div>
        </div>

        <div class="p-4">
            @include('livewire-calendar::livewire-calendar')
        </div>
    </div>

    <aside class="rounded-xl border border-slate-200 bg-slate-950 text-slate-50 shadow-sm">
        <div class="border-b border-white/10 px-4 py-3">
            <div class="text-xs font-semibold tracking-wide text-slate-200">Last Interaction</div>
            <div class="mt-1 text-sm text-white">
                {{ $lastInteraction ?? '—' }}
            </div>
        </div>

        <div class="p-4">
            <pre class="text-xs leading-relaxed text-slate-100 whitespace-pre-wrap">{{ json_encode($lastInteractionPayload, JSON_PRETTY_PRINT) ?: '{}' }}</pre>
        </div>
    </aside>
</div>
