@extends('layouts.blank')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<div class="min-h-screen flex font-sans bg-[#f4f7f6]" x-data="{ 
    activeTab: 'dashboard', 
    showJoinClassModal: false,
    showDefenseDetailsModal: false,
    selectedDefense: null,
    defenses: [
        {
            type: 'Proposal Defense',
            status: 'scheduled',
            title: 'AI-Powered Traffic Management System',
            student: 'Juan Dela Cruz',
            date: 'May 25, 2026',
            time: '9:00 AM - 11:00 AM',
            venue: 'Room 405, Research Building',
            adviser: 'Dr. Maria Santos',
            panel: ['Dr. Maria Santos', 'Dr. John Reyes', 'Prof. Anna Garcia']
        },
        {
            type: 'Final Defense',
            status: 'scheduled',
            title: 'Blockchain-Based Voting System',
            student: 'Maria Clara',
            date: 'May 28, 2026',
            time: '2:00 PM - 4:00 PM',
            venue: 'Conference Room A',
            adviser: 'Dr. Pedro Cruz',
            panel: ['Dr. Pedro Cruz', 'Dr. Sofia Martinez', 'Prof. Carlos Lopez']
        }
    ]
}">
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-[#0e5c3a] text-white flex flex-col justify-between z-20 border-r border-white/5">
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
                    
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm text-white truncate">Maria Santos</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Student Researcher</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>
                
                <a href="#" 
                   @click.prevent="activeTab = 'dashboard'"
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
                
                <a href="#" 
                   @click.prevent="activeTab = 'classes'"
                   :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg"></i>
                        <span>My Classes</span>
                    </div>
                    <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'research'"
                   :class="activeTab === 'research' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book-open text-lg"></i>
                        <span>My Research</span>
                    </div>
                    <span x-show="activeTab === 'research'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'proposal'"
                   :class="activeTab === 'proposal' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Research Proposal</span>
                    </div>
                    <span x-show="activeTab === 'proposal'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'progress'"
                   :class="activeTab === 'progress' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg"></i>
                        <span>Research Progress</span>
                    </div>
                    <span x-show="activeTab === 'progress'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'consultation'"
                   :class="activeTab === 'consultation' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chat-teardrop text-lg"></i>
                        <span>Consultation Records</span>
                    </div>
                    <span x-show="activeTab === 'consultation'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'revisions'"
                   :class="activeTab === 'revisions' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-note-pencil text-lg"></i>
                        <span>Revision Tracker</span>
                    </div>
                    <span x-show="activeTab === 'revisions'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'defense'"
                   :class="activeTab === 'defense' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>My Defense Schedule</span>
                    </div>
                    <span x-show="activeTab === 'defense'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'evaluations'"
                   :class="activeTab === 'evaluations' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-exam text-lg"></i>
                        <span>Evaluation Results</span>
                    </div>
                    <span x-show="activeTab === 'evaluations'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <a href="#" 
                   @click.prevent="activeTab = 'repository'"
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
            </div>

            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Research Forms</span>
                
                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg"></i>
                        <span>Official Forms</span>
                    </div>
                    <i class="ph ph-caret-right text-xs text-white/60"></i>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-6 pb-6 mt-auto">
            <div class="pt-4 border-t border-white/10 space-y-1">
                <a href="#" 
                   @click.prevent="activeTab = 'notifications'"
                   :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all duration-200">
                    <i class="ph ph-bell text-lg"></i>
                    <span>Notifications</span>
                </a>
                <a href="#" 
                   @click.prevent="activeTab = 'settings'"
                   :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold text-[13px] shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px]'"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all duration-200">
                    <i class="ph ph-gear text-lg"></i>
                    <span>Settings</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200 text-left">
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
    <div class="flex-1 flex flex-col min-h-screen pl-64">
        <!-- Top Nav Header -->
        <header class="bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-between flex-shrink-0">
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
                <button class="relative w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="ph ph-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        S
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">Maria</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Research Portal</span>
                    </div>
                </div>
            </div>
        </header>
        <!-- Main Body -->
        <main class="flex-1 p-8">
            <!-- TAB: Dashboard -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                <!-- Welcome title row -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Welcome Back, Student!</h1>
                        <p class="text-xs text-gray-450 mt-1">Here's your research journey overview</p>
                    </div>
                    <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200 hover:scale-[1.02]">
                        <i class="ph ph-upload-simple text-base"></i>
                        <span>Submit Document</span>
                    </button>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Research Progress Card (Solid Green) -->
                    <div class="bg-[#0e5c3a] text-white rounded-3xl p-5 shadow-sm shadow-[#0e5c3a]/5 flex flex-col justify-between min-h-[120px]">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-white/80">Research Progress</span>
                            <i class="ph ph-chart-line-up text-lg text-white/80"></i>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-bold">42%</span>
                            <span class="text-[10px] text-white/70 block mt-1">5 of 12 milestones completed</span>
                        </div>
                    </div>

                    <!-- Urgent Tasks Card (White, Red Left Border) -->
                    <div class="bg-white border-l-4 border-red-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex flex-col justify-between min-h-[120px]">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Urgent Tasks</span>
                            <i class="ph ph-warning-circle text-lg text-red-500"></i>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-bold text-gray-850">3</span>
                            <span class="text-[10px] text-red-550 font-bold block mt-1">2 days until deadline</span>
                        </div>
                    </div>

                    <!-- Next Consultation Card (White, Blue Left Border) -->
                    <div class="bg-white border-l-4 border-blue-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex flex-col justify-between min-h-[120px]">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Next Consultation</span>
                            <i class="ph ph-calendar text-lg text-blue-500"></i>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-bold text-gray-855">Today</span>
                            <span class="text-[10px] text-gray-500 block mt-1">2:00 PM with Dr. Santos</span>
                        </div>
                    </div>

                    <!-- Documents Card (White, Purple Left Border) -->
                    <div class="bg-white border-l-4 border-purple-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex flex-col justify-between min-h-[120px]">
                        <div class="flex justify-between items-start">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Documents</span>
                            <i class="ph ph-file-text text-lg text-purple-500"></i>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-bold text-gray-850">8</span>
                            <span class="text-[10px] text-purple-500 font-bold block mt-1">2 pending review</span>
                        </div>
                    </div>
                </div>

                <!-- Main Two-Column Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column (Span 2) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- My Research Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm shadow-slate-100/50 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-bold font-heading text-gray-800 flex items-center gap-2">
                                    <i class="ph ph-book-open text-lg text-[#0e5c3a]"></i>
                                    <span>My Research</span>
                                </h3>
                                <i @click="activeTab = 'research'" class="ph ph-arrow-square-out text-lg text-gray-400 hover:text-gray-650 cursor-pointer"></i>
                            </div>

                            <!-- Research Details Box -->
                            <div class="bg-[#0e5c3a]/5 rounded-2xl p-5 border border-[#0e5c3a]/10">
                                <h4 class="font-bold font-heading text-[#0e5c3a] text-lg mb-2 leading-snug">
                                    Machine Learning Applications in Agricultural Pest Detection
                                </h4>
                                <div class="flex items-center gap-2 mb-6">
                                    <span class="text-[10px] font-bold tracking-wider text-gray-500 uppercase">ID: RES-2026-001</span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#0e5c3a] text-white text-[9px] font-bold uppercase">Active</span>
                                </div>

                                <!-- Progress Section -->
                                <div class="space-y-2 mb-6">
                                    <div class="flex justify-between text-xs font-bold text-gray-600">
                                        <span>Research Journey Progress</span>
                                        <span class="text-[#0e5c3a]">42%</span>
                                    </div>
                                    <div class="w-full h-3 bg-gray-200/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#0e5c3a] rounded-full" style="width: 42%;"></div>
                                    </div>
                                </div>

                                <!-- Grid info -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-white rounded-xl p-3 border border-gray-100/80">
                                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Adviser</span>
                                        <span class="text-xs font-semibold text-gray-700 block mt-1">Dr. Maria Santos</span>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 border border-gray-100/80">
                                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Final Defense</span>
                                        <span class="text-xs font-semibold text-gray-700 block mt-1">July 15, 2026</span>
                                    </div>
                                </div>

                                <!-- View details button -->
                                <button @click="activeTab = 'research'" class="w-full py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200">
                                    View Full Research Details
                                </button>
                            </div>
                        </div>

                        <!-- Current Milestone Section -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm shadow-slate-100/50 p-6">
                            <h3 class="font-bold font-heading text-gray-800 mb-6 flex items-center gap-2">
                                <i class="ph ph-path text-lg text-[#0e5c3a]"></i>
                                <span>Current Milestone</span>
                            </h3>

                            <div class="space-y-4">
                                <!-- Milestone Item 1 (Completed) -->
                                <div class="flex items-start gap-4 p-4 bg-[#0e5c3a]/5 border border-[#0e5c3a]/15 rounded-2xl">
                                    <span class="w-8 h-8 rounded-full bg-[#0e5c3a]/10 border border-[#0e5c3a]/20 flex items-center justify-center text-[#0e5c3a] flex-shrink-0 mt-0.5">
                                        <i class="ph ph-check text-base font-bold"></i>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-850 text-sm">Proposal Defense</h4>
                                        <p class="text-xs text-gray-500 mt-1">Successfully completed on May 10, 2026</p>
                                        <span class="inline-flex items-center gap-1.5 text-[10px] text-[#0e5c3a] font-bold mt-2">
                                            <i class="ph ph-checks text-sm"></i>
                                            <span>Panel recommendation: Proceed to next phase</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Milestone Item 2 (In Progress) -->
                                <div class="flex items-start gap-4 p-4 bg-[#eebc3f]/5 border border-[#eebc3f]/20 rounded-2xl">
                                    <span class="w-8 h-8 rounded-full bg-[#eebc3f]/10 border border-[#eebc3f]/30 flex items-center justify-center text-[#eebc3f] flex-shrink-0 mt-0.5">
                                        <i class="ph ph-circle-dashed text-base font-bold animate-spin-slow"></i>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-850 text-sm">Data Gathering & Analysis</h4>
                                        <p class="text-xs text-gray-500 mt-1">In progress...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-8">
                        <!-- Action Required Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm shadow-slate-100/50 p-6">
                            <h3 class="font-bold font-heading text-gray-800 mb-6 flex items-center gap-2">
                                <i class="ph ph-warning text-lg text-red-500"></i>
                                <span>Action Required</span>
                            </h3>

                            <div class="space-y-3">
                                <!-- Action Item 1 -->
                                <div class="p-3.5 bg-red-50/50 border border-red-100 rounded-2xl flex items-start gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-red-100 text-red-650 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                        <i class="ph ph-file-arrow-up text-sm"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-xs text-gray-800 block">Submit Chapter 3</span>
                                        <span class="text-[10px] text-red-550 font-bold mt-1 block">Due in 2 days - May 20</span>
                                    </div>
                                </div>

                                <!-- Action Item 2 -->
                                <div class="p-3.5 bg-amber-50/50 border border-amber-100 rounded-2xl flex items-start gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-655 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                        <i class="ph ph-note-pencil text-sm"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-xs text-gray-800 block">Revise Abstract</span>
                                        <span class="text-[10px] text-amber-600 font-bold mt-1 block">Feedback from Dr. Santos</span>
                                    </div>
                                </div>

                                <!-- Action Item 3 -->
                                <div class="p-3.5 bg-blue-50/50 border border-blue-100 rounded-2xl flex items-start gap-3">
                                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-650 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                                        <i class="ph ph-calendar-check text-sm"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold text-xs text-gray-800 block">Confirm Consultation</span>
                                        <span class="text-[10px] text-blue-550 font-bold mt-1 block">May 22, 10:00 AM</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Consultation Card -->
                        <div class="bg-gradient-to-br from-[#0e5c3a] to-blue-700 text-white rounded-[2rem] shadow-lg shadow-blue-500/10 p-6 flex flex-col justify-between min-h-[220px]">
                            <div class="space-y-6">
                                <h3 class="font-bold text-white/95 flex items-center gap-2">
                                    <i class="ph ph-calendar text-lg text-[#eebc3f]"></i>
                                    <span>Today's Consultation</span>
                                </h3>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-[#eebc3f] font-bold text-xs">
                                            S
                                        </div>
                                        <div class="flex flex-col leading-none">
                                            <span class="font-bold text-sm text-white">Dr. Maria Santos</span>
                                            <span class="text-[10px] text-[#eebc3f] mt-0.5">Research Adviser</span>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 pl-1 text-xs text-white/90">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-clock text-sm text-[#eebc3f]"></i>
                                            <span>2:00 PM - 3:00 PM</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-map-pin text-sm text-[#eebc3f]"></i>
                                            <span>Room 304, Research Building</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="w-full py-3.5 bg-white text-[#0e5c3a] hover:bg-[#eebc3f] hover:text-[#0e5c3a] text-xs font-bold rounded-xl transition-all duration-200 mt-6 shadow-sm">
                                View All Consultations
                            </button>
                        </div>

                        <!-- Recent Updates Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm shadow-slate-100/50 p-6">
                            <h3 class="font-bold font-heading text-gray-800 mb-6 flex items-center gap-2">
                                <i class="ph ph-activity text-lg text-[#0e5c3a]"></i>
                                <span>Recent Updates</span>
                            </h3>

                            <div class="relative pl-6 border-l border-gray-150 space-y-6 text-xs text-gray-600">
                                <!-- Timeline Item 1 -->
                                <div class="relative">
                                    <span class="absolute -left-[30px] top-1 w-2.5 h-2.5 rounded-full bg-[#0e5c3a] ring-4 ring-white"></span>
                                    <span class="font-bold text-gray-800 block">Proposal document approved</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5">By Adviser • 2 hours ago</span>
                                </div>

                                <!-- Timeline Item 2 -->
                                <div class="relative">
                                    <span class="absolute -left-[30px] top-1 w-2.5 h-2.5 rounded-full bg-blue-500 ring-4 ring-white"></span>
                                    <span class="font-bold text-gray-800 block">Consultation meeting scheduled</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5">With Dr. Santos • Yesterday</span>
                                </div>

                                <!-- Timeline Item 3 -->
                                <div class="relative">
                                    <span class="absolute -left-[30px] top-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-4 ring-white"></span>
                                    <span class="font-bold text-gray-800 block">Chapter 2 revision requested</span>
                                    <span class="text-[10px] text-gray-400 block mt-0.5">By Review Committee • 3 days ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: My Classes -->
            <div x-show="activeTab === 'classes'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">My Classes</h1>
                        <p class="text-xs text-gray-450 mt-1">View and manage your registered classes.</p>
                    </div>
                    <button @click="showJoinClassModal = true" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200 hover:scale-[1.02]">
                        <i class="ph ph-plus text-base font-bold"></i>
                        <span>Join Class</span>
                    </button>
                </div>

                <!-- Empty State Card -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm shadow-slate-100/50 p-12 flex flex-col items-center justify-center min-h-[350px]">
                    <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 mb-4 border border-gray-100">
                        <i class="ph ph-book-open text-3xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg">No Classes Yet</h3>
                    <p class="text-xs text-gray-400 mt-2 text-center max-w-sm">
                        You are not registered in any classes. Join a class to collaborate with advisers.
                    </p>
                    <button @click="showJoinClassModal = true" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl mt-6 transition-all duration-200">
                        Join Class
                    </button>
                </div>
            </div>

            <!-- TAB: My Research -->
            <div x-show="activeTab === 'research'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Research Details</h1>
                        <p class="text-xs text-gray-450 mt-1">Machine Learning Applications in Agricultural Pest Detection</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 transition-all duration-200">
                            <i class="ph ph-note-pencil text-base"></i>
                            <span>Edit</span>
                        </button>
                        <button class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 transition-all duration-200">
                            <i class="ph ph-download text-base"></i>
                            <span>Download</span>
                        </button>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column (Span 2) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Abstract Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                            <h3 class="font-bold font-heading text-gray-800 text-lg mb-4">Abstract</h3>
                            <p class="text-xs text-gray-505 leading-relaxed">
                                This research explores the application of <span class="text-[#b45309]">machine learning algorithms</span> in detecting agricultural pests through image recognition. The study aims to develop an automated system that can identify common pests affecting crops in <span class="text-[#b45309]">South Cotabato province</span>, providing farmers with <span class="text-[#b45309]">real-time pest detection capabilities</span>.
                            </p>
                        </div>

                        <!-- Keywords Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                            <h3 class="font-bold font-heading text-gray-800 text-lg mb-4">Keywords</h3>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1.5 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">Machine Learning</span>
                                <span class="px-3 py-1.5 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">Agriculture</span>
                                <span class="px-3 py-1.5 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">Pest Detection</span>
                                <span class="px-3 py-1.5 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">Image Recognition</span>
                                <span class="px-3 py-1.5 bg-[#e6f4ea] text-[#0e5c3a] text-xs font-semibold rounded-full">AI</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Span 1) -->
                    <div class="space-y-8">
                        <!-- Detail Information Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                            <h3 class="font-bold font-heading text-gray-800 text-lg mb-6">Research Information</h3>
                            <div class="space-y-5">
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Research ID</span>
                                    <span class="text-xs font-bold text-gray-800 block mt-1">RES-2026-001</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Type</span>
                                    <span class="text-xs font-bold text-gray-800 block mt-1">Undergraduate Thesis</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Program</span>
                                    <span class="text-xs font-bold text-gray-800 block mt-1">BS Computer Science</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Status</span>
                                    <span class="inline-flex px-3 py-1 rounded-full bg-[#e6f4ea] text-[#0e5c3a] text-[10px] font-bold uppercase mt-1">In Progress</span>
                                </div>
                            </div>
                        </div>

                        <!-- Team Card -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                            <h3 class="font-bold font-heading text-gray-800 text-lg mb-6">Team</h3>
                            <div class="space-y-5">
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Researcher</span>
                                    <span class="text-xs font-bold text-gray-800 block mt-1">Your Name</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Adviser</span>
                                    <span class="text-xs font-bold text-gray-800 block mt-1">Dr. Maria Santos</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Research Proposal -->
            <div x-show="activeTab === 'proposal'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Proposal Management</h1>
                        <p class="text-xs text-gray-450 mt-1">Manage research proposals and approvals</p>
                    </div>
                </div>

                <!-- Stats Grid Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white border-l-4 border-emerald-500 rounded-3xl p-6 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Approved</span>
                            <span class="text-3xl font-bold text-gray-800 block">1</span>
                        </div>
                        <i class="ph ph-check-circle text-4xl text-emerald-500"></i>
                    </div>
                    <!-- Pending -->
                    <div class="bg-white border-l-4 border-amber-500 rounded-3xl p-6 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Pending</span>
                            <span class="text-3xl font-bold text-gray-800 block">0</span>
                        </div>
                        <i class="ph ph-clock text-4xl text-amber-500"></i>
                    </div>
                    <!-- Revisions -->
                    <div class="bg-white border-l-4 border-red-500 rounded-3xl p-6 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-800 block">0</span>
                        </div>
                        <i class="ph ph-x-circle text-4xl text-red-500"></i>
                    </div>
                    <!-- Total Proposals -->
                    <div class="bg-white border-l-4 border-blue-500 rounded-3xl p-6 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Total Proposals</span>
                            <span class="text-3xl font-bold text-gray-800 block">1</span>
                        </div>
                        <i class="ph ph-file-text text-4xl text-blue-500"></i>
                    </div>
                </div>

                <!-- Proposals List -->
                <div>
                    <h3 class="font-bold font-heading text-gray-800 text-sm mb-4">Research Proposal</h3>
                    <div class="bg-[#0e5c3a]/5 border border-[#0e5c3a]/15 rounded-[2rem] p-6">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                            <div>
                                <h4 class="font-bold font-heading text-gray-800 text-base mb-1 leading-snug">
                                    Machine Learning Applications in Agricultural Pest Detection
                                </h4>
                                <span class="text-xs text-gray-455 mt-1 block">Proposal ID: PROP-2026-001</span>
                                <span class="text-xs text-gray-455 mt-0.5 block">Submitted: March 5, 2026</span>
                            </div>
                            <div>
                                <span class="inline-flex px-4 py-1.5 rounded-full bg-[#10b981] text-white text-[10px] font-bold uppercase tracking-wider">Approved</span>
                            </div>
                        </div>

                        <!-- Reviewed Row -->
                        <div class="flex gap-16 mt-6">
                            <div>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Reviewed by</span>
                                <span class="text-xs font-bold text-gray-700 block mt-1">Dr. Maria Santos</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Approval Date</span>
                                <span class="text-xs font-bold text-gray-700 block mt-1">March 10, 2026</span>
                            </div>
                        </div>

                        <!-- Action Buttons Row -->
                        <div class="flex items-center gap-3 mt-6">
                            <button class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200">
                                View Proposal
                            </button>
                            <button class="px-5 py-2.5 bg-white border border-gray-200 text-gray-650 hover:text-gray-800 hover:bg-gray-50 text-xs font-bold rounded-xl transition-all duration-200">
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Research Progress -->
            <div x-show="activeTab === 'progress'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Research Lifecycle Tracker</h1>
                        <p class="text-xs text-gray-450 mt-1">Track your research progress through each milestone</p>
                    </div>
                </div>

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-bold text-gray-800 text-base">Overall Progress</h3>
                            <p class="text-xs text-gray-400 mt-1">Machine Learning Applications in Agricultural Pest Detection</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-[#0e5c3a] block">42%</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Complete</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-3 bg-gray-100 rounded-full mt-5 overflow-hidden">
                        <div class="h-full bg-[#0e5c3a] rounded-full" style="width: 42%;"></div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 text-center mt-6 pt-5 border-t border-gray-100">
                        <div>
                            <span class="text-2xl font-bold text-[#0e5c3a] block">6</span>
                            <span class="text-[10px] text-gray-400 font-semibold block mt-0.5">Completed</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold text-[#eebc3f] block">2</span>
                            <span class="text-[10px] text-gray-400 font-semibold block mt-0.5">In Progress</span>
                        </div>
                        <div>
                            <span class="text-2xl font-bold text-gray-400 block">4</span>
                            <span class="text-[10px] text-gray-400 font-semibold block mt-0.5">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Research Milestones Section -->
                <div>
                    <h3 class="font-bold font-heading text-gray-800 text-lg mb-6">Research Milestones</h3>

                    <!-- Timeline Container -->
                    <div class="relative ml-4 pl-8 border-l-2 border-gray-200/80 space-y-6">
                        
                        <!-- Milestone 1 (Completed) -->
                        <div class="relative">
                            <!-- Icon circle on the line -->
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-emerald-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-check text-xs font-bold"></i>
                            </div>
                            <!-- Card -->
                            <div class="bg-emerald-50/10 border border-emerald-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Research Title Presentation</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Feb 15, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider">Completed</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-emerald-100/50 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>All requirements met and approved</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 2 (Completed) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-emerald-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-check text-xs font-bold"></i>
                            </div>
                            <div class="bg-emerald-50/10 border border-emerald-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Proposal Approval</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Mar 10, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider">Completed</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-emerald-100/50 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>All requirements met and approved</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 3 (Completed) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-emerald-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-check text-xs font-bold"></i>
                            </div>
                            <div class="bg-emerald-50/10 border border-emerald-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Adviser Endorsement</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Mar 20, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider">Completed</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-emerald-100/50 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>All requirements met and approved</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 4 (Completed) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-emerald-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-check text-xs font-bold"></i>
                            </div>
                            <div class="bg-emerald-50/10 border border-emerald-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Instrument Validation</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Apr 5, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider">Completed</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-emerald-100/50 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>All requirements met and approved</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 5 (In Progress) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-amber-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-clock text-xs font-bold"></i>
                            </div>
                            <div class="bg-amber-50/10 border border-amber-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Data Gathering</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>In Progress</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-amber-500 text-white text-[9px] font-bold uppercase tracking-wider">In Progress</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-amber-100 text-[11px] text-amber-700">
                                    Currently working on this milestone
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 6 (Completed) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-emerald-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-check text-xs font-bold"></i>
                            </div>
                            <div class="bg-emerald-50/10 border border-emerald-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Proposal Defense</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>May 10, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider">Completed</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-emerald-100/50 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <span>✓</span>
                                    <span>All requirements met and approved</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 7 (In Progress) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-amber-500 border-4 border-[#f4f7f6] flex items-center justify-center text-white shadow-sm">
                                <i class="ph ph-clock text-xs font-bold"></i>
                            </div>
                            <div class="bg-amber-50/10 border border-amber-200 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm">Revisions</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>May 18, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-amber-500 text-white text-[9px] font-bold uppercase tracking-wider">In Progress</span>
                                </div>
                                <div class="mt-4 pt-3 border-t border-amber-100 text-[11px] text-amber-700">
                                    Currently working on this milestone
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 8 (Pending) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-[#f4f7f6] border-4 border-[#f4f7f6] flex items-center justify-center shadow-sm">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white"></div>
                            </div>
                            <div class="bg-white border border-gray-150 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Final Defense</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Jul 15, 2026</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[9px] font-bold uppercase tracking-wider">Pending</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 9 (Pending) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-[#f4f7f6] border-4 border-[#f4f7f6] flex items-center justify-center shadow-sm">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white"></div>
                            </div>
                            <div class="bg-white border border-gray-150 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Technical Editing</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Not Started</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[9px] font-bold uppercase tracking-wider">Pending</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 10 (Pending) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-[#f4f7f6] border-4 border-[#f4f7f6] flex items-center justify-center shadow-sm">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white"></div>
                            </div>
                            <div class="bg-white border border-gray-150 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Language Editing</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Not Started</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[9px] font-bold uppercase tracking-wider">Pending</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 11 (Pending) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-[#f4f7f6] border-4 border-[#f4f7f6] flex items-center justify-center shadow-sm">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white"></div>
                            </div>
                            <div class="bg-white border border-gray-150 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Final Manuscript Approval</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Not Started</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[9px] font-bold uppercase tracking-wider">Pending</span>
                                </div>
                            </div>
                        </div>

                        <!-- Milestone 12 (Pending) -->
                        <div class="relative">
                            <div class="absolute -left-[49px] top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-[#f4f7f6] border-4 border-[#f4f7f6] flex items-center justify-center shadow-sm">
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 bg-white"></div>
                            </div>
                            <div class="bg-white border border-gray-150 rounded-2xl p-5">
                                <div class="flex justify-between items-start gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm">Certificate of Authentic Authorship</h4>
                                        <p class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-1">
                                            <i class="ph ph-calendar"></i>
                                            <span>Not Started</span>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-[9px] font-bold uppercase tracking-wider">Pending</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex gap-4 mt-8">
                    <button class="px-6 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                        Update Progress
                    </button>
                    <button class="px-6 py-3 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                        Download Timeline
                    </button>
                </div>
            </div>

            <!-- TAB: Consultation Records -->
            <div x-show="activeTab === 'consultation'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Consultation Management</h1>
                        <p class="text-xs text-gray-450 mt-1">Schedule and manage adviser consultations</p>
                    </div>
                    <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200 hover:scale-[1.02]">
                        <i class="ph ph-plus text-base font-bold"></i>
                        <span>Book Consultation</span>
                    </button>
                </div>

                <!-- Upcoming Consultations Card -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-850 text-base mb-6">Upcoming Consultations</h3>
                    
                    <div class="space-y-4">
                        <!-- Consultation 1 -->
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-2xl bg-white hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                                    <i class="ph ph-map-pin"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">Dr. Maria Santos</h4>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Today, 2:00 PM</p>
                                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">In-Person - Room 304</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all">
                                View Details
                            </button>
                        </div>

                        <!-- Consultation 2 -->
                        <div class="flex items-center justify-between p-4 border border-gray-100 rounded-2xl bg-white hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                                    <i class="ph ph-video-camera"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">Dr. Juan Dela Cruz</h4>
                                    <p class="text-[11px] text-gray-400 mt-0.5">May 22, 10:00 AM</p>
                                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Online - Zoom</p>
                                </div>
                            </div>
                            <button class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Revision Tracker -->
            <div x-show="activeTab === 'revisions'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Research Revision Tracker</h1>
                        <p class="text-xs text-gray-455 mt-1">Track and manage document revisions</p>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Pending Revisions -->
                    <div class="bg-white border-l-4 border-amber-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Pending Revisions</span>
                            <span class="text-3xl font-bold text-gray-850 mt-1 block">5</span>
                        </div>
                        <span class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white border-l-4 border-emerald-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Completed</span>
                            <span class="text-3xl font-bold text-gray-855 mt-1 block">12</span>
                        </div>
                        <span class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Overdue -->
                    <div class="bg-white border-l-4 border-red-500 rounded-3xl p-5 shadow-sm shadow-slate-100 flex items-center justify-between min-h-[100px]">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Overdue</span>
                            <span class="text-3xl font-bold text-gray-850 mt-1 block">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-full bg-red-50 text-red-500 flex items-center justify-center text-xl">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Revision History -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-855 text-base mb-6">Revision History</h3>

                    <div class="space-y-4">
                        <!-- Revision 1 -->
                        <div class="border-l-4 border-amber-500 bg-amber-50/5 border border-gray-100 rounded-r-2xl p-4 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-850 text-sm">v3.2</h4>
                                <p class="text-xs text-gray-500 mt-1">Updated methodology section</p>
                                <span class="text-[10px] text-gray-400 block mt-2">May 18, 2026</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold">In Progress</span>
                        </div>

                        <!-- Revision 2 -->
                        <div class="border-l-4 border-emerald-500 bg-emerald-50/5 border border-gray-100 rounded-r-2xl p-4 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-850 text-sm">v3.1</h4>
                                <p class="text-xs text-gray-500 mt-1">Added more recent references</p>
                                <span class="text-[10px] text-gray-400 block mt-2">May 15, 2026</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Completed</span>
                        </div>

                        <!-- Revision 3 -->
                        <div class="border-l-4 border-emerald-500 bg-emerald-50/5 border border-gray-100 rounded-r-2xl p-4 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-850 text-sm">v3.0</h4>
                                <p class="text-xs text-gray-500 mt-1">Expanded literature review</p>
                                <span class="text-[10px] text-gray-400 block mt-2">May 10, 2026</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Completed</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: My Defense Schedule -->
            <div x-show="activeTab === 'defense'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">My Defense Schedule</h1>
                        <p class="text-xs text-gray-450 mt-1">View your assigned defense schedule and details</p>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Total Scheduled -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Total Scheduled</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">3</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar-check"></i>
                        </span>
                    </div>

                    <!-- This Week -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">This Week</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Pending</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">1</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Completed</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">0</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar-x"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex gap-4 items-center">
                    <span class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center text-lg">
                        <i class="ph ph-funnel"></i>
                    </span>
                    <select class="px-3 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-700 focus:outline-none focus:bg-white focus:border-gray-300">
                        <option>All Defense Types</option>
                        <option>Proposal Defense</option>
                        <option>Final Defense</option>
                    </select>
                    <select class="px-3 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-700 focus:outline-none focus:bg-white focus:border-gray-300">
                        <option>All Status</option>
                        <option>Scheduled</option>
                        <option>Pending</option>
                        <option>Completed</option>
                    </select>
                </div>

                <!-- Defense List -->
                <div class="space-y-6">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-[2rem] border-l-4 border-emerald-500 border-t border-r border-b border-gray-100 shadow-sm p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-gray-800 text-base">Proposal Defense</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Scheduled</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click.prevent="selectedDefense = defenses[0]; showDefenseDetailsModal = true" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition-colors">
                                    <i class="ph ph-eye text-base"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-750 text-sm">AI-Powered Traffic Management System</h4>
                            <p class="text-xs text-gray-400 mt-1">Student: Juan Dela Cruz</p>
                        </div>
                        
                        <!-- Columns info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-3 border-t border-b border-gray-100 text-xs text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="ph ph-calendar text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Date</span>
                                    <span class="font-semibold text-gray-700">May 25, 2026</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="ph ph-clock text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Time</span>
                                    <span class="font-semibold text-gray-700">9:00 AM - 11:00 AM</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="ph ph-map-pin text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Venue</span>
                                    <span class="font-semibold text-gray-700">Room 405, Research Building</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Members -->
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-400 font-medium">Panel Members:</span>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Dr. Maria Santos</span>
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Dr. John Reyes</span>
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Prof. Anna Garcia</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white rounded-[2rem] border-l-4 border-emerald-500 border-t border-r border-b border-gray-100 shadow-sm p-6 space-y-4">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-gray-800 text-base">Final Defense</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Scheduled</span>
                            </div>
                            <div class="flex gap-2">
                                <button @click.prevent="selectedDefense = defenses[1]; showDefenseDetailsModal = true" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition-colors">
                                    <i class="ph ph-eye text-base"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-700 text-sm">Blockchain-Based Voting System</h4>
                            <p class="text-xs text-gray-450 mt-1">Student: Maria Clara</p>
                        </div>
                        
                        <!-- Columns info -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-3 border-t border-b border-gray-100 text-xs text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="ph ph-calendar text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Date</span>
                                    <span class="font-semibold text-gray-700">May 28, 2026</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="ph ph-clock text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Time</span>
                                    <span class="font-semibold text-gray-700">2:00 PM - 4:00 PM</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="ph ph-map-pin text-emerald-600 text-base"></i>
                                <div>
                                    <span class="block text-[9px] text-gray-400 font-bold uppercase tracking-wider">Venue</span>
                                    <span class="font-semibold text-gray-700">Conference Room A</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Members -->
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-400 font-medium">Panel Members:</span>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Dr. Pedro Cruz</span>
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Dr. Sofia Martinez</span>
                                <span class="px-2.5 py-1 bg-gray-50 border border-gray-100 text-gray-600 text-[10px] font-medium rounded-full">Prof. Carlos Lopez</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Evaluation Results -->
            <div x-show="activeTab === 'evaluations'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Evaluation & Grading</h1>
                        <p class="text-xs text-gray-455 mt-1">Research defense evaluation and scoring system</p>
                    </div>
                </div>

                <!-- Green Header Card -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-[2rem] p-8 text-white shadow-lg flex justify-between items-center relative overflow-hidden">
                    <div class="space-y-4">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white/80 block">Overall Research Score</span>
                        <span class="text-5xl font-extrabold text-white block">90.0%</span>
                        <div class="inline-flex items-center gap-1 bg-white/10 px-3 py-1 rounded-full text-xs font-semibold text-white/90">
                            <span>★</span>
                            <span>Excellent Performance</span>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        <span class="text-5xl text-white/20">
                            <i class="ph ph-certificate"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold block">Proposal Defense</span>
                            <span class="text-[10px] text-white/80 block">May 10, 2026</span>
                        </div>
                    </div>
                </div>

                <!-- Scoring and Comments Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Scoring Breakdown -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                        <h3 class="font-bold text-gray-800 text-base">Scoring Breakdown</h3>
                        
                        <div class="space-y-4">
                            <!-- Score 1 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-700">
                                    <span>Research Originality</span>
                                    <span class="text-emerald-600">23/25</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 92%;"></div>
                                </div>
                            </div>

                            <!-- Score 2 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-700">
                                    <span>Methodology</span>
                                    <span class="text-emerald-600">18/20</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 90%;"></div>
                                </div>
                            </div>

                            <!-- Score 3 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-700">
                                    <span>Literature Review</span>
                                    <span class="text-emerald-600">14/15</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 93.3%;"></div>
                                </div>
                            </div>

                            <!-- Score 4 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-700">
                                    <span>Data Analysis</span>
                                    <span class="text-emerald-600">17/20</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 85%;"></div>
                                </div>
                            </div>

                            <!-- Score 5 -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-700">
                                    <span>Presentation & Defense</span>
                                    <span class="text-emerald-600">18/20</span>
                                </div>
                                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: 90%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Score row -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                            <span class="font-bold text-gray-850 text-sm">Total Score</span>
                            <span class="text-xl font-bold text-emerald-600">90/100</span>
                        </div>
                    </div>

                    <!-- Panelist Comments -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                        <h3 class="font-bold text-gray-800 text-base">Panelist Comments</h3>

                        <div class="space-y-4">
                            <!-- Comment 1 -->
                            <div class="border border-emerald-100 bg-emerald-50/10 rounded-2xl p-4 space-y-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-xs">Dr. Maria Santos</h4>
                                        <span class="text-[10px] text-gray-400 font-bold block mt-0.5">Panel Chair</span>
                                    </div>
                                    <div class="text-amber-500 text-xs flex gap-0.5">
                                        <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Excellent research methodology and data analysis. The presentation was clear and well-structured.
                                </p>
                            </div>

                            <!-- Comment 2 -->
                            <div class="border border-blue-100 bg-blue-50/10 rounded-2xl p-4 space-y-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-xs">Dr. John Reyes</h4>
                                        <span class="text-[10px] text-gray-400 font-bold block mt-0.5">Panelist</span>
                                    </div>
                                    <div class="text-amber-500 text-xs flex gap-0.5">
                                        <span>★</span><span>★</span><span>★</span><span>★</span><span class="text-gray-300">★</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Strong theoretical foundation. Consider expanding the literature review section.
                                </p>
                            </div>

                            <!-- Comment 3 -->
                            <div class="border border-purple-100 bg-purple-50/10 rounded-2xl p-4 space-y-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-xs">Prof. Anna Garcia</h4>
                                        <span class="text-[10px] text-gray-400 font-bold block mt-0.5">Panelist</span>
                                    </div>
                                    <div class="text-amber-500 text-xs flex gap-0.5">
                                        <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 leading-relaxed">
                                    Innovative approach and practical applications. Well-defended arguments.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Recommendations -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-gray-850 text-base">Panel Recommendations</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Strengths -->
                        <div class="border border-emerald-100 bg-emerald-50/10 rounded-2xl p-4 space-y-3">
                            <h4 class="font-bold text-emerald-700 text-xs flex items-center gap-1.5">
                                <span>✓</span>
                                <span>Strengths</span>
                            </h4>
                            <ul class="space-y-1.5 text-xs text-gray-600 list-disc list-inside">
                                <li>Clear research objectives and methodology</li>
                                <li>Comprehensive data collection and analysis</li>
                                <li>Well-structured presentation</li>
                                <li>Strong defense of research findings</li>
                            </ul>
                        </div>

                        <!-- Areas for Improvement -->
                        <div class="border border-amber-100 bg-amber-50/10 rounded-2xl p-4 space-y-3">
                            <h4 class="font-bold text-amber-700 text-xs flex items-center gap-1.5">
                                <span>⚠</span>
                                <span>Areas for Improvement</span>
                            </h4>
                            <ul class="space-y-1.5 text-xs text-gray-600 list-disc list-inside">
                                <li>Expand literature review with recent studies</li>
                                <li>Include more diverse data samples</li>
                                <li>Strengthen theoretical framework</li>
                                <li>Add more visual data representations</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Footer note and Final Recommendation -->
                    <div class="pt-5 border-t border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 font-medium">Final Recommendation:</span>
                                <span class="text-xs font-extrabold text-emerald-600 uppercase tracking-wider">PASSED - Proceed to Final Defense</span>
                            </div>
                            <p class="text-xs text-gray-500">The panel recommends addressing the minor revisions before the final defense.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button class="px-6 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                        Download Evaluation Report
                    </button>
                    <button class="px-6 py-3 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all duration-200 hover:scale-[1.02]">
                        Print Certificate
                    </button>
                </div>
            </div>

            <!-- TAB: Research Repository -->
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Research Repository</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Research Repository</h1>
                            <p class="text-xs text-gray-450 mt-1">Manage, upload, and track all your research files.</p>
                        </div>
                        <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200 hover:scale-[1.02]">
                            <i class="ph ph-upload-simple text-base font-bold"></i>
                            <span>Upload Document</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Files -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Total Files</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">6</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-gray-50 text-gray-500 flex items-center justify-center text-xl">
                            <i class="ph ph-file"></i>
                        </span>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Approved</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Pending Review</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- For Evaluation -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">For Evaluation</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">1</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="ph ph-exam"></i>
                        </span>
                    </div>
                </div>

                <!-- Search/Filter Bar -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex justify-between items-center gap-4">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-sm"></i>
                        </span>
                        <input type="text" placeholder="Search documents or researcher name..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-805 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-300 transition-all">
                    </div>
                    <div class="flex gap-4 items-center">
                        <span class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center text-lg">
                            <i class="ph ph-funnel"></i>
                        </span>
                        <select class="px-3 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-700 focus:outline-none focus:bg-white focus:border-gray-300">
                            <option>All Status</option>
                            <option>Approved</option>
                            <option>Pending Review</option>
                            <option>For Evaluation</option>
                            <option>Reviewed</option>
                        </select>
                    </div>
                </div>

                <!-- Grid of Files -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- File Card 1 -->
                    <div class="bg-white border-t-4 border-red-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">PDF</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[9px] font-bold uppercase tracking-wider">Reviewed</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Chapter 1</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Chapter 1 – Introduction</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Background of the study, research objectives, and significance.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">2.4 MB • May 10, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Card 2 -->
                    <div class="bg-white border-t-4 border-red-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">PDF</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[9px] font-bold uppercase tracking-wider">Pending Review</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Chapter 2</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Chapter 2 – Literature Review</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Synthesis of related studies and theoretical framework.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">3.8 MB • May 12, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Card 3 -->
                    <div class="bg-white border-t-4 border-red-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">PDF</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[9px] font-bold uppercase tracking-wider">For Evaluation</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Chapter 3</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Chapter 3 – Methodology</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Research design, sampling, data gathering procedures.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">2.1 MB • May 15, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Card 4 -->
                    <div class="bg-white border-t-4 border-blue-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">DOCX</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-bold uppercase tracking-wider">Approved</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Appendix A</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Survey Questionnaire</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Validated questionnaire used for primary data collection.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">856 KB • Apr 20, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Card 5 -->
                    <div class="bg-white border-t-4 border-red-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">PDF</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-bold uppercase tracking-wider">Approved</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Proposal</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Research Proposal – Final Draft</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Full research proposal approved for continuation.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">1.5 MB • Mar 5, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Card 6 -->
                    <div class="bg-white border-t-4 border-blue-500 rounded-b-[2rem] rounded-t-lg border-l border-r border-b border-gray-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
                        <div class="space-y-4">
                            <div class="flex justify-between items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg flex-shrink-0">
                                    <span class="font-bold text-xs">DOCX</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[9px] font-bold uppercase tracking-wider">Pending Review</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Appendix B</span>
                                <h4 class="font-bold text-gray-800 text-sm mt-0.5">Instrument Validation Form</h4>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2">Expert validation results for research instruments.</p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-3 border-t border-gray-100">
                            <span class="text-[9px] text-gray-400 font-semibold block">620 KB • Apr 28, 2026 • Maria Santos</span>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="w-full py-2 bg-white border border-[#0e5c3a] text-[#0e5c3a] hover:bg-[#0e5c3a]/5 text-xs font-bold rounded-xl transition-all">
                                    View
                                </button>
                                <button class="w-full py-2 bg-blue-50 hover:bg-blue-100 text-blue-750 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5">
                                    <i class="ph ph-download"></i>
                                    <span>Download</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8">
                <!-- Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Notifications Center</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Notifications Center</h1>
                            <p class="text-xs text-gray-455 mt-1">Showing notifications for: <span class="font-bold text-gray-850">Student Researcher</span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200">
                                <span>✓</span>
                                <span>Mark All Read</span>
                            </button>
                            <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200">
                                <i class="ph ph-bell text-base font-bold"></i>
                                <span>3 Unread</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading">Total</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">7</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-bell"></i>
                        </span>
                    </div>

                    <!-- Unread -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading">Unread</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">3</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Defense -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading">Defense</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">1</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Documents -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading">Documents</span>
                            <span class="text-2xl font-bold text-gray-800 mt-1 block">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Badges Filter Bar -->
                <div class="flex flex-wrap gap-2.5 items-center bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <button class="px-3.5 py-1.5 bg-[#0e5c3a] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>All</span>
                        <span class="px-1.5 py-0.5 bg-white/20 text-white text-[10px] rounded-full">7</span>
                    </button>
                    <button class="px-3.5 py-1.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>Unread</span>
                        <span class="px-1.5 py-0.5 bg-gray-200/50 text-gray-500 text-[10px] rounded-full">3</span>
                    </button>
                    <button class="px-3.5 py-1.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>Approvals</span>
                        <span class="px-1.5 py-0.5 bg-gray-200/50 text-gray-500 text-[10px] rounded-full">3</span>
                    </button>
                    <button class="px-3.5 py-1.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>Defense</span>
                        <span class="px-1.5 py-0.5 bg-gray-200/50 text-gray-500 text-[10px] rounded-full">1</span>
                    </button>
                    <button class="px-3.5 py-1.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>Documents</span>
                        <span class="px-1.5 py-0.5 bg-gray-200/50 text-gray-500 text-[10px] rounded-full">2</span>
                    </button>
                    <button class="px-3.5 py-1.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all">
                        <span>System</span>
                        <span class="px-1.5 py-0.5 bg-gray-200/50 text-gray-500 text-[10px] rounded-full">1</span>
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="space-y-4">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Chapter 3 Approved</h4>
                                <span class="px-2 py-0.5 rounded bg-emerald-700 text-white text-[9px] font-bold">New</span>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-semibold">Approval</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                Dr. Reyna Garcia approved your Chapter 3 – Methodology. You may proceed to Chapter 4.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>2 hours ago</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 text-xs">
                                <a href="#" class="text-emerald-700 font-bold hover:underline">Tap to view full details</a>
                                <button class="text-gray-400 hover:text-gray-600 font-semibold">Mark as read</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-warning-circle"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Revision Required – Chapter 2</h4>
                                <span class="px-2 py-0.5 rounded bg-emerald-700 text-white text-[9px] font-bold">New</span>
                                <span class="px-2 py-0.5 rounded bg-red-50 border border-red-100 text-red-700 text-[9px] font-semibold">Revision</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                Your adviser has requested revisions on Chapter 2 – Literature Review. Please address the comments and resubmit.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>5 hours ago</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 text-xs">
                                <a href="#" class="text-emerald-700 font-bold hover:underline">Tap to view full details</a>
                                <button class="text-gray-400 hover:text-gray-600 font-semibold">Mark as read</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Final Defense Scheduled</h4>
                                <span class="px-2 py-0.5 rounded bg-emerald-700 text-white text-[9px] font-bold">New</span>
                                <span class="px-2 py-0.5 rounded bg-purple-50 border border-purple-100 text-purple-700 text-[9px] font-semibold">Defense</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                Your final oral defense has been scheduled on June 20, 2026 at 2:00 PM in Research Building, Hall A.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>1 day ago</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 text-xs">
                                <a href="#" class="text-emerald-700 font-bold hover:underline">Tap to view full details</a>
                                <button class="text-gray-400 hover:text-gray-600 font-semibold">Mark as read</button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-yellow-50 text-amber-500 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-star"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Evaluation Result Posted</h4>
                                <span class="px-2 py-0.5 rounded bg-teal-50 border border-teal-100 text-teal-700 text-[9px] font-semibold">Evaluation</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                Your final oral defense received a panel evaluation score of 91.5/100. Congratulations!
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>2 days ago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Final Manuscript Approved</h4>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 border border-emerald-100 text-emerald-700 text-[9px] font-semibold">Approved</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                The College Dean has approved your final manuscript for reproduction. Please proceed to the library.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>3 days ago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-chat-teardrop font-bold"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Consultation Reminder</h4>
                                <span class="px-2 py-0.5 rounded bg-blue-50 border border-blue-100 text-blue-700 text-[9px] font-semibold">Consultation</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                You have a scheduled consultation with Dr. Garcia tomorrow, June 3, 2026 at 10:00 AM.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>4 days ago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7 -->
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 flex items-start gap-4 hover:border-gray-200 transition-all duration-200">
                        <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="ph ph-alarm"></i>
                        </div>
                        <div class="flex-1 space-y-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-bold text-gray-800 text-sm">Submission Deadline Alert</h4>
                                <span class="px-2 py-0.5 rounded bg-orange-50 border border-orange-100 text-orange-700 text-[9px] font-semibold">Deadline</span>
                            </div>
                            <p class="text-xs text-gray-550 leading-relaxed">
                                Chapter 4 submission deadline is on May 30, 2026. Ensure all requirements are complete.
                            </p>
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                <i class="ph ph-clock"></i>
                                <span>5 days ago</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Account Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 pb-24">
                <!-- Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Settings</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Account Settings</h1>
                            <p class="text-xs text-gray-455 mt-1">Manage your profile, security, and preferences.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200">
                                <span>Reset</span>
                            </button>
                            <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200">
                                <i class="ph ph-floppy-disk text-base font-bold"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Green Banner Card -->
                <div class="bg-[#0e5c3a] rounded-[2rem] p-6 text-white shadow-lg flex items-center gap-6 relative overflow-hidden">
                    <div class="relative w-20 h-20 rounded-2xl bg-amber-500 flex items-center justify-center font-bold text-white text-4xl shadow-md flex-shrink-0">
                        <span>M</span>
                        <button class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full bg-white text-gray-700 flex items-center justify-center text-xs shadow border border-gray-100 hover:bg-gray-50">
                            <i class="ph ph-camera"></i>
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-amber-400 block">Student Researcher</span>
                        <h3 class="text-xl font-bold text-white">Maria Santos</h3>
                        <p class="text-xs text-white/80">College of Information Technology</p>
                        <div class="flex items-center gap-2 pt-1.5">
                            <span class="px-2.5 py-0.5 rounded bg-amber-400 text-[#0e5c3a] text-[9px] font-extrabold">STU-2024-0042</span>
                            <span class="px-2.5 py-0.5 rounded border border-white/20 bg-white/5 text-white text-[9px] font-semibold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Active Account</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left Column (Profile, Details, Role Info) -->
                    <div class="lg:col-span-7 space-y-8">
                        <!-- Profile Information -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-user text-emerald-600"></i>
                                <span>Profile Information</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">First Name</label>
                                    <input type="text" value="Maria" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Last Name</label>
                                    <input type="text" value="Santos" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Email Address</label>
                                <input type="email" value="maria.santos@ndmu.edu.ph" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 cursor-not-allowed" disabled>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Phone Number</label>
                                    <input type="text" value="+63 912 345 6789" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Department / College</label>
                                    <input type="text" value="College of Information Technology" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Home Address</label>
                                <input type="text" value="Koronadal City, South Cotabato" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                            </div>
                        </div>

                        <!-- Account Details -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-identification-card text-emerald-600"></i>
                                <span>Account Details</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Username</label>
                                    <input type="text" value="maria.santos" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Employee / Student ID</label>
                                    <input type="text" value="STU-2024-0042" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 cursor-not-allowed" disabled>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Date of Birth</label>
                                    <input type="text" value="15/06/2003" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Date Registered</label>
                                    <input type="text" value="August 12, 2024" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-400 cursor-not-allowed" disabled>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Bio / About</label>
                                <textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">Undergraduate researcher specializing in machine learning and educational technology.</textarea>
                            </div>
                        </div>

                        <!-- Role & Portal Access -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-shield-check text-emerald-600"></i>
                                <span>Role & Portal Access</span>
                            </h3>

                            <div class="border border-gray-100 rounded-2xl overflow-hidden divide-y divide-gray-100 text-xs">
                                <div class="flex justify-between items-center p-3">
                                    <span class="text-gray-400 font-medium">Current Role</span>
                                    <span class="font-semibold text-gray-850">Student Researcher</span>
                                </div>
                                <div class="flex justify-between items-center p-3">
                                    <span class="text-gray-400 font-medium">Portal Type</span>
                                    <span class="font-semibold text-gray-850">Research Portal</span>
                                </div>
                                <div class="flex justify-between items-center p-3">
                                    <span class="text-gray-400 font-medium">Access Level</span>
                                    <span class="font-semibold text-gray-850">Standard Research Access</span>
                                </div>
                                <div class="flex justify-between items-center p-3">
                                    <span class="text-gray-400 font-medium">Account Status</span>
                                    <span class="font-semibold text-emerald-600 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Active</span>
                                    </span>
                                </div>
                            </div>
                            <span class="text-[9px] text-gray-400 block leading-normal mt-2">
                                To request a role change, contact the System Administrator.
                            </span>
                        </div>
                    </div>

                    <!-- Right Column (Preferences, Security, Theme) -->
                    <div class="lg:col-span-5 space-y-8">
                        <!-- Notification Preferences -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-bell-ringing text-emerald-600"></i>
                                <span>Notification Preferences</span>
                            </h3>

                            <div class="space-y-4 divide-y divide-gray-50">
                                <!-- Option 1 -->
                                <div class="flex justify-between items-center pt-3 first:pt-0">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">Email Notifications</span>
                                        <span class="text-[10px] text-gray-400 block">Receive updates via NDMU email</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 2 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">SMS Notifications</span>
                                        <span class="text-[10px] text-gray-400 block">Get text alerts on your phone</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 3 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">Defense Schedule Reminders</span>
                                        <span class="text-[10px] text-gray-400 block">48 hours before defense events</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 4 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">Chapter Review Updates</span>
                                        <span class="text-[10px] text-gray-400 block">When adviser approves or requests revision</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 5 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">Consultation Reminders</span>
                                        <span class="text-[10px] text-gray-400 block">Upcoming consultation appointments</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 6 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">System Announcements</span>
                                        <span class="text-[10px] text-gray-400 block">University-wide research notices</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>

                                <!-- Option 7 -->
                                <div class="flex justify-between items-center pt-3">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-gray-700 block">Security Alerts</span>
                                        <span class="text-[10px] text-gray-400 block">Login attempts and account changes</span>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" checked class="sr-only peer">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-key text-emerald-600"></i>
                                <span>Security Settings</span>
                            </h3>

                            <div class="space-y-4">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Change Password</span>
                                <div class="space-y-3">
                                    <input type="password" placeholder="Enter current password" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-300">
                                    <input type="password" placeholder="Minimum 8 characters" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-300">
                                    <input type="password" placeholder="Repeat new password" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-300">
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="checkbox" id="show-passwords" class="w-4 h-4 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                    <label for="show-passwords" class="select-none cursor-pointer">Show passwords</label>
                                </div>
                                <button class="w-full py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all">
                                    Update Password
                                </button>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center text-xs">
                                <div class="space-y-0.5">
                                    <span class="font-bold text-gray-700 block">Two-Factor Authentication</span>
                                    <span class="text-[9px] text-gray-400 block">Add an extra layer of security</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#0e5c3a]"></div>
                                </label>
                            </div>

                            <!-- Recent Login Activity -->
                            <div class="space-y-3 pt-4 border-t border-gray-100">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Recent Login Activity</span>
                                
                                <div class="space-y-3">
                                    <!-- Log 1 -->
                                    <div class="flex justify-between items-start text-xs">
                                        <div class="flex gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center text-base">
                                                <i class="ph ph-desktop"></i>
                                            </span>
                                            <div>
                                                <span class="font-bold text-gray-750 block">Chrome · Windows 11</span>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">Koronadal City · Today, 8:34 AM</span>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[9px] font-extrabold uppercase">Current</span>
                                    </div>

                                    <!-- Log 2 -->
                                    <div class="flex justify-between items-start text-xs">
                                        <div class="flex gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center text-base">
                                                <i class="ph ph-device-mobile"></i>
                                            </span>
                                            <div>
                                                <span class="font-bold text-gray-750 block">Safari · iPhone 14</span>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">Koronadal City · Yesterday, 6:12 PM</span>
                                            </div>
                                        </div>
                                        <button class="text-red-500 hover:text-red-700 font-bold text-[10px]">Revoke</button>
                                    </div>

                                    <!-- Log 3 -->
                                    <div class="flex justify-between items-start text-xs">
                                        <div class="flex gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center text-base">
                                                <i class="ph ph-desktop"></i>
                                            </span>
                                            <div>
                                                <span class="font-bold text-gray-750 block">Chrome · Windows 11</span>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">Koronadal City · May 28, 2026, 9:01 AM</span>
                                            </div>
                                        </div>
                                        <button class="text-red-500 hover:text-red-700 font-bold text-[10px]">Revoke</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appearance & System -->
                        <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-6">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                <i class="ph ph-palette text-emerald-600"></i>
                                <span>Appearance & System</span>
                            </h3>

                            <div class="space-y-4">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block">Color Theme</span>
                                <div class="grid grid-cols-3 gap-3">
                                    <button class="py-2.5 bg-emerald-50 border border-emerald-500 text-emerald-700 text-xs font-bold rounded-xl flex flex-col items-center gap-1">
                                        <i class="ph ph-sun text-base"></i>
                                        <span>Light</span>
                                    </button>
                                    <button class="py-2.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-bold rounded-xl flex flex-col items-center gap-1">
                                        <i class="ph ph-moon text-base"></i>
                                        <span>Dark</span>
                                    </button>
                                    <button class="py-2.5 bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600 text-xs font-bold rounded-xl flex flex-col items-center gap-1">
                                        <i class="ph ph-desktop text-base"></i>
                                        <span>System</span>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Language</label>
                                    <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                        <option>English (US)</option>
                                        <option>Filipino</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Timezone</label>
                                    <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                        <option>Asia/Manila (UTC+8)</option>
                                        <option>UTC (GMT+0)</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Date Format</label>
                                    <select class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-gray-300">
                                        <option>MM/DD/YYYY</option>
                                        <option>DD/MM/YYYY</option>
                                        <option>YYYY-MM-DD</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Danger Zone -->
                            <div class="pt-4 border-t border-gray-100 space-y-3">
                                <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider block">Danger Zone</span>
                                <button class="w-full py-3 bg-white border border-red-200 hover:bg-red-50/50 text-red-600 hover:text-red-700 text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all">
                                    <i class="ph ph-warning text-base"></i>
                                    <span>Deactivate Account</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Changes Saved Bar -->
            <div x-show="activeTab === 'settings'" x-cloak class="fixed bottom-0 right-0 left-64 bg-white border-t border-gray-150 p-4 px-8 flex justify-between items-center shadow-lg z-30 transition-all">
                <span class="text-xs text-gray-500 font-medium">Changes are saved to your NDMU Research Portal account.</span>
                <div class="flex gap-3">
                    <button class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 hover:text-gray-900 hover:bg-gray-50 text-xs font-bold rounded-xl transition-all">
                        Discard
                    </button>
                    <button class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-all">
                        Save All Changes
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- Join Class Modal Dialog -->
    <div x-show="showJoinClassModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="showJoinClassModal = false"></div>

        <!-- Modal Content Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 max-w-sm w-full p-6 relative z-10 transform transition-all flex flex-col items-center text-center">
            <!-- Icon -->
            <div class="w-12 h-12 rounded-full bg-[#0e5c3a]/10 flex items-center justify-center text-[#0e5c3a] mb-4">
                <i class="ph ph-plus-circle text-2xl font-bold"></i>
            </div>
            
            <!-- Title -->
            <h3 class="text-base font-bold text-gray-800">Join Class</h3>
            <p class="text-xs text-gray-450 mt-1">
                Enter the class code provided by your adviser.
            </p>

            <!-- Form -->
            <div class="w-full text-left mt-6">
                <label class="text-[9px] font-bold text-gray-400 uppercase tracking-wider block mb-2">Class Code</label>
                <input type="text" 
                       placeholder="e.g., CLS-123-456" 
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-300 transition-all duration-200">
                <span class="text-[9px] text-gray-400 mt-2 block leading-normal">
                    Class codes are usually 6-8 characters long and contain letters and numbers.
                </span>
            </div>

            <!-- Footer Buttons -->
            <div class="flex gap-3 w-full mt-6">
                <button @click="showJoinClassModal = false" class="flex-1 py-2.5 bg-white border border-gray-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50 text-xs font-bold rounded-xl transition-all duration-200">
                    Cancel
                </button>
                <button @click="showJoinClassModal = false" class="flex-1 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200">
                    Join Class
                </button>
            </div>
        </div>
    </div>

    <!-- Defense Details Modal Dialog -->
    <div x-show="showDefenseDetailsModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="showDefenseDetailsModal = false"></div>

        <!-- Modal Content Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 max-w-lg w-full max-h-[85vh] flex flex-col relative z-10 transform transition-all"
             @click.away="showDefenseDetailsModal = false">
            
            <!-- Modal Header -->
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-gray-800">Defense Details</h3>
                <button @click="showDefenseDetailsModal = false" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1 text-left" x-show="selectedDefense" x-cloak>
                <!-- Green Highlight Card -->
                <div class="bg-emerald-50/20 border border-emerald-100 rounded-2xl p-5 space-y-3">
                    <h4 class="font-bold text-gray-800 text-sm" x-text="selectedDefense?.title"></h4>
                    <p class="text-xs text-gray-500" x-text="selectedDefense?.student"></p>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500 text-white text-[10px] font-bold" x-text="selectedDefense?.type"></span>
                        <span class="px-2.5 py-0.5 rounded-full bg-white border border-gray-200 text-gray-500 text-[10px] font-bold animate-pulse" x-text="selectedDefense?.status"></span>
                    </div>
                </div>

                <!-- Date & Time Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3">
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Date</span>
                        <span class="text-xs font-semibold text-gray-800 block mt-1" x-text="selectedDefense?.date"></span>
                    </div>
                    <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3">
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Time</span>
                        <span class="text-xs font-semibold text-gray-800 block mt-1" x-text="selectedDefense?.time"></span>
                    </div>
                </div>

                <!-- Venue Row -->
                <div class="bg-gray-50/50 border border-gray-100 rounded-xl p-3">
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Venue</span>
                    <span class="text-xs font-semibold text-gray-800 block mt-1" x-text="selectedDefense?.venue"></span>
                </div>

                <!-- Adviser Row -->
                <div class="bg-blue-50/20 border border-blue-100 rounded-xl p-3">
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider block">Research Adviser</span>
                    <span class="text-xs font-semibold text-gray-800 block mt-1" x-text="selectedDefense?.adviser"></span>
                </div>

                <!-- Panel Members Section -->
                <div class="space-y-2">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Panel Members</span>
                    <div class="space-y-2">
                        <template x-for="member in selectedDefense?.panel" :key="member">
                            <div class="bg-white border border-gray-150 rounded-xl p-3 text-xs text-gray-750 font-semibold" x-text="member"></div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-6 border-t border-gray-100">
                <button @click="showDefenseDetailsModal = false" class="w-full py-3 bg-white border border-gray-250 text-gray-700 hover:text-gray-900 hover:bg-gray-50 text-xs font-bold rounded-xl transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
