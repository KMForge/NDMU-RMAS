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
    showApprovalModal: false,
    selectedApproval: null,
    
    // Facilitator static details
    approvals: [
        {
            id: 1,
            type: 'Proposal',
            typeClass: 'bg-orange-50 border border-orange-100 text-orange-700',
            date: 'May 20, 2026',
            title: 'AI-Powered Agricultural Pest Detection',
            student: 'Maria Santos',
            adviser: 'Dr. Roberto Garcia',
            status: 'Pending'
        },
        {
            id: 2,
            type: 'Defense',
            typeClass: 'bg-amber-50 border border-amber-100 text-amber-700',
            date: 'May 19, 2026',
            title: 'IoT-Based Smart Classroom Management',
            student: 'Anna Reyes',
            adviser: 'Dr. Patricia Cruz',
            status: 'Pending'
        },
        {
            id: 3,
            type: 'Final Manuscript',
            typeClass: 'bg-red-50 border border-red-100 text-red-700',
            date: 'May 18, 2026',
            title: 'Community Health Information System',
            student: 'Luis Fernandez',
            adviser: 'Dr. Michael Tan',
            status: 'Pending'
        }
    ],

    advisers: [
        { name: 'Dr. Roberto Garcia', load: 8, max: 10, color: 'bg-amber-500' },
        { name: 'Dr. Patricia Cruz', load: 6, max: 10, color: 'bg-emerald-600' },
        { name: 'Dr. Michael Tan', load: 9, max: 10, color: 'bg-red-500' },
        { name: 'Dr. Ana Reyes', load: 5, max: 10, color: 'bg-emerald-600' },
        { name: 'Dr. Juan Santos', load: 7, max: 10, color: 'bg-amber-500' }
    ],

    kanban: [
        {
            stage: 'Title Presentation',
            count: 0,
            color: 'bg-blue-100 text-blue-800',
            items: []
        },
        {
            stage: 'Proposal Approved',
            count: 1,
            color: 'bg-emerald-100 text-emerald-800',
            items: [
                {
                    code: 'RES-2026-005',
                    title: 'Educational Mobile App for Indigenous Languages',
                    students: 'Elena Rodriguez, Marco Diaz',
                    adviser: 'Dr. Patricia Cruz',
                    date: 'September 5, 2026',
                    progress: 30,
                    progressColor: 'bg-emerald-600'
                }
            ]
        },
        {
            stage: 'Validation',
            count: 1,
            color: 'bg-purple-100 text-purple-800',
            items: [
                {
                    code: 'RES-2026-004',
                    title: 'Blockchain Technology in Supply Chain Management',
                    students: 'Carlos Mendoza',
                    adviser: 'Dr. Roberto Garcia',
                    date: 'August 10, 2026',
                    progress: 35,
                    progressColor: 'bg-purple-600'
                }
            ]
        },
        {
            stage: 'Data Gathering',
            count: 1,
            color: 'bg-amber-100 text-amber-800',
            items: [
                {
                    code: 'RES-2026-001',
                    title: 'Machine Learning Applications in Agricultural Pest Detection',
                    students: 'Maria Santos, Juan Dela Cruz',
                    adviser: 'Dr. Roberto Garcia',
                    date: 'July 15, 2026',
                    progress: 65,
                    progressColor: 'bg-amber-500'
                }
            ]
        }
    ],

    categories: [
        { name: 'Artificial Intelligence', count: 12, percent: 80, color: 'bg-blue-600' },
        { name: 'Healthcare Technology', count: 8, percent: 40, color: 'bg-red-500' },
        { name: 'Education', count: 10, percent: 60, color: 'bg-purple-600' },
        { name: 'Agriculture', count: 7, percent: 35, color: 'bg-emerald-600' },
        { name: 'IoT & Smart Systems', count: 8, percent: 50, color: 'bg-orange-500' }
    ],

    defenses: [
        { title: 'AI-Powered Learning System', student: 'Juan Dela Cruz', type: 'Final Defense', date: 'June 20, 2026 • 2:00 PM' },
        { title: 'Smart Agriculture IoT Platform', student: 'Maria Santos', type: 'Proposal Defense', date: 'June 22, 2026 • 10:00 AM' },
        { title: 'Healthcare Monitoring System', student: 'Anna Reyes', type: 'Final Defense', date: 'June 25, 2026 • 3:00 PM' }
    ],

    notifications: [
        {
            id: 1,
            title: 'Pending Proposal Approval',
            isNew: true,
            badge: 'Approval',
            badgeClass: 'bg-orange-50 border border-orange-100 text-orange-700',
            description: 'Team AI-Pest submitted their research proposal for department oversight clearance.',
            time: '10 minutes ago',
            icon: 'ph ph-file-search',
            iconBg: 'bg-orange-50 text-orange-600',
            unread: true
        },
        {
            id: 2,
            title: 'Report Generated Successfully',
            isNew: false,
            badge: 'System',
            badgeClass: 'bg-gray-50 border border-gray-100 text-gray-700',
            description: 'The Q2 Research Progress Summary PDF has been generated and is ready for download.',
            time: '2 hours ago',
            icon: 'ph ph-check-circle',
            iconBg: 'bg-emerald-50 text-emerald-600',
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
                    <span class="font-semibold text-sm text-white truncate">Dr. Rosario Dela Paz</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Research Facilitator</span>
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

                <!-- Adviser Assignments -->
                <button 
                   type="button" 
                   @click="activeTab = 'advisers'"
                   :class="activeTab === 'advisers' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>Adviser Assignments</span>
                    </div>
                    <span x-show="activeTab === 'advisers'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Research Screening -->
                <button 
                   type="button" 
                   @click="activeTab = 'screening'"
                   :class="activeTab === 'screening' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-search text-lg"></i>
                        <span>Research Screening</span>
                    </div>
                    <span x-show="activeTab === 'screening'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Defense Management -->
                <button 
                   type="button" 
                   @click="activeTab = 'defenses'"
                   :class="activeTab === 'defenses' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>Defense Management</span>
                    </div>
                    <span x-show="activeTab === 'defenses'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Research Statistics -->
                <button 
                   type="button" 
                   @click="activeTab = 'statistics'"
                   :class="activeTab === 'statistics' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-bar text-lg"></i>
                        <span>Research Statistics</span>
                    </div>
                    <span x-show="activeTab === 'statistics'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
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

            <!-- Right profile area matching "F / Dr. Facilitator Portal" -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <button @click="activeTab = 'notifications'" class="relative w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <i class="ph ph-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <!-- Facilitator Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        F
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">Dr.</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Facilitator Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8 space-y-8">
            
            <!-- TAB: Dashboard (Active Default) -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                <!-- Welcome Title & Header Buttons -->
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800 animate-fade-in">Welcome Back, Faculty Head!</h1>
                        <p class="text-xs text-gray-450 mt-1">Department Research Oversight & Monitoring Dashboard</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <a href="#pending-approvals" class="px-4 py-2.5 bg-[#eebc3f] hover:bg-[#e0b030] text-[#0e5c3a] text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-colors cursor-pointer">
                            <i class="ph ph-warning-circle text-base"></i>
                            <span>7 Pending Approvals</span>
                        </a>
                        <button @click="alert('Generating Research Q2 report summary PDF...')" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md transition-colors cursor-pointer">
                            <i class="ph ph-chart-line-up text-base"></i>
                            <span>Generate Report</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards Row (4 Columns matching widgets) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Active Research -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-3">
                                <i class="ph ph-book-open"></i>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold block">Active Research</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">45</span>
                            <span class="text-[10px] text-emerald-600 font-bold mt-1 block flex items-center gap-1">
                                <i class="ph ph-trend-up"></i> +8 this month
                            </span>
                        </div>
                        <span class="text-emerald-500 text-xl font-bold">
                            <i class="ph ph-trend-up"></i>
                        </span>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-3">
                                <i class="ph ph-clipboard-text"></i>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold block">Pending Approvals</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">7</span>
                            <span class="text-[10px] text-amber-600 font-bold mt-1 block">Requires action</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Upcoming Defenses -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl mb-3">
                                <i class="ph ph-calendar"></i>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold block">Upcoming Defenses</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">12</span>
                            <span class="text-[10px] text-blue-600 font-bold mt-1 block">Next 30 days</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Completed (2026) -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl mb-3">
                                <i class="ph ph-certificate"></i>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold block">Completed (2026)</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">28</span>
                            <span class="text-[10px] text-purple-600 font-bold mt-1 block">+5 from last quarter</span>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-trend-up"></i>
                        </span>
                    </div>
                </div>

                <!-- Graphs & Adviser Workloads Split Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Performance Trends Graph Mockup -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6 lg:col-span-2">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm">Research Performance Trends</h3>
                                <p class="text-[10px] text-gray-400 mt-0.5">Monthly completion and submission rates</p>
                            </div>
                            <select class="bg-gray-50 border border-gray-200 text-gray-700 text-xs px-2.5 py-1.5 rounded-xl outline-none">
                                <option>Last 6 Months</option>
                                <option>This Year</option>
                            </select>
                        </div>

                        <!-- Pure CSS/SVG line graph representation -->
                        <div class="relative h-48 w-full flex items-end justify-between pt-4 px-2 border-b border-l border-gray-150">
                            <!-- SVG lines visual overlay representation -->
                            <svg class="absolute inset-0 w-full h-full p-2" viewBox="0 0 400 120" preserveAspectRatio="none">
                                <!-- Completed Line (Green) -->
                                <path d="M 0 90 Q 80 50 160 70 T 320 20 T 400 10" fill="none" stroke="#10b981" stroke-width="2" />
                                <!-- In Progress Line (Blue) -->
                                <path d="M 0 100 Q 80 80 160 50 T 320 60 T 400 40" fill="none" stroke="#3b82f6" stroke-width="2" />
                                <!-- Delayed Line (Yellow) -->
                                <path d="M 0 110 Q 80 105 160 100 T 320 85 T 400 90" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="3,3" />
                            </svg>
                            
                            <!-- Columns to structure grid baseline references -->
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-white px-1">Jan</span>
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-gray-100 border border-gray-200 rounded px-1.5">Feb</span>
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-white px-1">Mar</span>
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-white px-1">Apr</span>
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-white px-1">May</span>
                            <span class="text-[9px] text-gray-400 font-bold relative z-10 bg-white px-1">Jun</span>
                        </div>

                        <!-- Graph Legend -->
                        <div class="flex items-center gap-6 text-[10px] font-semibold text-gray-650 justify-center">
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 block"></span> Completed</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 block"></span> In Progress</span>
                            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 block"></span> Delayed</span>
                        </div>
                    </div>

                    <!-- Adviser Workload -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5">
                        <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                            <i class="ph ph-users text-emerald-600 text-lg"></i>
                            <span>Adviser Workload</span>
                        </h3>

                        <div class="space-y-4">
                            <template x-for="adv in advisers" :key="adv.name">
                                <div class="space-y-1">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-gray-700" x-text="adv.name">Adviser Name</span>
                                        <span class="font-bold text-gray-800" x-text="`${adv.load}/${adv.max}`">8/10</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 rounded-full w-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300" :class="adv.color" :style="`width: ${(adv.load/adv.max)*100}%`"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button @click="activeTab = 'advisers'" class="w-full text-center py-2.5 text-[#0e5c3a] font-bold text-xs hover:underline mt-2">
                            View All Advisers →
                        </button>
                    </div>
                </div>

                <!-- Section: Pending Approvals & Endorsements (Screenshot 2) -->
                <div id="pending-approvals" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-warning-circle text-amber-500"></i>
                                <span>Pending Approvals & Endorsements</span>
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Items requiring Faculty Head approval</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="alert('Filtering approvals...')" class="px-3 py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-[11px] font-bold rounded-xl cursor-pointer">Filter</button>
                            <button @click="alert('Approved all pending submissions'); approvals.forEach(a => a.status = 'Approved')" class="px-3 py-1.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-[11px] font-bold rounded-xl cursor-pointer">Approve All</button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <template x-for="app in approvals" :key="app.id">
                            <div x-show="app.status === 'Pending'" class="border border-gray-150 rounded-2xl p-5 flex items-center justify-between hover:border-emerald-100 hover:bg-emerald-50/5 transition-all">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold tracking-wide uppercase" :class="app.typeClass" x-text="app.type">Proposal</span>
                                        <span class="text-[10px] text-gray-400 font-semibold" x-text="app.date">May 20, 2026</span>
                                    </div>
                                    <h4 class="font-bold text-gray-850 text-sm" x-text="app.title">Project Title</h4>
                                    <span class="text-xs text-gray-450 block">Student: <span class="font-bold text-gray-700" x-text="app.student">Maria Santos</span> • Adviser: <span class="font-semibold text-gray-650" x-text="app.adviser">Dr. Roberto Garcia</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button @click="alert(`Approved: ${app.title}`); app.status = 'Approved'" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                                        <i class="ph ph-check"></i> Approve
                                    </button>
                                    <button @click="selectedApproval = app" class="px-4 py-2 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer">
                                        <i class="ph ph-eye"></i> Review
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Section: Research Monitoring Board (Kanban - Screenshot 3) -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <div class="flex flex-wrap justify-between items-center pb-2 border-b border-gray-50 gap-4">
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-chart-line-up text-emerald-600 text-lg"></i>
                                <span>Research Monitoring Board</span>
                            </h3>
                            <p class="text-[10px] text-gray-400 mt-0.5">Live workflow tracking across all stages</p>
                        </div>
                        <div class="relative w-72">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-magnifying-glass text-sm"></i>
                            </span>
                            <input type="text" placeholder="Search research..." class="w-full pl-9 pr-8 py-1.5 bg-gray-50 border border-gray-150 rounded-full text-xs text-gray-800 placeholder-gray-400 outline-none">
                            <button class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                <i class="ph ph-sliders"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Horizontally scrollable Kanban area -->
                    <div class="overflow-x-auto pb-4 flex gap-6">
                        <template x-for="col in kanban" :key="col.stage">
                            <div class="flex-shrink-0 w-72 bg-gray-50/50 border border-gray-100/80 rounded-2xl p-4 space-y-4">
                                <!-- Stage Header -->
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                    <span class="text-xs font-bold text-gray-700" x-text="col.stage">Title Presentation</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="col.color" x-text="col.count">0</span>
                                </div>

                                <!-- Stage Cards -->
                                <div class="space-y-3">
                                    <template x-if="col.items.length === 0">
                                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center text-xs text-gray-450">
                                            No research in this stage
                                        </div>
                                    </template>
                                    <template x-for="item in col.items" :key="item.code">
                                        <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-xs space-y-3 hover:shadow-sm transition-shadow">
                                            <div class="flex justify-between items-center">
                                                <span class="text-[9px] font-extrabold text-emerald-800" x-text="item.code">RES-2026-005</span>
                                                <i class="ph ph-dots-three-vertical text-gray-400"></i>
                                            </div>
                                            <h5 class="font-bold text-gray-800 text-xs line-clamp-2" x-text="item.title">Research Title</h5>
                                            
                                            <!-- Meta info links -->
                                            <div class="space-y-1.5 text-[10px] text-gray-400 font-semibold">
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ph ph-user"></i>
                                                    <span class="truncate text-gray-600" x-text="item.students">Students</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ph ph-users"></i>
                                                    <span class="truncate text-gray-600" x-text="item.adviser">Adviser</span>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <i class="ph ph-calendar"></i>
                                                    <span x-text="item.date">Date</span>
                                                </div>
                                            </div>

                                            <div class="space-y-1 pt-1 border-t border-gray-50">
                                                <div class="flex justify-between items-center text-[9px] font-bold">
                                                    <span class="text-gray-400">Progress</span>
                                                    <span class="text-emerald-700" x-text="`${item.progress}%`">30%</span>
                                                </div>
                                                <div class="h-1 bg-gray-100 rounded-full w-full overflow-hidden">
                                                    <div class="h-full rounded-full" :class="item.progressColor" :style="`width: ${item.progress}%`"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Lower Split row: Category vs Defenses -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Research by Category -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-5 lg:col-span-2">
                        <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Research by Category</h3>
                        
                        <div class="space-y-4">
                            <template x-for="cat in categories" :key="cat.name">
                                <div class="space-y-1">
                                    <div class="flex justify-between items-center text-xs font-semibold text-gray-700">
                                        <span x-text="cat.name">Category</span>
                                        <span class="font-bold text-gray-850" x-text="`${cat.count} projects`">12 projects</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 rounded-full w-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300" :class="cat.color" :style="`width: ${cat.percent}%`"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Upcoming Defenses list -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-50">
                            <h3 class="font-bold text-gray-800 text-sm">Upcoming Defenses</h3>
                            <button @click="activeTab = 'defenses'" class="text-[#0e5c3a] font-bold text-xs hover:underline cursor-pointer">View All →</button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="def in defenses" :key="def.title">
                                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0">
                                        <i class="ph ph-calendar"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-gray-800 text-xs block truncate" x-text="def.title">Project Title</span>
                                        <span class="text-[10px] text-gray-400 block mt-0.5 truncate" x-text="`Student: ${def.student}`">Student Name</span>
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[9px] rounded font-bold" x-text="def.type">Final Defense</span>
                                            <span class="text-[9px] text-gray-450 font-semibold" x-text="def.date">Date info</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
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
                            <p class="text-xs text-gray-450 mt-1">Showing notifications for: <span class="font-bold text-gray-850">Research Facilitator</span></p>
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

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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

    <!-- Approval Review Modal Mockup -->
    <div x-show="selectedApproval" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="selectedApproval = null" class="bg-white rounded-3xl w-full max-w-lg p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Review Pending Submission</h3>
                    <span class="text-[10px] text-gray-400" x-text="`Submitted: ${selectedApproval?.date}`">Submitted date</span>
                </div>
                <button @click="selectedApproval = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            
            <div class="space-y-3 text-xs leading-relaxed">
                <div>
                    <span class="text-gray-400 block font-semibold">Research Proposal Title</span>
                    <span class="text-gray-800 font-bold block mt-1 text-sm" x-text="selectedApproval?.title">Project Title</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-400 block font-semibold">Student Researcher</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedApproval?.student">Student Name</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Faculty Adviser</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedApproval?.adviser">Adviser Name</span>
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 space-y-2">
                    <span class="font-bold text-gray-700 block text-[10px] uppercase">Oversight Checklist status</span>
                    <ul class="space-y-1.5 text-gray-500 text-[10px] font-semibold">
                        <li class="flex items-center gap-1.5 text-emerald-600">✓ Adviser endorsement signed</li>
                        <li class="flex items-center gap-1.5 text-emerald-600">✓ Document format check passed</li>
                        <li class="flex items-center gap-1.5 text-amber-500">⚠ Facilitator final endorsement pending approval</li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-gray-100">
            <div class="pt-2 flex justify-end gap-3">
                <button @click="selectedApproval = null" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
                <button @click="alert(`Approved: ${selectedApproval?.title}`); selectedApproval.status = 'Approved'; selectedApproval = null" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                    Approve Submission
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
