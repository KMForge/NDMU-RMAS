<!-- NDMU Research Management Account Settings Partial -->
<div x-data="{
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
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">
        <span>Dashboard</span>
        <span>/</span>
        <span class="text-[#0e5c3a]">Settings</span>
    </div>

    <!-- Header Actions Row -->
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold font-heading text-gray-800">Account Settings</h1>
            <p class="text-xs text-gray-450 mt-1">Manage your profile, security, and preferences.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <button @click="alert('Resetting settings changes...')" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200 cursor-pointer">
                <i class="ph ph-arrows-counter-clockwise text-base"></i>
                <span>Reset</span>
            </button>
            <button @click="alert('Changes saved successfully!')" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200 cursor-pointer">
                <i class="ph ph-floppy-disk text-base"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </div>

    <!-- Profile Banner Summary -->
    <div class="bg-[#0e5c3a] text-white rounded-3xl p-6 flex items-center gap-6 relative overflow-hidden shadow-sm">
        <div class="w-24 h-24 rounded-3xl bg-[#eebc3f] text-[#0e5c3a] font-extrabold flex items-center justify-center text-4xl relative flex-shrink-0">
            <span>{{ $avatarInitials }}</span>
            <button @click="alert('Upload new profile picture mockup')" class="absolute -bottom-1 -right-1 w-7 h-7 bg-white text-gray-700 border border-gray-150 rounded-xl flex items-center justify-center shadow-sm hover:bg-gray-50 cursor-pointer">
                <i class="ph ph-camera text-sm"></i>
            </button>
        </div>
        <div>
            <span class="text-[10px] text-white/80 font-bold uppercase tracking-wider block">{{ $userRoleBadge }}</span>
            <h2 class="text-2xl font-bold font-heading text-white mt-1">{{ $userName }}</h2>
            <span class="text-xs text-white/60 font-semibold block mt-0.5">{{ $department }}</span>
            
            <div class="flex flex-wrap items-center gap-2 mt-3">
                <span class="bg-black/20 border border-white/10 text-[#eebc3f] px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                    {{ $userId }}
                </span>
                <span class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                    Active Account
                </span>
            </div>
        </div>
    </div>

    <!-- Details Split Columns Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
        
        <!-- Left Column: Info Cards -->
        <div class="space-y-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-user text-emerald-600 text-lg"></i>
                    <span>Profile Information</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">First Name</label>
                        <input type="text" x-model="firstName" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Last Name</label>
                        <input type="text" x-model="lastName" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email Address</label>
                    <input type="email" x-model="emailAddress" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-450 focus:outline-none" disabled>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Phone Number</label>
                        <input type="text" x-model="phoneNumber" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Department / College</label>
                        <input type="text" value="{{ $department }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 focus:outline-none" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Home Address</label>
                    <input type="text" x-model="homeAddress" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                </div>
            </div>

            <!-- Account Details -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-hash text-emerald-600 text-lg"></i>
                    <span>Account Details</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Username</label>
                        <input type="text" x-model="username" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Employee / Student ID</label>
                        <input type="text" value="{{ $userId }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 focus:outline-none" disabled>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Date of Birth</label>
                        <input type="text" value="15/06/2003" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Date Registered</label>
                        <input type="text" value="July 1, 2015" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 focus:outline-none" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Bio / About</label>
                    <textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">Undergraduate researcher specializing in machine learning and educational technology.</textarea>
                </div>
            </div>

            <!-- Role & Portal Access -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-shield text-emerald-600 text-lg"></i>
                    <span>Role & Portal Access</span>
                </h3>

                <div class="divide-y divide-gray-50 text-xs">
                    <div class="flex justify-between py-3">
                        <span class="font-semibold text-gray-500">Current Role</span>
                        <span class="font-bold text-gray-800">{{ $userRole }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-semibold text-gray-500">Portal Type</span>
                        <span class="font-bold text-gray-800">{{ $portalType }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-semibold text-gray-500">Access Level</span>
                        <span class="font-bold text-gray-800">{{ $accessLevel }}</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="font-semibold text-gray-500">Account Status</span>
                        <span class="font-bold text-emerald-600 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 block animate-pulse"></span> Active
                        </span>
                    </div>
                </div>
                <div class="text-[10px] text-gray-400 font-medium pt-2 text-center">
                    To request a role change, contact the System Administrator.
                </div>
            </div>
        </div>

        <!-- Right Column: Settings & Preferences -->
        <div class="space-y-6">
            <!-- Notification Preferences -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-bell text-emerald-600 text-lg"></i>
                    <span>Notification Preferences</span>
                </h3>

                <div class="space-y-4">
                    <!-- Email Toggles -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Email Notifications</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">Receive updates via NDMU email</span>
                        </div>
                        <button type="button" @click="emailNotifications = !emailNotifications" :class="emailNotifications ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="emailNotifications ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- SMS Toggles -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">SMS Notifications</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">Get text alerts on your phone</span>
                        </div>
                        <button type="button" @click="smsNotifications = !smsNotifications" :class="smsNotifications ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="smsNotifications ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- Defense Schedule Reminders -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Defense Schedule Reminders</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">48 hours before defense events</span>
                        </div>
                        <button type="button" @click="defenseReminders = !defenseReminders" :class="defenseReminders ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="defenseReminders ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- Chapter Review Updates -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Chapter Review Updates</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">When adviser approves or requests revision</span>
                        </div>
                        <button type="button" @click="chapterUpdates = !chapterUpdates" :class="chapterUpdates ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="chapterUpdates ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- Consultation Reminders -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Consultation Reminders</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">Upcoming consultation appointments</span>
                        </div>
                        <button type="button" @click="consultationReminders = !consultationReminders" :class="consultationReminders ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="consultationReminders ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- System Announcements -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">System Announcements</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">University-wide research notices</span>
                        </div>
                        <button type="button" @click="systemAnnouncements = !systemAnnouncements" :class="systemAnnouncements ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="systemAnnouncements ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>

                    <!-- Security Alerts -->
                    <div class="flex items-center justify-between py-1.5">
                        <div>
                            <span class="font-bold text-gray-800 text-xs block">Security Alerts</span>
                            <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">Login attempts and account changes</span>
                        </div>
                        <button type="button" @click="securityAlerts = !securityAlerts" :class="securityAlerts ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="securityAlerts ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Security Settings & Device Activities -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-lock text-emerald-600 text-lg"></i>
                    <span>Security Settings</span>
                </h3>

                <div class="space-y-4">
                    <span class="font-bold text-gray-700 text-xs block">Change Password</span>
                    
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Current Password</label>
                        <input :type="showPasswords ? 'text' : 'password'" placeholder="Enter current password" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>

                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">New Password</label>
                        <input :type="showPasswords ? 'text' : 'password'" placeholder="Minimum 8 characters" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>

                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Confirm New Password</label>
                        <input :type="showPasswords ? 'text' : 'password'" placeholder="Repeat new password" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    </div>

                    <button type="button" @click="showPasswords = !showPasswords" class="text-[10px] text-gray-450 hover:text-gray-600 font-bold flex items-center gap-1.5 cursor-pointer">
                        <i :class="showPasswords ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-sm"></i>
                        <span x-text="showPasswords ? 'Hide passwords' : 'Show passwords'">Show passwords</span>
                    </button>

                    <button @click="alert('Password updated successfully!')" class="w-full py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white font-bold text-xs rounded-xl shadow-md transition-colors cursor-pointer text-center">
                        Update Password
                    </button>
                </div>

                <hr class="border-gray-50">

                <!-- Two-Factor Auth -->
                <div class="flex items-center justify-between py-1">
                    <div>
                        <span class="font-bold text-gray-800 text-xs block">Two-Factor Authentication</span>
                        <span class="text-[10px] text-gray-400 mt-0.5 block font-medium">Add an extra layer of security</span>
                    </div>
                    <button type="button" @click="twoFactor = !twoFactor" :class="twoFactor ? 'bg-emerald-600' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                        <span :class="twoFactor ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                <hr class="border-gray-50">

                <!-- Recent Login Activity -->
                <div class="space-y-4">
                    <span class="font-bold text-gray-700 text-xs block">Recent Login Activity</span>
                    
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-lg flex-shrink-0">
                                    <i class="ph ph-monitor"></i>
                                </span>
                                <div>
                                    <span class="font-bold text-gray-800 text-xs block">Chrome · Windows 11</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5 font-medium">Koronadal City · Today, 8:34 AM</span>
                                </div>
                            </div>
                            <span class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[9px] font-bold">Current</span>
                        </div>

                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-lg flex-shrink-0">
                                    <i class="ph ph-device-mobile"></i>
                                </span>
                                <div>
                                    <span class="font-bold text-gray-800 text-xs block">Safari · iPhone 14</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5 font-medium">Koronadal City · Yesterday, 6:12 PM</span>
                                </div>
                            </div>
                            <button @click="alert('Session revoked.')" class="text-red-500 hover:text-red-700 text-[10px] font-bold cursor-pointer font-semibold">Revoke</button>
                        </div>

                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-lg flex-shrink-0">
                                    <i class="ph ph-monitor"></i>
                                </span>
                                <div>
                                    <span class="font-bold text-gray-800 text-xs block">Chrome · Windows 11</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5 font-medium">Koronadal City · May 28, 2026, 9:01 AM</span>
                                </div>
                            </div>
                            <button @click="alert('Session revoked.')" class="text-red-500 hover:text-red-700 text-[10px] font-bold cursor-pointer font-semibold">Revoke</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance & System -->
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5">
                <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                    <i class="ph ph-palette text-emerald-600 text-lg"></i>
                    <span>Appearance & System</span>
                </h3>

                <div class="space-y-4">
                    <!-- Theme Selector -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Color Theme</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button @click="theme = 'light'" :class="theme === 'light' ? 'border-[#0e5c3a] bg-emerald-50/5 text-[#0e5c3a]' : 'border-gray-150 text-gray-500 hover:bg-gray-50'" class="border py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer">
                                <i class="ph ph-sun"></i> Light
                            </button>
                            <button @click="theme = 'dark'" :class="theme === 'dark' ? 'border-[#0e5c3a] bg-emerald-50/5 text-[#0e5c3a]' : 'border-gray-150 text-gray-500 hover:bg-gray-50'" class="border py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer">
                                <i class="ph ph-moon"></i> Dark
                            </button>
                            <button @click="theme = 'system'" :class="theme === 'system' ? 'border-[#0e5c3a] bg-emerald-50/5 text-[#0e5c3a]' : 'border-gray-150 text-gray-500 hover:bg-gray-50'" class="border py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all cursor-pointer">
                                <i class="ph ph-desktop"></i> System
                            </button>
                        </div>
                    </div>

                    <!-- Language Dropdown -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Language</label>
                        <select class="w-full bg-gray-50 border border-gray-150 rounded-xl text-xs text-gray-700 px-3 py-2.5 outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                            <option>English (US)</option>
                            <option>Filipino (Philippines)</option>
                        </select>
                    </div>

                    <!-- Timezone Dropdown -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Timezone</label>
                        <select class="w-full bg-gray-50 border border-gray-150 rounded-xl text-xs text-gray-700 px-3 py-2.5 outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                            <option>Asia/Manila (UTC+8)</option>
                            <option>America/New_York (UTC-5)</option>
                        </select>
                    </div>

                    <!-- Date Format Dropdown -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Date Format</label>
                        <select class="w-full bg-gray-50 border border-gray-150 rounded-xl text-xs text-gray-700 px-3 py-2.5 outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                            <option>MM/DD/YYYY</option>
                            <option>DD/MM/YYYY</option>
                            <option>YYYY-MM-DD</option>
                        </select>
                    </div>

                    <!-- Danger Zone -->
                    <div class="pt-4 border-t border-gray-50">
                        <span class="block text-[10px] font-bold text-red-500 uppercase mb-2">Danger Zone</span>
                        <button @click="alert('Deactivate account request sent to Admin.')" class="w-full py-2.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold text-xs rounded-xl flex items-center justify-center gap-2 transition-colors cursor-pointer">
                            <i class="ph ph-warning-octagon text-base"></i>
                            <span>Deactivate Account</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
