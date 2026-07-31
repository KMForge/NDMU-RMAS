@extends('layouts.blank')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    /* Subtle scrollbar for sidebar */
    aside::-webkit-scrollbar {
        width: 4px;
    }
    aside::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.02);
    }
    aside::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    aside::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="min-h-screen flex font-sans bg-[#f4f7f6]" x-data="{ 
    activeTab: 'dashboard',
    notificationsFilter: 'all',
    showDetailsModal: false,
    selectedRequest: null,
    
    // Dean static details
    requests: [
        {
            id: 1,
            title: 'AI-Powered Learning Management System',
            student: 'Juan Dela Cruz',
            time: '2 hours ago',
            status: 'Awaiting Approval'
        },
        {
            id: 2,
            title: 'Smart Traffic Monitoring Using IoT',
            student: 'Maria Santos',
            time: '1 day ago',
            status: 'Awaiting Approval'
        },
        {
            id: 3,
            title: 'Mobile Health Application for Rural Areas',
            student: 'Carlos Reyes',
            time: '2 days ago',
            status: 'Awaiting Approval'
        }
    ],

    manuscripts: [
        {
            id: 1,
            title: 'Machine Learning for Crop Disease Detection',
            student: 'Anna Cruz',
            phase: 'Quality Review',
            rating: 'Excellent',
            ratingClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
        },
        {
            id: 2,
            title: 'Blockchain-Based Student Records System',
            student: 'Luis Garcia',
            phase: 'Final Check',
            rating: 'Very Good',
            ratingClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
        },
        {
            id: 3,
            title: 'Renewable Energy Management Platform',
            student: 'Sarah Mendoza',
            phase: 'Ready for Hardbound',
            rating: 'Excellent',
            ratingClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
        }
    ],

    appointments: [
        { name: 'Dr. Maria Santos', role: 'Research Adviser', dept: 'Computer Science', date: 'May 28, 2026', status: 'Active' },
        { name: 'Prof. Roberto Garcia', role: 'Panelist', dept: 'Information Technology', date: 'May 27, 2026', status: 'Active' },
        { name: 'Dr. Patricia Cruz', role: 'Research Adviser', dept: 'Engineering', date: 'May 25, 2026', status: 'Active' },
        { name: 'Prof. Michael Tan', role: 'Panelist', dept: 'Computer Science', date: 'May 24, 2026', status: 'Active' }
    ],

    notifications: [
        {
            id: 1,
            title: 'Proposal Awaiting Sign-off',
            isNew: true,
            badge: 'Dean Approval',
            badgeClass: 'bg-orange-50 border border-orange-100 text-orange-700',
            description: 'Juan Dela Cruz submitted their proposal “AI Learning Management System” for your final oversight approval.',
            time: '3 hours ago',
            icon: 'ph ph-signature',
            iconBg: 'bg-orange-50 text-orange-600',
            unread: true
        },
        {
            id: 2,
            title: 'New Advisor Appointment',
            isNew: false,
            badge: 'Faculty',
            badgeClass: 'bg-blue-50 border border-blue-100 text-blue-700',
            description: 'Dr. Maria Santos has been successfully appointed as a Research Advisor for the CS Department.',
            time: '1 day ago',
            icon: 'ph ph-user-circle',
            iconBg: 'bg-blue-50 text-blue-600',
            unread: false
        }
    ]
}">
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5 overflow-y-auto">
        <div class="flex-shrink-0">
            <!-- Logo -->
            <div class="flex items-center gap-3 p-6 border-b border-white/10">
                <div class="p-1 bg-white/10 rounded-xl border border-white/20">
                    <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-10 w-auto">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-extrabold text-xl text-white tracking-tight">NDMU</span>
                    <span class="text-[9px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Profile Badge -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
                <div class="w-10 h-10 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-bold flex items-center justify-center text-lg flex-shrink-0">
                    D
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm text-white truncate">Dr. Lourdes Castillo</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">College Dean</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>
                
                <!-- Dashboard -->
                <button 
                   type="button" 
                   @click="activeTab = 'dashboard'"
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>
                
                <!-- Pending Approvals -->
                <button 
                   type="button" 
                   @click="activeTab = 'pending'"
                   :class="activeTab === 'pending' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard text-lg"></i>
                        <span>Pending Approvals</span>
                    </div>
                    <span x-show="activeTab === 'pending'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Manuscript Approvals -->
                <button 
                   type="button" 
                   @click="activeTab = 'manuscript'"
                   :class="activeTab === 'manuscript' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-certificate text-lg"></i>
                        <span>Manuscript Approvals</span>
                    </div>
                    <span x-show="activeTab === 'manuscript'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Faculty Appointments -->
                <button 
                   type="button" 
                   @click="activeTab = 'appointments'"
                   :class="activeTab === 'appointments' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>Faculty Appointments</span>
                    </div>
                    <span x-show="activeTab === 'appointments'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Defense Schedules -->
                <button 
                   type="button" 
                   @click="activeTab = 'schedule'"
                   :class="activeTab === 'schedule' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>Defense Schedules</span>
                    </div>
                    <span x-show="activeTab === 'schedule'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Research Reports -->
                <button 
                   type="button" 
                   @click="activeTab = 'reports'"
                   :class="activeTab === 'reports' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Research Reports</span>
                    </div>
                    <span x-show="activeTab === 'reports'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Research Repository -->
                <button 
                   type="button" 
                   @click="activeTab = 'repository'"
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>
            </div>

            <!-- Research Forms Section -->
            <div class="space-y-1.5 pt-4">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Research Forms</span>
                
                <button 
                    type="button"
                    @click="alert('Official NDMU Forms are ready for download')"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg"></i>
                        <span>Official Forms</span>
                    </div>
                    <i class="ph ph-caret-right text-xs text-white/60"></i>
                </button>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-6 pb-6 mt-8">
            <div class="pt-4 border-t border-white/10 space-y-1">
                <!-- Notifications -->
                <a href="#" 
                   @click.prevent="activeTab = 'notifications'"
                   :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-bell text-lg"></i>
                        <span>Notifications</span>
                    </div>
                    <span x-show="activeTab === 'notifications'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
                
                <!-- Settings -->
                <a href="#" 
                   @click.prevent="activeTab = 'settings'"
                   :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>Settings</span>
                    </div>
                    <span x-show="activeTab === 'settings'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer">
                        <i class="ph ph-sign-out text-lg"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
            <div class="text-[9px] text-white/30 text-center font-medium mt-6">
                NDMU © 2026 - v1.0
            </div>
        </div>
    </aside>

    <!-- Right Side: Content Area -->
    <div class="flex-1 flex flex-col min-h-screen pl-72">
        <!-- Top Nav Header -->
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-10 flex-shrink-0">
            <!-- Search bar -->
            <div class="relative w-96">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                    <i class="ph ph-magnifying-glass text-base"></i>
                </span>
                <input
                    type="text"
                    placeholder="Search research, documents, or tasks..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-full text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-350 transition-all duration-200"
                >
            </div>

            <!-- Right profile area matching "D / Dr. Dean's Portal" -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <button @click="activeTab = 'notifications'" class="relative w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <i class="ph ph-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <!-- Dean's Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        D
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">Dr.</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Dean's Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8 space-y-8">
            
            <!-- TAB: Dashboard (Active Default) -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800 animate-fade-in">College Dean Dashboard</h1>
                    <p class="text-xs text-gray-450 mt-1">Final academic oversight and research approval authority</p>
                </div>

                <!-- Stats Cards Row (4 Columns matching layout widgets) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Active Research Projects (Solid Dark Green) -->
                    <div class="bg-[#0e5c3a] text-white rounded-3xl p-5 border border-[#0e5c3a]/10 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-white/80 font-medium block">Active Research Projects</span>
                            <span class="text-3xl font-bold mt-2 block">145</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-white/10 text-[#eebc3f] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-scroll"></i>
                        </span>
                    </div>

                    <!-- Pending Approvals (Solid Yellow) -->
                    <div class="bg-[#eebc3f] text-[#0e5c3a] rounded-3xl p-5 border border-[#eebc3f]/10 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-[#0e5c3a]/80 font-medium block">Pending Approvals</span>
                            <span class="text-3xl font-bold mt-2 block">28</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-white/20 text-[#0e5c3a] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Appointed Advisers -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-[#0e5c3a] border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Appointed Advisers</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">45</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-shield"></i>
                        </span>
                    </div>

                    <!-- Approved Manuscripts -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-[#0e5c3a] border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Approved Manuscripts</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">52</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-book-open"></i>
                        </span>
                    </div>
                </div>

                <!-- Split Row: Requests vs Manuscript Approvals -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Pending Research Requests -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col justify-between">
                        <!-- Dark Green Header -->
                        <div class="bg-[#0e5c3a] text-white p-5 flex items-center justify-between">
                            <h3 class="font-bold text-sm flex items-center gap-2">
                                <i class="ph ph-file-text text-[#eebc3f] text-lg"></i>
                                <span>Pending Research Requests</span>
                            </h3>
                            <i class="ph ph-dots-three-vertical text-white/60"></i>
                        </div>

                        <!-- Requests List -->
                        <div class="p-6 space-y-4 flex-grow">
                            <template x-for="req in requests" :key="req.id">
                                <div class="border border-gray-100 rounded-2xl p-4 flex items-center justify-between hover:border-emerald-100 transition-all">
                                    <div class="space-y-1">
                                        <h4 class="font-bold text-gray-800 text-xs" x-text="req.title">Project Title</h4>
                                        <span class="text-[10px] text-gray-450 block">Student: <span class="font-bold text-gray-700" x-text="req.student">Student Name</span></span>
                                        <span class="text-[9px] text-gray-400 font-medium block" x-text="req.time">2 hours ago</span>
                                    </div>
                                    <span class="bg-amber-50 text-amber-600 px-2 py-0.5 rounded text-[9px] font-bold" x-text="req.status">Awaiting Approval</span>
                                </div>
                            </template>
                        </div>

                        <!-- Footer View All link -->
                        <button @click="activeTab = 'pending'" class="w-full text-center py-4 bg-gray-50 border-t border-gray-100 hover:bg-gray-100 text-gray-600 font-bold text-xs transition-colors cursor-pointer">
                            View All Requests →
                        </button>
                    </div>

                    <!-- Right: Final Manuscript Approvals -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col justify-between">
                        <!-- Yellow/Gold Header -->
                        <div class="bg-[#eebc3f] text-[#0e5c3a] p-5 flex items-center justify-between">
                            <h3 class="font-bold text-sm flex items-center gap-2">
                                <i class="ph ph-certificate text-lg"></i>
                                <span>Final Manuscript Approvals</span>
                            </h3>
                            <i class="ph ph-dots-three-vertical text-[#0e5c3a]/60"></i>
                        </div>

                        <!-- Manuscripts List -->
                        <div class="p-6 space-y-4 flex-grow">
                            <template x-for="ms in manuscripts" :key="ms.id">
                                <div class="border border-gray-100 rounded-2xl p-4 flex items-center justify-between hover:border-emerald-100 transition-all">
                                    <div class="space-y-1">
                                        <h4 class="font-bold text-gray-800 text-xs" x-text="ms.title">Manuscript Title</h4>
                                        <span class="text-[10px] text-gray-450 block">Student: <span class="font-bold text-gray-700" x-text="ms.student">Student Name</span></span>
                                        <span class="text-[9px] text-gray-400 font-medium block" x-text="ms.phase">Quality Review</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded text-[9px] font-bold" :class="ms.ratingClass" x-text="ms.rating">Excellent</span>
                                </div>
                            </template>
                        </div>

                        <!-- Footer View All link -->
                        <button @click="activeTab = 'manuscript'" class="w-full text-center py-4 bg-gray-50 border-t border-gray-100 hover:bg-gray-100 text-gray-600 font-bold text-xs transition-colors cursor-pointer">
                            View All Manuscripts →
                        </button>
                    </div>
                </div>

                <!-- Recent Appointments Section -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="bg-[#0e5c3a] text-white p-5 flex items-center justify-between">
                        <h3 class="font-bold text-sm flex items-center gap-2">
                            <i class="ph ph-users-three text-[#eebc3f] text-lg"></i>
                            <span>Recent Appointments</span>
                        </h3>
                        <button @click="activeTab = 'appointments'" class="text-xs text-white/80 hover:text-white font-bold cursor-pointer">Manage Appointments</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-400 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Department</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-gray-700">
                                <template x-for="app in appointments" :key="app.name">
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-gray-800" x-text="app.name">Faculty Name</td>
                                        <td class="px-6 py-4 font-semibold text-gray-500" x-text="app.role">Role</td>
                                        <td class="px-6 py-4 text-gray-600" x-text="app.dept">Department</td>
                                        <td class="px-6 py-4 text-gray-400 font-medium" x-text="app.date">Date</td>
                                        <td class="px-6 py-4">
                                            <span class="bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full text-[10px] font-bold" x-text="app.status">Active</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bottom quick navigation row (3 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Defense Schedule -->
                    <button @click="activeTab = 'schedule'" class="bg-[#0e5c3a] text-white hover:bg-[#0a4a2e] text-center rounded-3xl p-6 flex flex-col items-center justify-center space-y-2 border border-[#0e5c3a]/15 shadow-sm transition-all cursor-pointer">
                        <i class="ph ph-calendar text-3xl text-[#eebc3f]"></i>
                        <span class="font-bold text-sm block">Defense Schedule</span>
                        <span class="text-[10px] text-white/80">View upcoming defense schedules</span>
                    </button>

                    <!-- Research Reports -->
                    <button @click="activeTab = 'reports'" class="bg-[#eebc3f] text-[#0e5c3a] hover:bg-[#e0b030] text-center rounded-3xl p-6 flex flex-col items-center justify-center space-y-2 border border-[#eebc3f]/15 shadow-sm transition-all cursor-pointer">
                        <i class="ph ph-chart-line-up text-3xl text-[#0e5c3a]"></i>
                        <span class="font-bold text-sm block">Research Reports</span>
                        <span class="text-[10px] text-[#0e5c3a]/80">Generate college reports</span>
                    </button>

                    <!-- Research Repository -->
                    <button @click="activeTab = 'repository'" class="bg-white hover:bg-gray-50 text-center rounded-3xl p-6 flex flex-col items-center justify-center space-y-2 border border-gray-100 shadow-sm transition-all cursor-pointer">
                        <i class="ph ph-book-open text-3xl text-[#0e5c3a]"></i>
                        <span class="font-bold text-sm text-gray-800 block">Research Repository</span>
                        <span class="text-[10px] text-gray-400">Browse approved research</span>
                    </button>
                </div>
            </div>

            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Notifications Center</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Notifications Center</h1>
                            <p class="text-xs text-gray-455 mt-1">Showing notifications for: <span class="font-bold text-gray-850">College Dean</span></p>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button @click="notifications.forEach(n => n.unread = false)" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200 cursor-pointer">
                                <span>✓</span>
                                <span>Mark All Read</span>
                            </button>
                            <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200">
                                <i class="ph ph-bell text-base font-bold"></i>
                                <span x-text="`${notifications.filter(n => n.unread).length} Unread`">1 Unread</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="space-y-4">
                    <template x-for="item in notifications" :key="item.id">
                        <div 
                            class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200 relative group"
                            :class="item.unread ? 'border-l-4 border-l-[#0e5c3a]' : ''"
                        >
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg flex-shrink-0" :class="item.iconBg">
                                <i :class="item.icon"></i>
                            </div>

                            <div class="flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-gray-800 text-sm" x-text="item.title">Notification Title</h4>
                                    <template x-if="item.isNew">
                                        <span class="px-2 py-0.5 rounded bg-emerald-700 text-white text-[9px] font-bold">New</span>
                                    </template>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-semibold" :class="item.badgeClass" x-text="item.badge">Category</span>
                                </div>
                                
                                <p class="text-xs text-gray-550 leading-relaxed" x-text="item.description">
                                    Notification description body copy.
                                </p>
                                
                                <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                    <i class="ph ph-clock"></i>
                                    <span x-text="item.time">1 hour ago</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings', [
                    'avatarInitials' => 'D',
                    'userName' => 'Dr. Lourdes Castillo',
                    'emailAddress' => 'l.castillo@ndmu.edu.ph',
                    'userRole' => 'College Dean',
                    'userRoleBadge' => 'COLLEGE DEAN',
                    'department' => 'College of Information Technology',
                    'userId' => 'EXE-2015-0003',
                    'portalType' => 'Executive Portal',
                    'accessLevel' => 'Executive & Approval Access'
                ])
            </div>

            <!-- TAB: Pending Approvals -->
            <div x-show="activeTab === 'pending'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Proposal Management</h1>
                    <p class="text-xs text-gray-450 mt-1">Manage research proposals and approvals</p>
                </div>

                <!-- Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-emerald-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Approved</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">1</span>
                        </div>
                        <span class="text-emerald-500 text-4xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-amber-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Pending</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">0</span>
                        </div>
                        <span class="text-amber-500 text-4xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">0</span>
                        </div>
                        <span class="text-red-500 text-4xl">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Total Proposals</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">1</span>
                        </div>
                        <span class="text-blue-500 text-4xl">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Research Proposal List Section -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100/50 shadow-sm space-y-6">
                    <h2 class="text-base font-bold text-gray-800 font-heading">Research Proposal</h2>
                    
                    <!-- Proposal Item Card -->
                    <div class="bg-[#f0faf5] rounded-2xl border border-emerald-100/60 p-6 space-y-6">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <h3 class="text-sm font-bold text-gray-900 leading-snug">Machine Learning Applications in Agricultural Pest Detection</h3>
                                <div class="text-[11px] text-gray-450 space-y-0.5 mt-2">
                                    <p>Proposal ID: <span class="font-semibold text-gray-700">PROP-2026-0001</span></p>
                                    <p>Submitted: <span class="font-semibold text-gray-700">March 5, 2026</span></p>
                                </div>
                            </div>
                            <span class="bg-[#10b981] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                Approved
                            </span>
                        </div>

                        <!-- Divider -->
                        <div class="border-t border-emerald-100/50"></div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Reviewed by</span>
                                <span class="text-gray-850 font-bold text-xs mt-1 block">Dr. Maria Santos</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-semibold">Approval Date</span>
                                <span class="text-gray-850 font-bold text-xs mt-1 block">March 10, 2026</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-2">
                            <button @click="alert('Viewing Proposal: Machine Learning Applications in Agricultural Pest Detection')" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-[11px] font-bold rounded-xl transition-all shadow-md shadow-[#0e5c3a]/15 cursor-pointer">
                                View Proposal
                            </button>
                            <button @click="alert('Downloading PDF for PROP-2026-0001')" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-[11px] font-bold rounded-xl transition-all shadow-sm cursor-pointer">
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            </div>

            <!-- TAB: Manuscript Approvals -->
            <div x-show="activeTab === 'manuscript'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Document Review System</h1>
                    <p class="text-xs text-gray-455 mt-1">Review and annotate research documents</p>
                </div>

                <!-- Main Card (Chapter 3 - Research Methodology (Revised)) -->
                <div class="bg-white rounded-[2rem] border border-gray-100/50 shadow-sm p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-3xl border border-red-100 flex-shrink-0">
                            <i class="ph ph-file-pdf"></i>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-base font-bold text-gray-900 leading-snug">Chapter 3 - Research Methodology (Revised)</h2>
                            <p class="text-xs font-semibold text-gray-450">Machine Learning Applications in Agricultural Pest Detection</p>
                            <p class="text-[10px] text-gray-400 font-medium pt-0.5">Uploaded: May 15, 2026  •  Version 2.3  •  42 pages</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto flex-shrink-0">
                        <button @click="alert('Downloading: Chapter 3 - Research Methodology (Revised)')" class="flex-1 md:flex-none px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 shadow-md shadow-[#0e5c3a]/15 transition-all cursor-pointer">
                            <i class="ph ph-download text-base font-bold"></i> Download
                        </button>
                        <button @click="alert('Viewing Full Document: Chapter 3 - Research Methodology (Revised)')" class="flex-1 md:flex-none px-5 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center justify-center transition-all shadow-sm cursor-pointer">
                            View Full Document
                        </button>
                    </div>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-emerald-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Approved</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">8</span>
                        </div>
                        <span class="text-emerald-500 text-4xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-amber-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">5</span>
                        </div>
                        <span class="text-amber-500 text-4xl">
                            <i class="ph ph-warning"></i>
                        </span>
                    </div>

                    <!-- Comments -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Comments</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">12</span>
                        </div>
                        <span class="text-blue-500 text-4xl">
                            <i class="ph ph-chat-text"></i>
                        </span>
                    </div>

                    <!-- Critical -->
                    <div class="bg-white rounded-3xl p-6 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Critical</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">2</span>
                        </div>
                        <span class="text-red-500 text-4xl">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Document Preview & Feedback Split Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Document Preview (Takes 2 spans) -->
                    <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 space-y-6 flex flex-col justify-between">
                        <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                            <i class="ph ph-eye text-[#0e5c3a] text-lg"></i>
                            <span>Document Preview</span>
                        </h3>
                        
                        <div class="border border-gray-100 rounded-2xl p-6 space-y-6 max-h-[600px] overflow-y-auto bg-gray-50/30">
                            <div class="bg-white border border-gray-100 rounded-xl p-8 space-y-6 shadow-sm">
                                <h4 class="text-base font-bold text-gray-900 font-heading text-center">Chapter 3: Research Methodology</h4>
                                <p class="text-xs text-gray-650 leading-relaxed pt-2">
                                    This chapter presents the research design, methods, and procedures employed in this study. The methodology encompasses the research approach, data collection instruments, sampling techniques, and data analysis methods.
                                </p>
                                
                                <h5 class="text-sm font-bold text-gray-850 font-heading pt-2">3.1 Research Design</h5>
                                <p class="text-xs text-gray-650 leading-relaxed">
                                    This study utilizes a quantitative research approach with an experimental design to evaluate the effectiveness of machine learning algorithms in detecting agricultural pests...
                                </p>
                                
                                <h5 class="text-sm font-bold text-gray-850 font-heading pt-2">3.2 Data Collection</h5>
                                <p class="text-xs text-gray-650 leading-relaxed">
                                    The data collection process involves capturing high-resolution images of crops from various agricultural sites across South Cotabato province...
                                </p>
                                
                                <!-- Highlighted Reviewer Note Block -->
                                <div class="border-l-4 border-l-amber-500 bg-amber-50/40 p-4 rounded-r-2xl border border-t-transparent border-r-transparent border-b-transparent">
                                    <p class="text-xs text-amber-800 leading-relaxed">
                                        <span class="font-bold text-amber-900">Reviewer Note:</span> Consider adding more details about the image preprocessing steps used in your methodology.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Comments & Feedback (Takes 1 span) -->
                    <div class="lg:col-span-1 bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 flex flex-col justify-between space-y-6">
                        <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                            <i class="ph ph-chat-text text-[#0e5c3a] text-lg"></i>
                            <span>Comments & Feedback</span>
                        </h3>
                        
                        <!-- List of comments -->
                        <div class="space-y-4 overflow-y-auto max-h-[460px] pr-1">
                            <!-- Comment 1 -->
                            <div class="border border-gray-100 border-l-4 border-l-amber-500 rounded-2xl p-4 space-y-3 bg-white hover:border-gray-200 transition-all">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="leading-tight">
                                        <span class="font-bold text-gray-800 text-xs block">Dr. Maria Santos</span>
                                        <span class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">Adviser</span>
                                    </div>
                                    <span class="text-[9px] text-gray-400 font-medium">2 hours ago</span>
                                </div>
                                <p class="text-xs text-gray-650 leading-relaxed">
                                    Please expand this section with more recent studies from 2024-2026.
                                </p>
                                <div class="flex items-center justify-between text-[10px] font-bold border-t border-gray-50 pt-2 text-gray-400">
                                    <span>Page 12</span>
                                    <div class="flex items-center gap-3">
                                        <button @click="alert('Reply function')" class="hover:text-gray-700 cursor-pointer">Reply</button>
                                        <button @click="alert('Resolve function')" class="text-emerald-600 hover:text-emerald-700 cursor-pointer">Resolve</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Comment 2 -->
                            <div class="border border-gray-100 border-l-4 border-l-emerald-500 rounded-2xl p-4 space-y-3 bg-white hover:border-gray-200 transition-all">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="leading-tight">
                                        <span class="font-bold text-gray-800 text-xs block">Dr. John Reyes</span>
                                        <span class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">Panelist</span>
                                    </div>
                                    <span class="text-[9px] text-gray-400 font-medium">5 hours ago</span>
                                </div>
                                <p class="text-xs text-gray-650 leading-relaxed">
                                    Excellent data presentation. Well organized.
                                </p>
                                <div class="flex items-center justify-between text-[10px] font-bold border-t border-gray-50 pt-2 text-gray-400">
                                    <span>Page 18</span>
                                    <div class="flex items-center gap-3">
                                        <button @click="alert('Reply function')" class="hover:text-gray-700 cursor-pointer">Reply</button>
                                        <button @click="alert('Resolve function')" class="text-emerald-600 hover:text-emerald-700 cursor-pointer">Resolve</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Comment 3 -->
                            <div class="border border-gray-100 border-l-4 border-l-amber-500 rounded-2xl p-4 space-y-3 bg-white hover:border-gray-200 transition-all">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="leading-tight">
                                        <span class="font-bold text-gray-800 text-xs block">Prof. Anna Garcia</span>
                                        <span class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">Technical Editor</span>
                                    </div>
                                    <span class="text-[9px] text-gray-400 font-medium">1 day ago</span>
                                </div>
                                <p class="text-xs text-gray-650 leading-relaxed">
                                    Check citation format on this page - should follow APA 7th edition.
                                </p>
                                <div class="flex items-center justify-between text-[10px] font-bold border-t border-gray-50 pt-2 text-gray-400">
                                    <span>Page 5</span>
                                    <div class="flex items-center gap-3">
                                        <button @click="alert('Reply function')" class="hover:text-gray-700 cursor-pointer">Reply</button>
                                        <button @click="alert('Resolve function')" class="text-emerald-600 hover:text-emerald-700 cursor-pointer">Resolve</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Add Comment form -->
                        <div class="space-y-3 pt-2">
                            <textarea placeholder="Add a comment..." rows="2" class="w-full p-3 bg-white border border-gray-200 rounded-2xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all resize-none"></textarea>
                            <button @click="alert('Posting comment...')" class="w-full py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md shadow-[#0e5c3a]/15 transition-all cursor-pointer">
                                Post Comment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Review Actions Section (Full width bottom card) -->
                <div class="bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center gap-2">
                        <i class="ph ph-shield-check text-[#0e5c3a] text-lg"></i>
                        <span>Review Actions</span>
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button @click="alert('Approved Document')" class="py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 transition-all cursor-pointer">
                            <i class="ph ph-check-circle text-base"></i> Approve Document
                        </button>
                        
                        <button @click="alert('Requested Revisions')" class="py-4 bg-[#d97706] hover:bg-[#b45309] text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-amber-600/10 transition-all cursor-pointer">
                            <i class="ph ph-warning text-base"></i> Request Revisions
                        </button>
                        
                        <button @click="alert('Rejected Document')" class="py-4 bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-red-600/10 transition-all cursor-pointer">
                            <i class="ph ph-x-circle text-base"></i> Reject Document
                        </button>
                    </div>
                </div>
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'settings', 'pending', 'manuscript'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-3xl">
                    <i class="ph ph-terminal-window"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800 uppercase tracking-wide" x-text="activeTab.replace('_', ' ').replace('-', ' ')">Tab Title</h2>
                    <p class="text-xs text-gray-455 mt-1">This protected page section is ready for its backend integration.</p>
                </div>
                <button @click="activeTab = 'dashboard'" class="px-4 py-2 bg-[#0e5c3a] text-white text-xs font-bold rounded-xl transition-all cursor-pointer">
                    Back to Dashboard
                </button>
            </div>
            
        </main>
    </div>

</div>
@endsection
