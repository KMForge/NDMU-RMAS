@php
    $settingsUser = auth()->user();
    $userName = $settingsUser?->name ?? ($userName ?? 'User Account');
    $emailAddress = $settingsUser?->email ?? ($emailAddress ?? '');
    $department = $settingsUser?->department ?? ($department ?? 'College of Information Technology');
    $userId = $settingsUser?->student_id ?? ($settingsUser?->id ? 'ID-' . str_pad($settingsUser->id, 4, '0', STR_PAD_LEFT) : ($userId ?? 'N/A'));
    $userRole = $userRole ?? ($settingsUser ? ucfirst($settingsUser->user_type->value ?? 'User') : 'User');
    $userRoleBadge = $userRoleBadge ?? strtoupper($userRole);
    $portalType = $portalType ?? ($userRole . ' Portal');
    $accessLevel = $accessLevel ?? ($userRole . ' Access');
    $avatarInitials = strtoupper(substr(trim($userName), 0, 1));

    $canManageDigitalSignature = $settingsUser
        && Illuminate\Support\Facades\Gate::allows('create', App\Models\UserSignature::class);
    $registeredSignature = $canManageDigitalSignature ? $settingsUser->signature : null;
@endphp

<!-- NDMU Research Management Account Settings Partial -->
<div class="space-y-8" x-data="{
    emailNotifications: true,
    smsNotifications: false,
    defenseReminders: true,
    chapterUpdates: true,
    consultationReminders: true,
    systemAnnouncements: true,
    securityAlerts: true,
    twoFactor: false,
    showPasswords: false,
    theme: 'light',
    firstName: '{{ explode(' ', $userName)[1] ?? $userName }}',
    lastName: '{{ explode(' ', $userName)[2] ?? '' }}',
    emailAddress: '{{ $emailAddress ?? '' }}',
    phoneNumber: '+63 912 345 6789',
    homeAddress: 'Koronadal City, South Cotabato',
    username: '{{ strtolower(str_replace(' ', '.', $userName)) }}'
}">
    <!-- Profile Banner Summary -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] p-7 sm:p-8 text-white shadow-xl border border-emerald-800/40">
        <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-[#eebc3f]/10 blur-2xl pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center gap-5">
                <div class="relative flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-2xl font-black text-[#073823] shadow-lg border-2 border-white/20">
                    <span>{{ $avatarInitials }}</span>
                    <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-400 border-2 border-[#073823]"></span>
                    </span>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f] font-black text-[9px] uppercase tracking-[0.16em]">
                            {{ $userRoleBadge }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-black uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black font-heading text-white tracking-tight">{{ $userName }}</h2>
                    <p class="text-xs text-emerald-100/80 font-medium">{{ $department }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-4 py-2 text-xs font-bold text-white shadow-inner">
                    <span class="text-[9px] uppercase tracking-wider text-[#eebc3f] block font-black">Account ID</span>
                    <span class="font-mono font-bold">{{ $userId }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Split Columns Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column: Info Cards -->
        <div class="space-y-8">
            <!-- Profile Information -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#0e5c3a]"></div>
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <span class="w-2.5 h-6 rounded-full bg-[#0e5c3a]"></span>
                    <h3 class="font-black text-slate-900 text-sm font-heading flex items-center gap-2">
                        <span>Profile Information</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">First Name</label>
                        <input type="text" x-model="firstName" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Last Name</label>
                        <input type="text" x-model="lastName" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
                    <input type="email" x-model="emailAddress" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 cursor-not-allowed" disabled>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" x-model="phoneNumber" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Department / College</label>
                        <input type="text" value="{{ $department }}" class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 cursor-not-allowed" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1.5">Institutional Address</label>
                    <input type="text" x-model="homeAddress" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 transition-all">
                </div>
            </div>

            <!-- Role & Portal Access -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-4 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#0e5c3a] to-[#eebc3f]"></div>
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <span class="w-2.5 h-6 rounded-full bg-[#0e5c3a]"></span>
                    <h3 class="font-black text-slate-900 text-sm font-heading">Role & Portal Access</h3>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="flex justify-between py-3">
                        <span class="font-bold text-slate-500">Current Role</span>
                        <span class="font-black text-slate-900">{{ $userRole }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-bold text-slate-500">Portal Type</span>
                        <span class="font-black text-slate-900">{{ $portalType }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-bold text-slate-500">Access Level</span>
                        <span class="font-black text-[#0e5c3a]">{{ $accessLevel }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-bold text-slate-500">Account Status</span>
                        <span class="font-black text-emerald-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 block animate-pulse"></span> Active & Verified
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings & Digital Signature -->
        <div class="space-y-8">
            <!-- Digital Signature Enrollment -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <span class="w-2.5 h-6 rounded-full bg-[#0e5c3a]"></span>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm font-heading">Digital Signature Specimen</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Used for authoritative form approvals and endorsements.</p>
                    </div>
                </div>

                @if (session('signature_success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800" role="status">
                        {{ session('signature_success') }}
                    </div>
                @endif

                @error('signature')
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-800" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                @if (! $canManageDigitalSignature)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-600 font-medium">
                        System administrators cannot register or apply academic digital signatures.
                    </div>
                @else
                    <p class="text-xs leading-relaxed text-slate-600 font-medium">
                        Register a clear PNG or JPEG specimen of your signature. It is securely encrypted and only applied after your explicit authorization.
                    </p>

                    @if ($registeredSignature)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="rounded-xl border border-white bg-white p-3 shadow-xs">
                                    <img
                                        src="{{ route('signature.show') }}"
                                        alt="Your registered digital signature"
                                        class="h-16 w-52 object-contain"
                                    >
                                </div>
                                <div class="text-xs text-slate-600 sm:text-right">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[10px] uppercase">
                                        <i class="ph ph-seal-check"></i> Enrolled
                                    </span>
                                    <p class="mt-1 font-medium text-slate-500 text-[11px]">{{ $registeredSignature->registered_at?->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('signature.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500">
                                {{ $registeredSignature ? 'Replace signature specimen' : 'Signature specimen image' }}
                            </label>
                            <input
                                type="file"
                                name="signature"
                                accept="image/png,image/jpeg,.png,.jpg,.jpeg"
                                required
                                class="block w-full rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-[#0e5c3a] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-[#073823] cursor-pointer"
                            >
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium">PNG or JPEG only, max 2 MB. Transparent background recommended.</p>
                        <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-4 py-2.5 text-xs font-black text-white transition-colors shadow-md cursor-pointer">
                            {{ $registeredSignature ? 'Replace Digital Signature' : 'Register Digital Signature' }}
                        </button>
                    </form>

                    @if ($registeredSignature)
                        <form method="POST" action="{{ route('signature.destroy') }}" onsubmit="return confirm('Remove your registered digital signature?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full rounded-xl border border-rose-200 bg-white hover:bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-600 transition-colors cursor-pointer">
                                Remove Digital Signature
                            </button>
                        </form>
                    @endif
                @endif
            </div>

            <!-- Notification Preferences -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#073823] to-[#eebc3f]"></div>
                <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                    <span class="w-2.5 h-6 rounded-full bg-[#0e5c3a]"></span>
                    <h3 class="font-black text-slate-900 text-sm font-heading">Notification Preferences</h3>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Email Notifications</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block font-medium">Receive updates via university email</span>
                        </div>
                        <button type="button" @click="emailNotifications = !emailNotifications" :class="emailNotifications ? 'bg-[#0e5c3a]' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out">
                            <span :class="emailNotifications ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Defense Schedule Reminders</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block font-medium">48 hours prior to defense events</span>
                        </div>
                        <button type="button" @click="defenseReminders = !defenseReminders" :class="defenseReminders ? 'bg-[#0e5c3a]' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out">
                            <span :class="defenseReminders ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-slate-900 text-xs block">Manuscript Review Updates</span>
                            <span class="text-[10px] text-slate-400 mt-0.5 block font-medium">When adviser or panel issues revision findings</span>
                        </div>
                        <button type="button" @click="chapterUpdates = !chapterUpdates" :class="chapterUpdates ? 'bg-[#0e5c3a]' : 'bg-slate-200'" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out">
                            <span :class="chapterUpdates ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

