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
    showClassModal: false,
    showConsultationModal: false,
    selectedNotification: null,
    
    // Static state data for high-fidelity interactive elements
    notifications: [
        {
            id: 1,
            type: 'submission',
            title: 'New Research Submission',
            isNew: true,
            badge: 'New Submission',
            badgeClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700',
            description: 'Maria Santos submitted Chapter 3 – Methodology for your review. Please provide feedback within 5 working days.',
            time: '1 hour ago',
            icon: 'ph ph-file-text',
            iconBg: 'bg-emerald-50 text-emerald-600',
            category: 'documents',
            unread: true
        },
        {
            id: 2,
            type: 'revision',
            title: 'Revision Submitted',
            isNew: true,
            badge: 'Revision',
            badgeClass: 'bg-blue-50 border border-blue-100 text-blue-750',
            description: 'Carlo Bautista submitted the revised Chapter 2 addressing your previous comments. Ready for re-review.',
            time: '3 hours ago',
            icon: 'ph ph-cloud-arrow-up',
            iconBg: 'bg-blue-50 text-blue-600',
            category: 'documents',
            unread: true,
            hasActions: true
        },
        {
            id: 3,
            type: 'consultation',
            title: 'Consultation Requested',
            isNew: true,
            badge: 'Consultation',
            badgeClass: 'bg-purple-50 border border-purple-100 text-purple-700',
            description: 'Maria Santos requested a consultation session for May 25, 2026 at 9:00 AM. Please confirm availability.',
            time: '6 hours ago',
            icon: 'ph ph-chat-teardrop',
            iconBg: 'bg-purple-50 text-purple-600',
            category: 'approvals',
            unread: true
        },
        {
            id: 4,
            type: 'defense',
            title: 'Defense Session Scheduled',
            isNew: false,
            badge: 'Defense',
            badgeClass: 'bg-indigo-50 border border-indigo-100 text-indigo-700',
            description: 'The proposal defense for Team AI-Traffic is scheduled on May 29, 2026 at 10:00 AM.',
            time: '1 day ago',
            icon: 'ph ph-calendar',
            iconBg: 'bg-indigo-50 text-indigo-600',
            category: 'defense',
            unread: false
        },
        {
            id: 5,
            type: 'approval',
            title: 'Proposal Endorsement Signed',
            isNew: false,
            badge: 'Approval',
            badgeClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700',
            description: 'You endorsed the proposal “Smart Agriculture IoT Platform” for defense scheduling.',
            time: '2 days ago',
            icon: 'ph ph-check-square',
            iconBg: 'bg-emerald-50 text-emerald-600',
            category: 'approvals',
            unread: false
        },
        {
            id: 6,
            type: 'system',
            title: 'System Maintenance Notice',
            isNew: false,
            badge: 'System',
            badgeClass: 'bg-gray-50 border border-gray-100 text-gray-700',
            description: 'The NDMU Research Management Portal will undergo scheduled maintenance on Sunday from 2:00 AM to 4:00 AM.',
            time: '3 days ago',
            icon: 'ph ph-gear',
            iconBg: 'bg-gray-50 text-gray-600',
            category: 'system',
            unread: false
        }
    ],

    classes: [
        { code: 'CS-401', name: 'Software Engineering Capstone', students: 12, submissions: 3 },
        { code: 'IT-402', name: 'Information Technology Project', students: 8, submissions: 1 },
        { code: 'CS-402', name: 'Artificial Intelligence Research', students: 6, submissions: 2 }
    ],

    assignedResearchers: [
        { name: 'Juan Dela Cruz', project: 'AI-Powered Traffic Management System', status: 'Data Gathering', progress: 65, avatar: 'J' },
        { name: 'Maria Clara Santos', project: 'Blockchain-Based Voting System', status: 'Final Defense Prep', progress: 82, avatar: 'M' },
        { name: 'Ana Rodriguez', project: 'Mobile Health Monitoring App', status: 'Data Analysis', progress: 58, avatar: 'A' }
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
                    <span class="font-semibold text-sm text-white truncate">Dr. Reyna Garcia</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Research Adviser</span>
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
                
                <!-- My Classes -->
                <button 
                   type="button" 
                   @click="activeTab = 'classes'"
                   :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book text-lg"></i>
                        <span>My Classes</span>
                    </div>
                    <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Join Requests -->
                <button 
                   type="button" 
                   @click="activeTab = 'requests'"
                   :class="activeTab === 'requests' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-user-plus text-lg"></i>
                        <span>Join Requests</span>
                    </div>
                    <span x-show="activeTab === 'requests'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Assigned Researchers -->
                <button 
                   type="button" 
                   @click="activeTab = 'researchers'"
                   :class="activeTab === 'researchers' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>Assigned Researchers</span>
                    </div>
                    <span x-show="activeTab === 'researchers'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Proposal Review -->
                <button 
                   type="button" 
                   @click="activeTab = 'proposal'"
                   :class="activeTab === 'proposal' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-search text-lg"></i>
                        <span>Proposal Review</span>
                    </div>
                    <span x-show="activeTab === 'proposal'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Research Monitoring -->
                <button 
                   type="button" 
                   @click="activeTab = 'monitoring'"
                   :class="activeTab === 'monitoring' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span>Research Monitoring</span>
                    </div>
                    <span x-show="activeTab === 'monitoring'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Consultation Records -->
                <button 
                   type="button" 
                   @click="activeTab = 'consultation'"
                   :class="activeTab === 'consultation' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chat-teardrop text-lg"></i>
                        <span>Consultation Records</span>
                    </div>
                    <span x-show="activeTab === 'consultation'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Document Review -->
                <button 
                   type="button" 
                   @click="activeTab = 'docreview'"
                   :class="activeTab === 'docreview' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Document Review</span>
                    </div>
                    <span x-show="activeTab === 'docreview'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Revision Management -->
                <button 
                   type="button" 
                   @click="activeTab = 'revisions'"
                   :class="activeTab === 'revisions' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-arrows-counter-clockwise text-lg"></i>
                        <span>Revision Management</span>
                    </div>
                    <span x-show="activeTab === 'revisions'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Defense Endorsement -->
                <button 
                   type="button" 
                   @click="activeTab = 'endorsement'"
                   :class="activeTab === 'endorsement' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-certificate text-lg"></i>
                        <span>Defense Endorsement</span>
                    </div>
                    <span x-show="activeTab === 'endorsement'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Evaluation Records -->
                <button 
                   type="button" 
                   @click="activeTab = 'evaluations'"
                   :class="activeTab === 'evaluations' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-notebook text-lg"></i>
                        <span>Evaluation Records</span>
                    </div>
                    <span x-show="activeTab === 'evaluations'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
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

            <!-- Right profile area -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell with Active Indicator -->
                <button @click="activeTab = 'notifications'" class="relative w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <i class="ph ph-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <!-- Faculty Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        A
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">Dr.</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Faculty Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8">
            
            <!-- TAB: Dashboard (Active Default) -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                <!-- Header / Breadcrumbs & Buttons -->
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800 animate-fade-in">Adviser Dashboard</h1>
                        <p class="text-xs text-gray-450 mt-1">Monitor and guide your advisees' research progress</p>
                    </div>
                    
                    <button @click="activeTab = 'researchers'" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md transition-colors cursor-pointer">
                        <i class="ph ph-users-three text-base"></i>
                        <span>View All Advisees</span>
                    </button>
                </div>

                <!-- Stats Cards Row (4 Columns matching layout) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Active Advisees (Solid Green) -->
                    <div class="bg-[#0e5c3a] text-white rounded-3xl p-5 border border-[#0e5c3a]/10 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-white/80 font-medium block">Active Advisees</span>
                            <span class="text-3xl font-bold mt-2 block">12</span>
                            <span class="text-[10px] text-[#eebc3f] font-bold mt-1 block">3 nearing defense</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-white/10 text-[#eebc3f] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-users-three"></i>
                        </span>
                    </div>

                    <!-- Urgent Reviews (White/Red left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Urgent Reviews</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">7</span>
                            <span class="text-[10px] text-red-500 font-bold mt-1 block">3 overdue submissions</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Today's Consultations (White/Blue left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Today's Consultations</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">3</span>
                            <span class="text-[10px] text-blue-500 font-bold mt-1 block">Next: 2:00 PM</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Completed Research (White/Purple left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-purple-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Completed Research</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">28</span>
                            <span class="text-[10px] text-purple-500 font-bold mt-1 block">This academic year</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-trend-up"></i>
                        </span>
                    </div>
                </div>

                <!-- Main Layout Columns (Left: 2/3 Progress Monitor, Right: 1/3 Schedule & Actions) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Advisees Progress Monitor -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6 lg:col-span-2">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-chart-bar text-emerald-600 text-lg"></i>
                                <span>Advisees Progress Monitor</span>
                            </h3>
                            <span class="text-xs font-semibold text-gray-450 hover:underline cursor-pointer">Sort by Name</span>
                        </div>

                        <!-- Progress Monitor List -->
                        <div class="space-y-4">
                            <template x-for="r in assignedResearchers" :key="r.name">
                                <div class="border border-gray-100 bg-gray-50/10 rounded-2xl p-5 space-y-4 hover:border-emerald-100 hover:bg-emerald-50/5 transition-all">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-gray-850 text-sm" x-text="r.name">Student Name</h4>
                                            <span class="text-xs text-gray-400 block mt-0.5" x-text="r.project">Research project title goes here.</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-extrabold text-emerald-700 block" x-text="`${r.progress}%`">65%</span>
                                            <span class="text-[9px] text-gray-400 uppercase tracking-wider">Complete</span>
                                        </div>
                                    </div>

                                    <!-- Badges -->
                                    <div class="flex gap-2">
                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold" x-text="r.status">Data Gathering</span>
                                        <span class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold">On Track</span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="space-y-1">
                                        <div class="h-2 bg-gray-100 rounded-full w-full overflow-hidden">
                                            <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" :style="`width: ${r.progress}%`"></div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-3 pt-1">
                                        <button @click="alert(`Opening ${r.name}'s research proposal...`)" class="w-1/2 text-center py-2 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                                            View Research
                                        </button>
                                        <button @click="alert(`Reviewing documents for ${r.name}...`)" class="w-1/2 text-center py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-sm transition-colors cursor-pointer">
                                            Review Documents
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- View All Footer Link -->
                        <button @click="activeTab = 'researchers'" class="w-full text-center py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold text-xs rounded-2xl transition-colors cursor-pointer">
                            View All 12 Advisees →
                        </button>
                    </div>

                    <!-- Right: Pending, Schedule, Actions -->
                    <div class="space-y-6">
                        
                        <!-- Side Widget 1: Pending Reviews -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                                <i class="ph ph-file-text text-amber-500 text-lg"></i>
                                <span>Pending Reviews (3)</span>
                            </h3>

                            <div class="space-y-4">
                                <!-- Item 1 -->
                                <div class="border-l-4 border-l-amber-500 bg-amber-50/10 rounded-2xl p-4 border-t border-r border-b border-gray-100/50 space-y-3">
                                    <div>
                                        <span class="font-bold text-gray-800 text-xs block">Chapter 3 - Methodology</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Juan Dela Cruz</span>
                                        <span class="text-[10px] text-amber-600 font-bold mt-1.5 block flex items-center gap-1">
                                            <i class="ph ph-clock"></i> Submitted 2 hours ago
                                        </span>
                                    </div>
                                    <button @click="alert('Opening Chapter 3 Review window')" class="w-full text-center py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                        Review Document
                                    </button>
                                </div>

                                <!-- Item 2 -->
                                <div class="border-l-4 border-l-red-500 bg-red-50/10 rounded-2xl p-4 border-t border-r border-b border-gray-100/50 space-y-3">
                                    <div>
                                        <span class="font-bold text-gray-800 text-xs block">Revised Proposal</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Pedro Reyes</span>
                                        <span class="text-[10px] text-red-500 font-bold mt-1.5 block flex items-center gap-1">
                                            <i class="ph ph-clock"></i> Submitted 1 day ago
                                        </span>
                                    </div>
                                    <button @click="alert('Opening Revised Proposal Review window')" class="w-full text-center py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                        Review Document
                                    </button>
                                </div>

                                <!-- Item 3 -->
                                <div class="border-l-4 border-l-red-500 bg-red-50/10 rounded-2xl p-4 border-t border-r border-b border-gray-100/50 space-y-3">
                                    <div>
                                        <span class="font-bold text-gray-800 text-xs block">Chapter 2 - Literature Review</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Ana Rodriguez</span>
                                        <span class="text-[10px] text-red-500 font-bold mt-1.5 block flex items-center gap-1">
                                            <i class="ph ph-clock"></i> Submitted 3 days ago
                                        </span>
                                    </div>
                                    <button @click="alert('Opening Chapter 2 Review window')" class="w-full text-center py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                        Review Document
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Side Widget 2: Today's Consultations (Solid Blue Card) -->
                        <div class="bg-blue-600 text-white rounded-3xl p-6 shadow-md space-y-4">
                            <h3 class="font-bold text-white text-sm flex items-center gap-2 pb-2 border-b border-white/10">
                                <i class="ph ph-calendar text-lg"></i>
                                <span>Today's Consultations</span>
                            </h3>

                            <div class="space-y-3">
                                <!-- Consultation 1 -->
                                <div class="flex items-center justify-between py-2 border-b border-white/10">
                                    <div>
                                        <span class="font-bold text-xs block">Juan Dela Cruz</span>
                                        <span class="text-[10px] text-white/80 mt-0.5 block">Methodology Review</span>
                                    </div>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded font-bold">2:00 PM</span>
                                </div>

                                <!-- Consultation 2 -->
                                <div class="flex items-center justify-between py-2 border-b border-white/10">
                                    <div>
                                        <span class="font-bold text-xs block">Ana Rodriguez</span>
                                        <span class="text-[10px] text-white/80 mt-0.5 block">Data Analysis</span>
                                    </div>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded font-bold">3:30 PM</span>
                                </div>

                                <!-- Consultation 3 -->
                                <div class="flex items-center justify-between py-2">
                                    <div>
                                        <span class="font-bold text-xs block">Maria Clara</span>
                                        <span class="text-[10px] text-white/80 mt-0.5 block">Defense Preparation</span>
                                    </div>
                                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded font-bold">4:30 PM</span>
                                </div>
                            </div>

                            <button @click="activeTab = 'consultation'" class="w-full text-center py-2 bg-white hover:bg-gray-50 text-blue-600 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                                View Full Schedule
                            </button>
                        </div>

                        <!-- Side Widget 3: Quick Actions -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Quick Actions</h3>
                            <div class="space-y-2.5">
                                <button @click="activeTab = 'proposal'" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Review Proposals
                                </button>
                                <button @click="activeTab = 'consultation'" class="bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Schedule Consultation
                                </button>
                                <button @click="activeTab = 'endorsement'" class="bg-purple-50 hover:bg-purple-100 text-purple-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Recommend for Defense
                                </button>
                            </div>
                        </div>

                        <!-- Side Widget 4: Recent Activity -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Recent Activity</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0">
                                        <i class="ph ph-check"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-800 text-xs block">Approved Chapter 2</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Juan Dela Cruz • 2h ago</span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-sm flex-shrink-0">
                                        <i class="ph ph-chat-teardrop"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-800 text-xs block">Left feedback</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">Maria Clara • 5h ago</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            
            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8">
                <!-- Header / Breadcrumbs & Buttons -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Notifications Center</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Notifications Center</h1>
                            <p class="text-xs text-gray-450 mt-1">Showing notifications for: <span class="font-bold text-gray-850">Research Adviser</span></p>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button @click="notifications.forEach(n => n.unread = false)" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200 cursor-pointer">
                                <span>✓</span>
                                <span>Mark All Read</span>
                            </button>
                            <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200">
                                <i class="ph ph-bell text-base font-bold"></i>
                                <span x-text="`${notifications.filter(n => n.unread).length} Unread`">3 Unread</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.length">6</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Total</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-bell"></i>
                        </span>
                    </div>

                    <!-- Unread Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.unread).length">3</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Unread</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Defense Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.category === 'defense').length">1</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Defense</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Documents Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.category === 'documents').length">2</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Documents</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Filter Bar (Capsule Pills) -->
                <div class="flex flex-wrap gap-2.5 items-center bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <button 
                        @click="notificationsFilter = 'all'"
                        :class="notificationsFilter === 'all' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>All</span>
                        <span 
                            :class="notificationsFilter === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.length">6</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'unread'"
                        :class="notificationsFilter === 'unread' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Unread</span>
                        <span 
                            :class="notificationsFilter === 'unread' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.unread).length">3</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'approvals'"
                        :class="notificationsFilter === 'approvals' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Approvals</span>
                        <span 
                            :class="notificationsFilter === 'approvals' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'approvals').length">2</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'defense'"
                        :class="notificationsFilter === 'defense' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Defense</span>
                        <span 
                            :class="notificationsFilter === 'defense' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'defense').length">1</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'documents'"
                        :class="notificationsFilter === 'documents' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Documents</span>
                        <span 
                            :class="notificationsFilter === 'documents' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'documents').length">2</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'system'"
                        :class="notificationsFilter === 'system' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>System</span>
                        <span 
                            :class="notificationsFilter === 'system' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'system').length">1</span>
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="space-y-4">
                    <template x-for="item in notifications" :key="item.id">
                        <div 
                            x-show="notificationsFilter === 'all' || (notificationsFilter === 'unread' && item.unread) || (notificationsFilter === item.category)"
                            class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200 relative group"
                            :class="item.unread ? 'border-l-4 border-l-[#0e5c3a]' : ''"
                        >
                            <!-- Notification Icon Wrapper -->
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg flex-shrink-0" :class="item.iconBg">
                                <i :class="item.icon"></i>
                            </div>

                            <!-- Notification Content -->
                            <div class="flex-1 space-y-3 pr-12">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="font-bold text-gray-800 text-sm" x-text="item.title">Notification Title</h4>
                                    
                                    <!-- Dynamic Badges -->
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
                                
                                <div class="flex justify-between items-center pt-2 text-xs">
                                    <button @click="selectedNotification = item" class="text-emerald-700 font-bold hover:underline cursor-pointer">Tap to view full details</button>
                                    <template x-if="item.unread">
                                        <button @click="item.unread = false" class="text-gray-400 hover:text-gray-600 font-semibold cursor-pointer">Mark as read</button>
                                    </template>
                                </div>
                            </div>

                            <!-- Special Actions -->
                            <div x-show="item.hasActions" class="absolute right-6 top-6 bottom-6 flex flex-col justify-between items-end">
                                <!-- Check circle tick -->
                                <button @click="alert('Approved revision!'); item.unread = false" class="text-emerald-600 hover:text-emerald-800 text-lg transition-colors p-1 cursor-pointer">
                                    <i class="ph ph-check"></i>
                                </button>
                                
                                <!-- Trash Icon -->
                                <button @click="notifications = notifications.filter(n => n.id !== item.id)" class="text-gray-400 hover:text-red-500 text-lg transition-colors p-1 cursor-pointer">
                                    <i class="ph ph-trash"></i>
                                </button>
                                
                                <!-- Arrow right icon -->
                                <button @click="selectedNotification = item" class="text-gray-400 hover:text-gray-700 text-lg transition-colors p-1 cursor-pointer">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Classes Mockup -->
            <div x-show="activeTab === 'classes'" x-cloak class="space-y-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">My Classes</h1>
                        <p class="text-xs text-gray-450 mt-1">Manage classes and student research tracking</p>
                    </div>
                    <button @click="showClassModal = true" class="px-4 py-2.5 bg-[#0e5c3a] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md cursor-pointer">
                        <i class="ph ph-plus-circle text-base"></i>
                        <span>Create Class</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="c in classes">
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 hover:border-gray-200 transition-all">
                            <div class="flex justify-between items-start">
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 text-[10px] font-extrabold rounded-full" x-text="c.code">CODE-101</span>
                                <i class="ph ph-dots-three-vertical text-gray-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm" x-text="c.name">Class Name</h3>
                                <p class="text-[11px] text-gray-400 mt-1">2026 Academic Year</p>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-gray-50 text-xs">
                                <span class="text-gray-500 font-semibold" x-text="`${c.students} Students`">12 Students</span>
                                <span class="text-amber-600 font-bold" x-text="`${c.submissions} Submissions`">3 Submissions</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings', [
                    'avatarInitials' => 'D',
                    'userName' => 'Dr. Reyna Garcia',
                    'emailAddress' => 'r.garcia@ndmu.edu.ph',
                    'userRole' => 'Research Adviser',
                    'userRoleBadge' => 'RESEARCH ADVISER',
                    'department' => 'College of Information Technology',
                    'userId' => 'ADV-2015-0002',
                    'portalType' => 'Faculty Portal',
                    'accessLevel' => 'Faculty & Guidance Access'
                ])
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'classes', 'settings'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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

    <!-- Notification Details Modal -->
    <div x-show="selectedNotification" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="selectedNotification = null" class="bg-white rounded-3xl w-full max-w-md p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg flex-shrink-0" :class="selectedNotification?.iconBg">
                        <i :class="selectedNotification?.icon"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm" x-text="selectedNotification?.title">Notification Details</h3>
                        <span class="text-[10px] text-gray-400" x-text="selectedNotification?.time">1 hour ago</span>
                    </div>
                </div>
                <button @click="selectedNotification = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <div class="space-y-2">
                <span class="px-2 py-0.5 rounded text-[9px] font-semibold" :class="selectedNotification?.badgeClass" x-text="selectedNotification?.badge">Category</span>
                <p class="text-xs text-gray-650 leading-relaxed" x-text="selectedNotification?.description">
                    Full notification description message text goes here.
                </p>
            </div>
            <div class="pt-4 flex justify-end">
                <button @click="selectedNotification = null" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Create Class Modal Mockup -->
    <div x-show="showClassModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showClassModal = false" class="bg-white rounded-3xl w-full max-w-md p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-gray-800 text-sm">Create New Research Class</h3>
                <button @click="showClassModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Class Name</label>
                    <input type="text" placeholder="e.g. Software Engineering Capstone" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Class Code</label>
                    <input type="text" placeholder="e.g. CS-401" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3">
                <button @click="showClassModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Cancel
                </button>
                <button @click="alert('Class created successfully! (Mock)'); showClassModal = false" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                    Create
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
