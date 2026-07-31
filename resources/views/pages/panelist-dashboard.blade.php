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
    selectedDefense: null,
    assignedPapersSearchQuery: '',
    assignedPapersStatusFilter: 'all',
    proposalSearchQuery: '',
    proposalStatusFilter: 'all',
    proposalProposals: [
        {
            id: 'PROP-2026-001',
            title: 'Machine Learning Applications in Agricultural Pest Detection',
            status: 'Approved',
            submitted: 'March 5, 2026',
            reviewedBy: 'Dr. Maria Santos',
            approvalDate: 'March 10, 2026',
            statusClass: 'bg-[#10b981] text-white font-bold px-3 py-1 rounded-full text-[10px]'
        }
    ],
    filteredProposals() {
        return this.proposalProposals.filter(p => {
            if (this.proposalStatusFilter !== 'all' && p.status.toLowerCase() !== this.proposalStatusFilter.toLowerCase()) return false;
            if (this.proposalSearchQuery.trim() !== '') {
                const q = this.proposalSearchQuery.toLowerCase();
                return p.title.toLowerCase().includes(q) || p.id.toLowerCase().includes(q);
            }
            return true;
        });
    },
    assignedPapers: [
        {
            title: 'The Impact of Social Media Usage on the Academic Performance of Senior High Schoo...',
            college: 'College of Education',
            researchers: [
                { name: 'Maria Santos', bg: 'bg-[#0e5c3a] text-white', init: 'M' },
                { name: 'Juan dela Cruz', bg: 'bg-emerald-700 text-white', init: 'J' }
            ],
            adviser: 'Dr. Reyna Garcia',
            status: 'For Review',
            statusClass: 'bg-orange-55 border border-orange-200 text-orange-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            defenseType: 'Proposal Defense',
            defenseTypeClass: 'bg-yellow-50 border border-yellow-200 text-yellow-750 font-bold px-2 py-0.5 rounded-full text-[10px]',
            submitted: 'May 28, 2026'
        },
        {
            title: 'Effectiveness of Blended Learning Modalities on Student Engagement in NDMU College of...',
            college: 'College of Engineering',
            researchers: [
                { name: 'Ana Reyes', bg: 'bg-[#0e5c3a] text-white', init: 'A' },
                { name: 'Carlo Bautista', bg: 'bg-emerald-700 text-white', init: 'C' },
                { name: 'Lea Mercado', bg: 'bg-[#0f766e] text-white', init: 'L' }
            ],
            adviser: 'Prof. Miguel Torres',
            status: 'Under Review',
            statusClass: 'bg-blue-50 border border-blue-200 text-blue-705 font-bold px-2 py-0.5 rounded-full text-[10px]',
            defenseType: 'Pre-Oral Defense',
            defenseTypeClass: 'bg-blue-50 border border-blue-200 text-blue-800 font-bold px-2 py-0.5 rounded-full text-[10px]',
            submitted: 'May 20, 2026'
        },
        {
            title: 'Financial Literacy and Savings Behavior Among Undergraduate Students: A Mixed-...',
            college: 'College of Business',
            researchers: [
                { name: 'Paolo Lim', bg: 'bg-[#0e5c3a] text-white', init: 'P' },
                { name: 'Grace Tan', bg: 'bg-emerald-700 text-white', init: 'G' }
            ],
            adviser: 'Dr. Sandra Villanueva',
            status: 'Evaluated',
            statusClass: 'bg-emerald-50 border border-emerald-250 text-emerald-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            defenseType: 'Final Oral Defense',
            defenseTypeClass: 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            submitted: 'Apr 15, 2026'
        },
        {
            title: 'Community-Based Interventions for Maternal Health Outcomes in Selected Barangays of...',
            college: 'College of Nursing',
            researchers: [
                { name: 'Rose Aquino', bg: 'bg-[#0e5c3a] text-white', init: 'R' }
            ],
            adviser: 'Dr. Felix Navarro',
            status: 'Pending Defense',
            statusClass: 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            defenseType: 'Final Oral Defense',
            defenseTypeClass: 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            submitted: 'Mar 10, 2026'
        },
        {
            title: 'Digital Transformation in Local Government Units: Barriers and Enablers in the...',
            college: 'College of Public Administration',
            researchers: [
                { name: 'Marco Jimenez', bg: 'bg-[#0e5c3a] text-white', init: 'M' },
                { name: 'Pia Ramos', bg: 'bg-[#0f766e] text-white', init: 'P' }
            ],
            adviser: 'Dr. Lourdes Castillo',
            status: 'Approved',
            statusClass: 'bg-emerald-50 border border-emerald-250 text-emerald-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            defenseType: 'Final Oral Defense',
            defenseTypeClass: 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[10px]',
            submitted: 'Feb 22, 2026'
        }
    ],
    filteredAssignedPapers() {
        return this.assignedPapers.filter(p => {
            if (this.assignedPapersStatusFilter !== 'all' && p.status.toLowerCase() !== this.assignedPapersStatusFilter.toLowerCase()) return false;
            if (this.assignedPapersSearchQuery.trim() !== '') {
                const q = this.assignedPapersSearchQuery.toLowerCase();
                return p.title.toLowerCase().includes(q) || p.adviser.toLowerCase().includes(q) || p.college.toLowerCase().includes(q) || p.researchers.some(r => r.name.toLowerCase().includes(q));
            }
            return true;
        });
    },
    
    // Panelist-specific static data
    defenses: [
        {
            id: 1,
            student: 'Juan Dela Cruz',
            type: 'Proposal Defense',
            date: 'May 25, 2026',
            time: '9:00 AM - 11:00 AM',
            title: 'AI-Powered Traffic Management System',
            venue: 'Room 405, Research Building',
            panel: ['Dr. Antonio Santos', 'Dr. John Reyes', 'Prof. Anna Garcia'],
            status: 'Scheduled'
        },
        {
            id: 2,
            student: 'Maria Clara',
            type: 'Final Defense',
            date: 'May 28, 2026',
            time: '2:00 PM - 4:00 PM',
            title: 'Blockchain-Based Voting System',
            venue: 'Conference Room A',
            panel: ['Dr. Antonio Santos', 'Dr. Sofia Martinez', 'Prof. Carlos Lopez'],
            status: 'Scheduled'
        }
    ],

    notifications: [
        {
            id: 1,
            title: 'New Paper Assigned',
            isNew: true,
            badge: 'Paper Assignment',
            badgeClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700',
            description: 'You have been assigned as a panelist for Team AI-Traffic’s proposal defense.',
            time: '2 hours ago',
            icon: 'ph ph-file-text',
            iconBg: 'bg-emerald-50 text-emerald-600',
            category: 'documents',
            unread: true
        },
        {
            id: 2,
            title: 'Evaluation Deadline Reminder',
            isNew: true,
            badge: 'Reminder',
            badgeClass: 'bg-amber-50 border border-amber-100 text-amber-700',
            description: 'Please submit your evaluation sheet for Maria Clara’s final defense within 24 hours.',
            time: '5 hours ago',
            icon: 'ph ph-warning-circle',
            iconBg: 'bg-amber-50 text-amber-600',
            category: 'system',
            unread: true
        },
        {
            id: 3,
            title: 'Defense Schedule Updated',
            isNew: false,
            badge: 'Schedule',
            badgeClass: 'bg-purple-50 border border-purple-100 text-purple-700',
            description: 'The venue for Juan Dela Cruz’s proposal defense has been updated to Room 405.',
            time: '1 day ago',
            icon: 'ph ph-calendar',
            iconBg: 'bg-purple-50 text-purple-600',
            category: 'defense',
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
                    <span class="font-semibold text-sm text-white truncate">Dr. Antonio Santos</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Panelist</span>
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
                
                <!-- Assigned Research Papers -->
                <button 
                   type="button" 
                   @click="activeTab = 'assigned-papers'"
                   :class="activeTab === 'assigned-papers' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Assigned Research Papers</span>
                    </div>
                    <span x-show="activeTab === 'assigned-papers'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Proposal Evaluation -->
                <button 
                   type="button" 
                   @click="activeTab = 'proposal-eval'"
                   :class="activeTab === 'proposal-eval' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-scroll text-lg"></i>
                        <span>Proposal Evaluation</span>
                    </div>
                    <span x-show="activeTab === 'proposal-eval'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- Final Defense Evaluation -->
                <button 
                   type="button" 
                   @click="activeTab = 'final-eval'"
                   :class="activeTab === 'final-eval' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard-text text-lg"></i>
                        <span>Final Defense Evaluation</span>
                    </div>
                    <span x-show="activeTab === 'final-eval'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- My Recommendations -->
                <button 
                   type="button" 
                   @click="activeTab = 'recommendations'"
                   :class="activeTab === 'recommendations' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chat-teardrop text-lg"></i>
                        <span>My Recommendations</span>
                    </div>
                    <span x-show="activeTab === 'recommendations'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </button>

                <!-- My Defense Schedule -->
                <button 
                   type="button" 
                   @click="activeTab = 'schedule'"
                   :class="activeTab === 'schedule' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg"></i>
                        <span>My Defense Schedule</span>
                    </div>
                    <span x-show="activeTab === 'schedule'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
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

            <!-- Right profile area area matching "P / Dr. Evaluation Portal" -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <button @click="activeTab = 'notifications'" class="relative w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <i class="ph ph-bell text-lg"></i>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <!-- Evaluation Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        P
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">Dr.</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Evaluation Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8">
            
            <!-- TAB: Dashboard (Active Default) -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8">
                <!-- Header and Title -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800 animate-fade-in">Panelist Dashboard</h1>
                    <p class="text-xs text-gray-450 mt-1">Review and evaluate research defenses</p>
                </div>

                <!-- Stats Cards Row (4 Columns with left borders) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Upcoming Defenses -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-emerald-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Upcoming Defenses</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">5</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Pending Evaluations -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Pending Evaluations</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">3</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>

                    <!-- Completed Reviews -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-amber-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Completed Reviews</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">24</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-star"></i>
                        </span>
                    </div>

                    <!-- Average Score -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-purple-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Average Score</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">87%</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-chart-line-up"></i>
                        </span>
                    </div>
                </div>

                <!-- Scheduled Defense Panels section -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Scheduled Defense Panels</h3>
                    
                    <div class="space-y-4">
                        <template x-for="def in defenses" :key="def.id">
                            <div class="border border-gray-100/50 bg-gray-50/5 rounded-2xl p-5 flex items-center justify-between hover:border-emerald-100 hover:bg-emerald-50/5 transition-all">
                                <div>
                                    <h4 class="font-bold text-gray-850 text-sm" x-text="def.student">Student Name</h4>
                                    <span class="text-xs text-gray-400 block mt-0.5" x-text="def.type">Defense Type</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-gray-500 block font-semibold" x-text="def.date">Date</span>
                                    <button @click="selectedDefense = def" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl mt-2 shadow-sm transition-colors cursor-pointer">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        </template>
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
                            <p class="text-xs text-gray-450 mt-1">Showing notifications for: <span class="font-bold text-gray-850">Panelist</span></p>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button @click="notifications.forEach(n => n.unread = false)" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all duration-200 cursor-pointer">
                                <span>✓</span>
                                <span>Mark All Read</span>
                            </button>
                            <button class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-lg shadow-[#0e5c3a]/15 transition-all duration-200">
                                <i class="ph ph-bell text-base font-bold"></i>
                                <span x-text="`${notifications.filter(n => n.unread).length} Unread`">2 Unread</span>
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
                    'userName' => 'Dr. Antonio Santos',
                    'emailAddress' => 'a.santos@ndmu.edu.ph',
                    'userRole' => 'Panelist',
                    'userRoleBadge' => 'PANELIST',
                    'department' => 'College of Information Technology',
                    'userId' => 'PAN-2015-0004',
                    'portalType' => 'Faculty Portal',
                    'accessLevel' => 'Faculty & Guidance Access'
                ])
            </div>

            <!-- TAB: Assigned Research Papers -->
            <div x-show="activeTab === 'assigned-papers'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Title Block -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Assigned Research Papers</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Assigned Research Papers</h1>
                            <p class="text-xs text-gray-455 mt-1">View and manage research papers assigned to you for evaluation.</p>
                        </div>
                        
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-[#0e5c3a] text-xs font-bold rounded-xl">
                            <i class="ph ph-file-text"></i>
                            <span>5 Papers Assigned</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Assigned -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-emerald-500 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Total Assigned</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block" x-text="assignedPapers.length">5</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>

                    <!-- For Review -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-orange-500 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">For Review</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block" x-text="assignedPapers.filter(p => p.status === 'For Review').length">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Under Review -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-blue-500 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Under Review</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block" x-text="assignedPapers.filter(p => p.status === 'Under Review').length">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-notebook"></i>
                        </span>
                    </div>

                    <!-- Evaluated -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-emerald-600 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Evaluated</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block" x-text="assignedPapers.filter(p => ['Evaluated', 'Approved'].includes(p.status)).length">2</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-check"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center justify-between gap-4">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-base"></i>
                        </span>
                        <input
                            type="text"
                            x-model="assignedPapersSearchQuery"
                            placeholder="Search by title, researcher, adviser, or department..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all duration-200"
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 pl-1"><i class="ph ph-funnel text-base"></i></span>
                        <select 
                            x-model="assignedPapersStatusFilter" 
                            class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-855 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.2em auto;"
                        >
                            <option value="all">All Status</option>
                            <option value="for review">For Review</option>
                            <option value="under review">Under Review</option>
                            <option value="evaluated">Evaluated</option>
                            <option value="pending defense">Pending Defense</option>
                            <option value="approved">Approved</option>
                        </select>
                    </div>
                </div>

                <!-- Assigned Papers Table Card -->
                <div class="bg-white rounded-3xl border border-gray-100/50 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Research Title</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Researchers</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Adviser</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Defense Type</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Submitted</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(paper, idx) in filteredAssignedPapers()" :key="idx">
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <!-- Research Title -->
                                        <td class="px-6 py-4 max-w-sm">
                                            <div class="space-y-1">
                                                <span class="text-xs text-gray-800 font-extrabold block leading-normal" x-text="paper.title">Research Project Title</span>
                                                <span class="text-[10px] text-gray-400 font-semibold block" x-text="paper.college">College of Education</span>
                                            </div>
                                        </td>

                                        <!-- Researchers -->
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1.5">
                                                <template x-for="r in paper.researchers" :key="r.name">
                                                    <div class="flex items-center gap-2">
                                                        <span :class="r.bg" class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-black shrink-0" x-text="r.init">M</span>
                                                        <span class="text-xs text-gray-700 font-bold" x-text="r.name">Researcher Name</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>

                                        <!-- Adviser -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1.5 text-xs text-gray-600 font-bold">
                                                <i class="ph ph-user-shared text-gray-400"></i>
                                                <span x-text="paper.adviser">Dr. Reyna Garcia</span>
                                            </div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="px-6 py-4">
                                            <span :class="paper.statusClass" x-text="paper.status">For Review</span>
                                        </td>

                                        <!-- Defense Type -->
                                        <td class="px-6 py-4">
                                            <span :class="paper.defenseTypeClass" x-text="paper.defenseType">Proposal Defense</span>
                                        </td>

                                        <!-- Submitted Date -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1.5 text-[11px] text-gray-500 font-semibold">
                                                <i class="ph ph-calendar text-gray-400"></i>
                                                <span x-text="paper.submitted">May 28, 2026</span>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2.5">
                                                <button @click="alert(`Viewing details for: ${paper.title}`)" class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors cursor-pointer" title="View details">
                                                    <i class="ph ph-eye text-sm"></i>
                                                </button>
                                                <button @click="alert(`Evaluating research: ${paper.title}`)" class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center hover:bg-emerald-100 transition-colors cursor-pointer" title="Evaluate paper">
                                                    <i class="ph ph-file-text text-sm"></i>
                                                </button>
                                                <button @click="alert(`Downloading document for: ${paper.title}`)" class="w-7 h-7 rounded-lg border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-gray-50 transition-colors cursor-pointer" title="Download paper">
                                                    <i class="ph ph-download text-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="filteredAssignedPapers().length === 0">
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-xs text-gray-455 font-bold">
                                            No assigned research papers found matching search query or status filter.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Proposal Evaluation -->
            <div x-show="activeTab === 'proposal-eval'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Proposal Management</h1>
                    <p class="text-xs text-gray-455 mt-1">Manage research proposals and approvals</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 border-l-4 border-l-[#10b981] shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Approved</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="proposalProposals.filter(p => p.status === 'Approved').length">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#10b981] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 border-l-4 border-l-amber-500 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Pending</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="proposalProposals.filter(p => p.status === 'Pending').length">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-550 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 border-l-4 border-l-red-500 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-855 mt-2 block" x-text="proposalProposals.filter(p => p.status === 'Revisions').length">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 border-l-4 border-l-blue-500 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Total Proposals</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="proposalProposals.length">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Main Proposal Card -->
                <div class="bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 space-y-6">
                    <h2 class="text-sm font-bold text-gray-850 font-heading tracking-wide">Research Proposal</h2>
                    
                    <div class="space-y-4">
                        <template x-for="p in filteredProposals()" :key="p.id">
                            <div class="bg-[#f0fdf4] border border-emerald-100 rounded-3xl p-6 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div>
                                        <h3 class="font-extrabold text-sm text-[#0e5c3a] leading-snug" x-text="p.title">Machine Learning Applications in Agricultural Pest Detection</h3>
                                        <p class="text-[10px] text-gray-500 font-semibold mt-1.5" x-text="`Proposal ID: ${p.id}`">Proposal ID: PROP-2026-001</p>
                                        <p class="text-[10px] text-gray-500 font-semibold mt-0.5" x-text="`Submitted: ${p.submitted}`">Submitted: March 5, 2026</p>
                                    </div>
                                    <span :class="p.statusClass" class="flex-shrink-0 self-start text-[10px] font-black" x-text="p.status">Approved</span>
                                </div>

                                <div class="border-t border-emerald-100/50 pt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Reviewed by</span>
                                        <span class="text-xs text-gray-800 font-bold block mt-1" x-text="p.reviewedBy">Dr. Maria Santos</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Approval Date</span>
                                        <span class="text-xs text-gray-800 font-bold block mt-1" x-text="p.approvalDate">March 10, 2026</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <button @click="alert(`Viewing Proposal: ${p.title}`)" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-all cursor-pointer">
                                        View Proposal
                                    </button>
                                    <button @click="alert(`Downloading PDF for: ${p.title}`)" class="px-5 py-2.5 bg-white border border-gray-250 hover:bg-gray-50 text-gray-707 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                        Download PDF
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="filteredProposals().length === 0">
                            <div class="bg-gray-50 rounded-2xl p-8 text-center text-xs text-gray-455 font-semibold border border-gray-100">
                                No proposals found matching search query.
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'settings', 'assigned-papers', 'proposal-eval'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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

    <!-- Defense Details Modal -->
    <div x-show="selectedDefense" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="selectedDefense = null" class="bg-white rounded-3xl w-full max-w-md p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-gray-800 text-sm" x-text="selectedDefense?.type">Defense Details</h3>
                <button @click="selectedDefense = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-gray-400 block font-semibold">Research Project Title</span>
                    <span class="text-gray-800 font-bold block mt-1 text-sm" x-text="selectedDefense?.title">Project Title</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-400 block font-semibold">Student Researcher</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedDefense?.student">Student Name</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Venue</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedDefense?.venue">Venue</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-400 block font-semibold">Date</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedDefense?.date">Date</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-semibold">Schedule Time</span>
                        <span class="text-gray-800 font-bold block mt-1" x-text="selectedDefense?.time">Time</span>
                    </div>
                </div>
                <div>
                    <span class="text-gray-400 block font-semibold">Defense Panel</span>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <template x-for="p in selectedDefense?.panel" :key="p">
                            <span class="px-2 py-1 bg-gray-50 text-gray-700 border border-gray-100 rounded-lg text-[10px] font-bold" x-text="p">Panelist</span>
                        </template>
                    </div>
                </div>
            </div>
            <hr class="border-gray-100">
            <div class="pt-2 flex justify-end gap-3">
                <button @click="selectedDefense = null" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
                <button @click="alert('Loading evaluation sheet... (Mock)'); selectedDefense = null" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                    Evaluate Defense
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
