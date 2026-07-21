@extends('layouts.blank')

@section('content')
<div class="min-h-screen flex font-sans bg-[#f4f7f6]">
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
                    M
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
                
                <a href="#" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-[#eebc3f] text-[#0e5c3a] font-bold text-xs shadow-sm transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
                
                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-users text-lg"></i>
                    <span>My Classes</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-book-open text-lg"></i>
                    <span>My Research</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-file-text text-lg"></i>
                    <span>Research Proposal</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-chart-line-up text-lg"></i>
                    <span>Research Progress</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-chat-teardrop text-lg"></i>
                    <span>Consultation Records</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-note-pencil text-lg"></i>
                    <span>Revision Tracker</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-calendar text-lg"></i>
                    <span>Defense Schedule</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-exam text-lg"></i>
                    <span>Evaluation Results</span>
                </a>

                <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-folder text-lg"></i>
                    <span>Research Repository</span>
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
                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
                    <i class="ph ph-bell text-lg"></i>
                    <span>Notifications</span>
                </a>
                <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-white/90 hover:text-white hover:bg-white/5 font-semibold text-[13px] transition-all duration-200">
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
                        <span class="text-[10px] text-red-500 font-bold block mt-1">2 days until deadline</span>
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
                            <i class="ph ph-arrow-square-out text-lg text-gray-400 hover:text-gray-650 cursor-pointer"></i>
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
                            <button class="w-full py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl transition-all duration-200">
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
                                    <h4 class="font-bold text-gray-800 text-sm">Proposal Defense</h4>
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
                                    <h4 class="font-bold text-gray-800 text-sm">Data Gathering & Analysis</h4>
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
                                <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-650 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
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
        </main>
    </div>
</div>
@endsection
