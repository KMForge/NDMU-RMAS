@props(['message'])

<div class="rounded-3xl border-2 border-dashed border-slate-200/90 bg-white p-12 text-center space-y-3">
    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-3xl mx-auto shadow-2xs">
        <i class="ph ph-folder-dashed"></i>
    </div>
    <p class="text-sm font-bold text-slate-700 max-w-md mx-auto">{{ $message }}</p>
</div>

