@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'classes', 'requests', 'consultation', 'docreview', 'revisions', 'repository', 'forms', 'notifications', 'settings', 'researchers', 'proposal', 'monitoring', 'endorsement', 'evaluations'];
    $initialTab = $activeDashboardTab ?? (in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard');
    $showClassModal = $errors->hasAny(['class', 'creation_token', 'name', 'description', 'max_students']);
    $officialFormPhases = $officialFormPhases ?? [];
    $officialForms = $officialForms ?? [];
    $officialFormsByPhase = collect($officialForms)->groupBy('phase', preserveKeys: true);
    $requestedOfficialForm = request()->query('form');
    $initialOfficialForm = is_string($requestedOfficialForm) && array_key_exists($requestedOfficialForm, $officialForms)
        ? $requestedOfficialForm
        : array_key_first($officialForms);
    $initialFormPhase = $initialOfficialForm === null
        ? array_key_first($officialFormPhases)
        : $officialForms[$initialOfficialForm]['phase'];
@endphp

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
    activeTab: @js($initialTab),
    activeFormPhase: @js($initialFormPhase),
    activeOfficialForm: @js($initialOfficialForm),
    officialForms: @js($officialForms),
    formsExpanded: @js($initialTab === 'forms'),
    notificationsFilter: 'all',
    showClassModal: @js($showClassModal),
    showConsultationModal: false,
    showRepositoryUploadModal: @js($errors->has('document') && $initialTab === 'repository'),
    selectedNotification: null,
    notifications: @js($adviserNotifications),
    assignedResearchers: @js($adviserOverviewAdvisees),
    evaluationBreakdown: [
        { label: 'Research Originality', score: '23', max: '25', percent: '92%' },
        { label: 'Methodology', score: '18', max: '20', percent: '90%' },
        { label: 'Literature Review', score: '14', max: '15', percent: '93.3%' },
        { label: 'Data Analysis', score: '17', max: '20', percent: '85%' },
        { label: 'Presentation & Defense', score: '18', max: '20', percent: '90%' }
    ],
    evaluationComments: [
        { name: 'Dr. Maria Santos', title: 'Panel Chair', rating: 5, comment: 'Excellent research methodology and data analysis. The presentation was clear and well-structured.', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { name: 'Dr. John Reyes', title: 'Panelist', rating: 4, comment: 'Strong theoretical foundation. Consider expanding the literature review section.', borderClass: 'border-blue-100 bg-blue-50/10' },
        { name: 'Prof. Anna Garcia', title: 'Panelist', rating: 5, comment: 'Innovative approach and practical applications. Well-defended arguments.', borderClass: 'border-purple-100 bg-purple-50/10' }
    ],
    defenseSearchQuery: '',
    defenseTypeFilter: 'all',
    defenseStatusFilter: 'all',
    defenseSchedules: [
        {
            type: 'Proposal Defense',
            status: 'Scheduled',
            title: 'AI-Powered Traffic Management ' + 'System',
            student: 'Juan Del' + 'a Cruz',
            date: 'May 25, 2026',
            time: '9:00 AM - 11:00 AM',
            venue: 'Room 405, Research Building',
            panels: ['Dr. Maria Santos', 'Dr. John Reyes', 'Prof. Anna Garcia'],
            leftBorder: 'border-l-4 border-l-[#10b981]',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
        },
        {
            type: 'Final Defense',
            status: 'Scheduled',
            title: 'Blockchain-Based Voting System',
            student: 'Maria Clara',
            date: 'May 28, 2026',
            time: '2:00 PM - 4:00 PM',
            venue: 'Conference Room A',
            panels: ['Dr. Pedro Cruz', 'Dr. Sofia Martinez', 'Prof. Carlos Lopez'],
            leftBorder: 'border-l-4 border-l-[#10b981]',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
        },
        {
            type: 'Proposal Defense',
            status: 'Pending',
            title: 'Machine Learning in Agricultural Pest Detection',
            student: 'Your Research',
            date: 'July 15, 2026',
            time: 'TBA',
            venue: 'TBA',
            panels: [],
            leftBorder: 'border-l-4 border-l-amber-500',
            statusClass: 'bg-amber-50 border border-amber-100 text-amber-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
        }
    ],
    filteredSchedules() {
        return this.defenseSchedules.filter(s => {
            if (this.defenseTypeFilter !== 'all' && s.type.toLowerCase() !== this.defenseTypeFilter.toLowerCase()) return false;
            if (this.defenseStatusFilter !== 'all' && s.status.toLowerCase() !== this.defenseStatusFilter.toLowerCase()) return false;
            return true;
        });
    },
    monitoringMilestones: [
        { title: 'Research Title Presentation', date: 'Feb 15, 2026', status: 'Completed', statusClass: 'bg-[#10b981] text-white', desc: 'All requirements met and approved', icon: 'ph-check-circle text-emerald-500 bg-emerald-50 border border-emerald-100', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { title: 'Proposal Approval', date: 'Mar 10, 2026', status: 'Completed', statusClass: 'bg-[#10b981] text-white', desc: 'All requirements met and approved', icon: 'ph-check-circle text-emerald-500 bg-emerald-50 border border-emerald-100', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { title: 'Adviser Endorsement', date: 'Mar 20, 2026', status: 'Completed', statusClass: 'bg-[#10b981] text-white', desc: 'All requirements met and approved', icon: 'ph-check-circle text-emerald-500 bg-emerald-50 border border-emerald-100', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { title: 'Instrument Validation', date: 'Apr 5, 2026', status: 'Completed', statusClass: 'bg-[#10b981] text-white', desc: 'All requirements met and approved', icon: 'ph-check-circle text-emerald-500 bg-emerald-50 border border-emerald-100', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { title: 'Data Gathering', date: 'In Progress', status: 'In Progress', statusClass: 'bg-[#f59e0b] text-white', desc: 'Currently working on this milestone', icon: 'ph-clock text-amber-500 bg-amber-50 border border-amber-100', borderClass: 'border-amber-200 bg-amber-50/5' },
        { title: 'Proposal Defense', date: 'May 10, 2026', status: 'Completed', statusClass: 'bg-[#10b981] text-white', desc: 'All requirements met and approved', icon: 'ph-check-circle text-emerald-500 bg-emerald-50 border border-emerald-100', borderClass: 'border-emerald-100 bg-emerald-50/10' },
        { title: 'Revisions', date: 'May 18, 2026', status: 'In Progress', statusClass: 'bg-[#f59e0b] text-white', desc: 'Currently working on this milestone', icon: 'ph-clock text-amber-500 bg-amber-50 border border-amber-100', borderClass: 'border-amber-200 bg-amber-50/5' },
        { title: 'Final Defense', date: 'Jul 15, 2026', status: 'Pending', statusClass: 'bg-[#9ca3af] text-white', desc: '', icon: 'ph-circle text-gray-300 bg-gray-50 border border-gray-100', borderClass: 'border-gray-100' },
        { title: 'Technical Editing', date: 'Not Started', status: 'Pending', statusClass: 'bg-[#9ca3af] text-white', desc: '', icon: 'ph-circle text-gray-300 bg-gray-50 border border-gray-100', borderClass: 'border-gray-100' },
        { title: 'Language Editing', date: 'Not Started', status: 'Pending', statusClass: 'bg-[#9ca3af] text-white', desc: '', icon: 'ph-circle text-gray-300 bg-gray-50 border border-gray-100', borderClass: 'border-gray-100' },
        { title: 'Final Manuscript Approval', date: 'Not Started', status: 'Pending', statusClass: 'bg-[#9ca3af] text-white', desc: '', icon: 'ph-circle text-gray-300 bg-gray-50 border border-gray-100', borderClass: 'border-gray-100' },
        { title: 'Certificate of Authentic Authorship', date: 'Not Started', status: 'Pending', statusClass: 'bg-[#9ca3af] text-white', desc: '', icon: 'ph-circle text-gray-300 bg-gray-50 border border-gray-100', borderClass: 'border-gray-100' }
    ],
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
    userSearchQuery: '',
    userRoleFilter: 'all',
    managementSubTab: 'all',
    managementUsers: [
        { name: 'System Administrator', initials: 'S', initialsBg: 'bg-emerald-700 text-white', email: 'admin@ndmu.edu.ph', role: 'Administrator', roleClass: 'bg-blue-50 border border-blue-100 text-blue-700', status: 'Active', dept: 'Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Lourdes Castillo', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'l.castillo@ndmu.edu.ph', role: 'College Dean', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'Office of the College Dean', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Rosario Dela Paz', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'r.dela-paz@ndmu.edu.ph', role: 'Research Facilitator', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Engr. Jose Montero', initials: 'E', initialsBg: 'bg-emerald-700 text-white', email: 'j.montero@ndmu.edu.ph', role: 'Research Facilitator', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Engineering', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Reyna Garcia', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'r.garcia@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Michael Tan', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'm.tan@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Prof. Lucia Fernandez', initials: 'P', initialsBg: 'bg-emerald-700 text-white', email: 'l.fernandez@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Engineering', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Maria Santos', initials: 'M', initialsBg: 'bg-emerald-700 text-white', email: 'maria.santos@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Information Technology', date: '2024-08-12', isSystem: true },
        { name: 'Carlo Mendoza', initials: 'C', initialsBg: 'bg-[#0f766e] text-white', email: 'carlo.mendoza@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Information Technology', date: '2024-08-12', isSystem: true },
        { name: 'Anna Lim', initials: 'A', initialsBg: 'bg-emerald-700 text-white', email: 'anna.lim@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Information Technology', date: '2024-08-15', isSystem: true },
        { name: 'Felix Torres', initials: 'F', initialsBg: 'bg-[#0f766e] text-white', email: 'felix.torres@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2024-08-20', isSystem: true },
        { name: 'Sofia Herrera', initials: 'S', initialsBg: 'bg-emerald-700 text-white', email: 'sofia.herrera@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2025-08-10', isSystem: true },
        { name: 'Rafael Ocampo', initials: 'R', initialsBg: 'bg-[#0f766e] text-white', email: 'rafael.ocampo@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2025-08-10', isSystem: true },
        { name: 'Isabelle Garcia', initials: 'I', initialsBg: 'bg-emerald-700 text-white', email: 'isabelle.garcia@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', text: 'Active', dept: 'College of Engineering', date: '2025-08-11', isSystem: true },
        { name: 'Marco Villanueva', initials: 'M', initialsBg: 'bg-[#0f766e] text-white', email: 'marco.villanueva@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2025-08-12', isSystem: true },
        { name: 'Juan Del' + 'a Cruz', initials: 'J', initialsBg: 'bg-[#0f766e] text-white', email: 'juan.delacruz@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Engineering', date: '2026-05-28', isSystem: false },
        { name: 'Ana Reyes', initials: 'A', initialsBg: 'bg-emerald-700 text-white', email: 'ana.reyes@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Information Technology', date: '2026-05-30', isSystem: false },
        { name: 'Kevin Aguila', initials: 'K', initialsBg: 'bg-[#0f766e] text-white', email: 'kevin.aguila@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Engineering', date: '2026-06-01', isSystem: false },
        { name: 'Clara Nieto', initials: 'C', initialsBg: 'bg-[#0f766e] text-white', email: 'clara.nieto@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Engineering', date: '2026-06-01', isSystem: false },
        { name: 'Dante Flores', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'dante.flores@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Engineering', date: '2026-06-02', isSystem: false },
        { name: 'Dr. Miguel Torres', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'newadviser@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Engineering', date: '2026-06-01', isSystem: false, hasTempPw: true },
        { name: 'Prof. Roberto Garcia', initials: 'R', initialsBg: 'bg-emerald-700 text-white', email: 'r.garcia@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Patricia Cruz', initials: 'D', initialsBg: 'bg-[#0f766e] text-white', email: 'p.cruz@ndmu.edu.ph', role: 'Research Adviser', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Engineering', date: '2024-01-01', isSystem: true },
        { name: 'Prof. Michael Tan', initials: 'P', initialsBg: 'bg-emerald-700 text-white', email: 'm.tan-panelist@ndmu.edu.ph', role: 'Panelist', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Dr. Antonio Santos', initials: 'D', initialsBg: 'bg-emerald-700 text-white', email: 'a.santos@ndmu.edu.ph', role: 'Panelist', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Information Technology', date: '2024-01-01', isSystem: true },
        { name: 'Prof. Patricia Cruz', initials: 'P', initialsBg: 'bg-[#0f766e] text-white', email: 'p.cruz-panelist@ndmu.edu.ph', role: 'Panelist', roleClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700', status: 'Active', dept: 'College of Engineering', date: '2024-01-01', isSystem: true }
    ],
    filteredUsers() {
        return this.managementUsers.filter(u => {
            if (this.managementSubTab === 'pending' && u.status !== 'Pending') return false;
            if (this.userRoleFilter !== 'all' && u.role.toLowerCase() !== this.userRoleFilter.toLowerCase()) return false;
            if (this.userSearchQuery.trim() !== '') {
                const q = this.userSearchQuery.toLowerCase();
                return u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
            }
            return true;
        });
    },
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
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                </div>
                <div class="flex flex-col leading-tight overflow-hidden">
                    <span class="font-semibold text-sm text-white truncate">{{ $adviser->name }}</span>
                    <span class="text-[10px] text-white/60 font-medium mt-0.5">Research Adviser</span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-6 py-4 space-y-6">
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Navigation</span>
                
                <!-- Dashboard -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'dashboard']) }}"
                   wire:navigate
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
                
                <!-- My Classes -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'classes']) }}"
                   wire:navigate
                   :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-book text-lg"></i>
                        <span>My Classes</span>
                    </div>
                    <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Join Requests -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'requests']) }}"
                   wire:navigate
                   :class="activeTab === 'requests' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-user-plus text-lg"></i>
                        <span>Join Requests</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($requestStats['pending'] > 0)
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                                {{ $requestStats['pending'] }}
                            </span>
                        @endif
                        <span x-show="activeTab === 'requests'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                    </div>
                </a>

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
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}"
                   wire:navigate
                   :class="activeTab === 'consultation' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chat-teardrop text-lg"></i>
                        <span>Consultation Records</span>
                    </div>
                    <span x-show="activeTab === 'consultation'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Document Review -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'docreview']) }}"
                   wire:navigate
                   :class="activeTab === 'docreview' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg"></i>
                        <span>Document Review</span>
                    </div>
                    <span x-show="activeTab === 'docreview'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

                <!-- Revision Management -->
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'revisions']) }}"
                   wire:navigate
                   :class="activeTab === 'revisions' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-arrows-counter-clockwise text-lg"></i>
                        <span>Revision Management</span>
                    </div>
                    <span x-show="activeTab === 'revisions'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>

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
                <a
                   href="{{ route('adviser.dashboard', ['tab' => 'repository']) }}"
                   wire:navigate
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                   class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a]"></span>
                </a>
            </div>

            <!-- Research Forms Section -->
            <div class="space-y-1.5 pt-4 mt-4 border-t border-white/10">
                <span class="text-[10px] font-bold tracking-wider text-[#a5c1a0] uppercase px-3 block mb-2">Research Forms</span>

                <button
                    type="button"
                    @click="formsExpanded = ! formsExpanded; activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#0e5c3a] font-bold shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/5 font-semibold'"
                    :aria-expanded="formsExpanded"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-[13px] transition-all duration-200 text-left cursor-pointer"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg"></i>
                        <span>Official Forms</span>
                    </div>
                    <i class="ph ph-caret-right text-xs transition-transform duration-200" :class="formsExpanded && 'rotate-90'"></i>
                </button>

                <div x-show="formsExpanded" x-cloak x-transition class="mt-1 space-y-0.5">
                    @foreach ($officialFormPhases as $phase => $label)
                        @php
                            $phaseForms = $officialFormsByPhase->get($phase, collect());
                        @endphp
                        <div>
                            <button
                                type="button"
                                @click="activeTab = 'forms'; activeFormPhase = activeFormPhase === '{{ $phase }}' ? null : '{{ $phase }}'"
                                class="w-full flex items-center justify-between gap-2 py-2 pl-4 pr-3 rounded-xl text-white/55 hover:text-white hover:bg-white/5 transition-colors duration-200 text-[11px] font-semibold text-left"
                            >
                                <span class="flex min-w-0 items-start gap-2">
                                    <i class="ph ph-caret-right mt-0.5 shrink-0 text-[10px] transition-transform duration-200" :class="activeFormPhase === '{{ $phase }}' && 'rotate-90'"></i>
                                    <span class="leading-4">{{ $label }}</span>
                                </span>
                                <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#eebc3f]/20 px-1.5 text-[9px] font-bold text-[#eebc3f]">{{ $phaseForms->count() }}</span>
                            </button>

                            <div x-show="activeFormPhase === '{{ $phase }}'" x-cloak x-transition class="mt-0.5 space-y-0.5 pl-2">
                                @foreach ($phaseForms as $code => $form)
                                    <button
                                        type="button"
                                        @click="activeTab = 'forms'; activeOfficialForm = '{{ $code }}'"
                                        :class="activeOfficialForm === '{{ $code }}' ? 'bg-[#eebc3f] text-[#0e5c3a] ring-1 ring-white font-bold' : 'text-white/70 hover:text-white hover:bg-white/5'"
                                        class="w-full flex items-start gap-2 rounded-xl px-3 py-2 text-left transition-colors duration-200"
                                    >
                                        <i class="ph ph-file-plus mt-0.5 shrink-0 text-sm"></i>
                                        <span class="min-w-0">
                                            <span class="block text-[10px] font-bold">{{ $code }}</span>
                                            <span class="block text-[10px] leading-3.5">{{ $form['title'] }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
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
                NDMU © {{ now()->year }} - v1.0
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
                    <span x-show="notifications.some(notification => notification.unread)" class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>
                
                <!-- Faculty Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">{{ $adviser->name }}</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Faculty Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8">
            @if (session('class_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('class_success') }}
                </div>
            @endif

            @if ($errors->has('class'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('class') }}
                </div>
            @endif

            @if (session('consultation_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('consultation_success') }}
                </div>
            @endif

            @if ($errors->has('consultation'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('consultation') }}
                </div>
            @endif

            @if (session('document_review_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('document_review_success') }}
                </div>
            @endif

            @if ($errors->has('document_review'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('document_review') }}
                </div>
            @endif

            @if (session('revision_success'))
                <div role="status" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('revision_success') }}
                </div>
            @endif

            @if ($errors->has('revision'))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first('revision') }}
                </div>
            @endif

            @if ($errors->hasAny(['comment', 'severity', 'page_number', 'parent_id', 'decision', 'review_notes']))
                <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            
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
                            <span class="text-3xl font-bold mt-2 block">{{ $adviserOverviewStats['active_advisees'] }}</span>
                            <span class="text-[10px] text-[#eebc3f] font-bold mt-1 block">{{ $adviserOverviewStats['nearing_defense'] }} nearing defense</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-white/10 text-[#eebc3f] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-users-three"></i>
                        </span>
                    </div>

                    <!-- Urgent Reviews (White/Red left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Urgent Reviews</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">{{ $adviserOverviewStats['urgent_reviews'] }}</span>
                            <span class="text-[10px] text-red-500 font-bold mt-1 block">{{ $adviserOverviewStats['overdue_revisions'] }} overdue revisions</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Today's Consultations (White/Blue left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Today's Consultations</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">{{ $adviserOverviewStats['today_consultations'] }}</span>
                            <span class="text-[10px] text-blue-500 font-bold mt-1 block">
                                Next: {{ $adviserOverviewStats['next_consultation_at']?->timezone(config('ndmu-rmas.timezone'))->format('g:i A') ?? 'None scheduled' }}
                            </span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Completed Research (White/Purple left border) -->
                    <div class="bg-white rounded-3xl p-5 border-l-4 border-l-purple-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Completed Research</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">{{ $adviserOverviewStats['completed_research'] }}</span>
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
                                            <h4 class="font-bold text-gray-850 text-sm" x-text="r.name"></h4>
                                            <span class="text-xs text-gray-400 block mt-0.5" x-text="r.project || 'No research project assigned'"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-extrabold text-emerald-700 block" x-text="`${r.progress}%`"></span>
                                            <span class="text-[9px] text-gray-400 uppercase tracking-wider">Complete</span>
                                        </div>
                                    </div>

                                    <!-- Badges -->
                                    <div class="flex gap-2">
                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold" x-text="r.status || 'No recorded status'"></span>
                                        <span class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold" x-text="r.progress >= 100 ? 'Complete' : 'In progress'"></span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="space-y-1">
                                        <div class="h-2 bg-gray-100 rounded-full w-full overflow-hidden">
                                            <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" :style="`width: ${r.progress}%`"></div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-3 pt-1">
                                        <button @click="activeTab = 'researchers'" class="w-1/2 text-center py-2 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                                            View Research
                                        </button>
                                        <a href="{{ route('adviser.dashboard', ['tab' => 'docreview']) }}" wire:navigate class="w-1/2 text-center py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-sm transition-colors cursor-pointer">
                                            Review Documents
                                        </a>
                                    </div>
                                </div>
                            </template>
                            <div x-show="assignedResearchers.length === 0" class="rounded-2xl border border-dashed border-gray-200 p-8 text-center text-xs text-gray-500">
                                No active advisees are assigned.
                            </div>
                        </div>

                        <!-- View All Footer Link -->
                        <button @click="activeTab = 'researchers'" class="w-full text-center py-3 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold text-xs rounded-2xl transition-colors cursor-pointer">
                            View All {{ $adviserOverviewStats['active_advisees'] }} Advisees →
                        </button>
                    </div>

                    <!-- Right: Pending, Schedule, Actions -->
                    <div class="space-y-6">
                        
                        <!-- Side Widget 1: Pending Reviews -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm flex items-center gap-2 pb-2 border-b border-gray-50">
                                <i class="ph ph-file-text text-amber-500 text-lg"></i>
                                <span>Pending Reviews ({{ $adviserOverviewStats['urgent_reviews'] }})</span>
                            </h3>

                            <div class="space-y-4">
                                @forelse ($adviserPendingDocuments as $pendingDocument)
                                    <div class="border-l-4 border-l-amber-500 bg-amber-50/10 rounded-2xl p-4 border-t border-r border-b border-gray-100/50 space-y-3">
                                        <div>
                                            <span class="font-bold text-gray-800 text-xs block">{{ $pendingDocument->original_filename }}</span>
                                            <span class="text-[10px] text-gray-400 block mt-0.5">{{ $pendingDocument->user->name }}</span>
                                            <span class="text-[10px] text-amber-600 font-bold mt-1.5 block flex items-center gap-1">
                                                <i class="ph ph-clock"></i>
                                                Submitted {{ $pendingDocument->submitted_at?->diffForHumans() }}
                                            </span>
                                        </div>
                                        <a href="{{ route('adviser.dashboard', ['tab' => 'docreview', 'document_id' => $pendingDocument->getKey(), 'document_status' => 'all']) }}" wire:navigate class="block w-full text-center py-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-[10px] font-bold rounded-lg transition-colors cursor-pointer">
                                            Review Document
                                        </a>
                                    </div>
                                @empty
                                    <p class="py-6 text-center text-xs text-gray-500">No documents are waiting for review.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Side Widget 2: Today's Consultations (Solid Blue Card) -->
                        <div class="bg-blue-600 text-white rounded-3xl p-6 shadow-md space-y-4">
                            <h3 class="font-bold text-white text-sm flex items-center gap-2 pb-2 border-b border-white/10">
                                <i class="ph ph-calendar text-lg"></i>
                                <span>Today's Consultations</span>
                            </h3>

                            <div class="space-y-3">
                                @forelse ($adviserTodayConsultations as $consultation)
                                    <div class="flex items-center justify-between py-2 border-b border-white/10 last:border-b-0">
                                        <div>
                                            <span class="font-bold text-xs block">{{ $consultation->student_name }}</span>
                                            <span class="text-[10px] text-white/80 mt-0.5 block">{{ $consultation->agenda }}</span>
                                        </div>
                                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded font-bold">
                                            {{ $consultation->preferred_at?->timezone(config('ndmu-rmas.timezone'))->format('g:i A') }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="py-4 text-center text-xs text-white/80">No consultations scheduled today.</p>
                                @endforelse
                            </div>

                            <a href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}" wire:navigate class="block w-full text-center py-2 bg-white hover:bg-gray-50 text-blue-600 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                                View Full Schedule
                            </a>
                        </div>

                        <!-- Side Widget 3: Quick Actions -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Quick Actions</h3>
                            <div class="space-y-2.5">
                                <button @click="activeTab = 'proposal'" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Review Proposals
                                </button>
                                <a href="{{ route('adviser.dashboard', ['tab' => 'consultation']) }}" wire:navigate class="block bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Schedule Consultation
                                </a>
                                <button @click="activeTab = 'endorsement'" class="bg-purple-50 hover:bg-purple-100 text-purple-800 font-bold text-xs w-full py-2.5 rounded-xl transition-colors cursor-pointer text-center">
                                    Recommend for Defense
                                </button>
                            </div>
                        </div>

                        <!-- Side Widget 4: Recent Activity -->
                        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-800 text-sm pb-2 border-b border-gray-50">Recent Activity</h3>
                            <div class="space-y-4">
                                @forelse ($adviserRecentActivity as $activity)
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0">
                                            <i class="ph {{ $activity['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-gray-800 text-xs block">{{ $activity['title'] }}</span>
                                            <span class="text-[10px] text-gray-400 block mt-0.5">
                                                {{ $activity['student_name'] ?: 'Student' }} · {{ $activity['occurred_at']?->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="py-4 text-center text-xs text-gray-500">No review activity recorded yet.</p>
                                @endforelse
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
                                <span x-text="`${notifications.filter(n => n.unread).length} Unread`">{{ $adviserNotificationStats['unread'] }} Unread</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.length">{{ $adviserNotificationStats['total'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Total</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="ph ph-bell"></i>
                        </span>
                    </div>

                    <!-- Unread Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.unread).length">{{ $adviserNotificationStats['unread'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Unread</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Defense Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.category === 'defense').length">{{ $adviserNotificationStats['defense'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block font-heading mt-0.5">Defense</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- Documents Card -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="notifications.filter(n => n.category === 'documents').length">{{ $adviserNotificationStats['documents'] }}</span>
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
                            x-text="notifications.length">{{ $adviserNotificationStats['total'] }}</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'unread'"
                        :class="notificationsFilter === 'unread' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Unread</span>
                        <span 
                            :class="notificationsFilter === 'unread' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.unread).length">{{ $adviserNotificationStats['unread'] }}</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'approvals'"
                        :class="notificationsFilter === 'approvals' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Approvals</span>
                        <span 
                            :class="notificationsFilter === 'approvals' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'approvals').length">{{ $adviserNotificationStats['approvals'] }}</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'defense'"
                        :class="notificationsFilter === 'defense' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Defense</span>
                        <span 
                            :class="notificationsFilter === 'defense' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'defense').length">{{ $adviserNotificationStats['defense'] }}</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'documents'"
                        :class="notificationsFilter === 'documents' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>Documents</span>
                        <span 
                            :class="notificationsFilter === 'documents' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'documents').length">{{ $adviserNotificationStats['documents'] }}</span>
                    </button>
                    <button 
                        @click="notificationsFilter = 'system'"
                        :class="notificationsFilter === 'system' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-50 border border-gray-100 hover:bg-gray-100 text-gray-600'"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all cursor-pointer">
                        <span>System</span>
                        <span 
                            :class="notificationsFilter === 'system' ? 'bg-white/20 text-white' : 'bg-gray-200/50 text-gray-500'"
                            class="px-1.5 py-0.5 text-[10px] rounded-full" 
                            x-text="notifications.filter(n => n.category === 'system').length">{{ $adviserNotificationStats['system'] }}</span>
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
                                    <h4 class="font-bold text-gray-800 text-sm" x-text="item.title"></h4>
                                    
                                    <!-- Dynamic Badges -->
                                    <template x-if="item.isNew">
                                        <span class="px-2 py-0.5 rounded bg-emerald-700 text-white text-[9px] font-bold">New</span>
                                    </template>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-semibold" :class="item.badgeClass" x-text="item.badge"></span>
                                </div>
                                
                                <p class="text-xs text-gray-550 leading-relaxed" x-text="item.description"></p>
                                
                                <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold">
                                    <i class="ph ph-clock"></i>
                                    <span x-text="item.time"></span>
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
                    <div x-show="notifications.length === 0" class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-10 text-center text-sm text-gray-500">
                        No notifications have been delivered.
                    </div>
                </div>
            </div>

            <!-- TAB: Classes -->
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
                    @forelse ($researchClasses as $researchClass)
                        <a href="{{ route('adviser.classes.show', $researchClass) }}" wire:navigate class="block bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 hover:border-[#0e5c3a]/30 hover:shadow-md transition-all">
                            <div class="flex justify-between items-start">
                                <span class="px-3 py-1 bg-amber-50 text-amber-700 text-[10px] font-extrabold rounded-full">{{ $researchClass->revealJoinCode() }}</span>
                                <i class="ph ph-dots-three-vertical text-gray-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm">{{ $researchClass->name }}</h3>
                                @if ($researchClass->description)
                                    <p class="text-[11px] text-gray-400 mt-1">{{ $researchClass->description }}</p>
                                @endif
                            </div>
                             <div class="flex justify-between items-center pt-4 border-t border-gray-50 text-xs">
                                 <span class="text-gray-500 font-semibold">{{ $researchClass->active_students_count }} Students</span>
                                 <span class="text-amber-600 font-bold">
                                     {{ $researchClass->pending_join_requests_count }} Pending · Limit: {{ $researchClass->max_students }}
                                 </span>
                             </div>
                        </a>
                    @empty
                        <div class="md:col-span-3 bg-white rounded-3xl p-10 border border-gray-100 shadow-sm text-center">
                            <i class="ph ph-chalkboard-teacher text-3xl text-gray-300"></i>
                            <p class="text-sm text-gray-500 mt-3">You have not created a research class yet.</p>
                        </div>
                    @endforelse
                </div>
             </div>

            <!-- TAB: Join Requests -->
            <div x-show="activeTab === 'requests'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Join Requests</h1>
                    <p class="text-sm text-gray-500 mt-1">Review and manage student requests to join your classes</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    @foreach ([
                        ['label' => 'Pending', 'value' => $requestStats['pending'], 'icon' => 'ph-calendar-blank', 'iconClass' => 'bg-orange-100 text-orange-600'],
                        ['label' => 'Approved', 'value' => $requestStats['approved'], 'icon' => 'ph-check', 'iconClass' => 'bg-emerald-100 text-emerald-600'],
                        ['label' => 'Rejected', 'value' => $requestStats['rejected'], 'icon' => 'ph-x', 'iconClass' => 'bg-red-100 text-red-500'],
                        ['label' => 'Total', 'value' => $requestStats['total'], 'icon' => 'ph-user-focus', 'iconClass' => 'bg-blue-100 text-blue-600'],
                    ] as $stat)
                        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700">{{ $stat['label'] }}</span>
                                <span class="w-10 h-10 rounded-xl {{ $stat['iconClass'] }} flex items-center justify-center">
                                    <i class="ph {{ $stat['icon'] }} text-xl"></i>
                                </span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900 mt-3">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="tab" value="requests">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                        <input
                            type="search"
                            name="request_q"
                            value="{{ $requestSearch }}"
                            maxlength="100"
                            placeholder="Search by student name, ID, email, or class..."
                            class="w-full h-12 pl-12 pr-4 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:border-[#0e5c3a]"
                        >
                    </div>
                    <div class="relative md:w-44">
                        <i class="ph ph-funnel absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400 pointer-events-none"></i>
                        <select
                            name="request_status"
                            onchange="this.form.submit()"
                            class="w-full h-12 pl-12 pr-9 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 appearance-none focus:outline-none focus:border-[#0e5c3a]"
                        >
                            <option value="pending" @selected($requestStatus === 'pending')>Pending</option>
                            <option value="active" @selected($requestStatus === 'active')>Approved</option>
                            <option value="rejected" @selected($requestStatus === 'rejected')>Rejected</option>
                            <option value="all" @selected($requestStatus === 'all')>All requests</option>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                    </div>
                </form>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    @forelse ($classJoinRequests as $joinRequest)
                        <article class="p-6 border-b border-gray-100 last:border-b-0 flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-[#0e5c3a] font-bold flex items-center justify-center flex-shrink-0">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($joinRequest->student->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-bold text-gray-900 text-sm">{{ $joinRequest->student->name }}</h2>
                                        <span @class([
                                            'px-2.5 py-1 rounded-full text-[9px] font-bold uppercase',
                                            'bg-orange-50 text-orange-700' => $joinRequest->status === 'pending',
                                            'bg-emerald-50 text-emerald-700' => $joinRequest->status === 'active',
                                            'bg-red-50 text-red-700' => $joinRequest->status === 'rejected',
                                        ])>
                                            {{ $joinRequest->status === 'active' ? 'Approved' : $joinRequest->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-1">{{ $joinRequest->student->email }}</p>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-[11px] text-gray-500">
                                        <span class="font-semibold text-[#0e5c3a]">{{ $joinRequest->researchClass->name }}</span>
                                        @if ($joinRequest->student->student_id)
                                            <span>ID: {{ $joinRequest->student->student_id }}</span>
                                        @endif
                                        @if ($joinRequest->student->program)
                                            <span>{{ $joinRequest->student->program }}</span>
                                        @endif
                                        <span>Requested {{ $joinRequest->requested_at?->diffForHumans() ?? $joinRequest->created_at->diffForHumans() }}</span>
                                        @if ($joinRequest->reviewed_at)
                                            <span>Reviewed {{ $joinRequest->reviewed_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0">
                                @if ($joinRequest->status === 'pending')
                                    <form method="POST" action="{{ route('adviser.classes.join-requests.reject', [$joinRequest->researchClass, $joinRequest]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-4 py-2.5 border border-red-200 text-red-700 hover:bg-red-50 text-xs font-bold rounded-xl">
                                            Reject
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('adviser.classes.join-requests.approve', [$joinRequest->researchClass, $joinRequest]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                                            Approve
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500">
                                        {{ $joinRequest->status === 'active' ? 'Student enrolled' : 'Request declined' }}
                                    </span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="min-h-60 p-12 flex flex-col items-center justify-center text-center">
                            <i class="ph ph-user-focus text-6xl text-gray-300"></i>
                            <h2 class="font-bold text-gray-900 mt-4">No requests found</h2>
                            <p class="text-sm text-gray-500 mt-2">
                                @if ($requestSearch !== '')
                                    No join requests match your search.
                                @elseif ($requestStatus === 'pending')
                                    Student join requests will appear here.
                                @else
                                    There are no {{ $requestStatus === 'active' ? 'approved' : $requestStatus }} requests.
                                @endif
                            </p>
                        </div>
                    @endforelse

                    @if ($classJoinRequests->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $classJoinRequests->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- TAB: Document Review -->
            <div x-show="activeTab === 'docreview'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Document Review System</h1>
                    <p class="text-sm text-gray-500 mt-1">Review and annotate documents submitted by your assigned researchers</p>
                </div>

                <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="tab" value="docreview">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                        <input
                            type="search"
                            name="document_q"
                            value="{{ $documentReviewSearch }}"
                            maxlength="100"
                            placeholder="Search by document, student, email, or student ID..."
                            class="w-full h-12 pl-12 pr-4 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:border-[#0e5c3a]"
                        >
                    </div>
                    <div class="relative md:w-52">
                        <i class="ph ph-funnel absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400 pointer-events-none"></i>
                        <select
                            name="document_status"
                            onchange="this.form.submit()"
                            class="w-full h-12 pl-12 pr-9 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 appearance-none focus:outline-none focus:border-[#0e5c3a]"
                        >
                            <option value="pending" @selected($documentReviewStatus === 'pending')>Pending</option>
                            <option value="under_review" @selected($documentReviewStatus === 'under_review')>Under review</option>
                            <option value="revision_requested" @selected($documentReviewStatus === 'revision_requested')>Revisions requested</option>
                            <option value="accepted" @selected($documentReviewStatus === 'accepted')>Approved</option>
                            <option value="rejected" @selected($documentReviewStatus === 'rejected')>Rejected</option>
                            <option value="all" @selected($documentReviewStatus === 'all')>All documents</option>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                    </div>
                </form>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    @forelse ($reviewDocuments as $reviewDocument)
                        <a
                            href="{{ route('adviser.dashboard', ['tab' => 'docreview', 'document_id' => $reviewDocument->id, 'document_status' => $documentReviewStatus, 'document_q' => $documentReviewSearch]) }}"
                            wire:navigate
                            @class([
                                'bg-white rounded-2xl p-4 border shadow-sm flex items-start gap-3 transition-all',
                                'border-[#0e5c3a] ring-2 ring-[#0e5c3a]/10' => $selectedReviewDocument?->is($reviewDocument),
                                'border-gray-100 hover:border-[#0e5c3a]/30' => ! $selectedReviewDocument?->is($reviewDocument),
                            ])
                        >
                            <span class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                                <i class="ph ph-file-text text-xl"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="font-bold text-sm text-gray-900 block truncate">{{ $reviewDocument->original_filename }}</span>
                                <span class="text-[11px] text-gray-500 block truncate mt-1">{{ $reviewDocument->user->name }}</span>
                                <span class="text-[10px] text-gray-400 block mt-1">
                                    {{ \Illuminate\Support\Str::headline($reviewDocument->status->value) }}
                                    · {{ $reviewDocument->submitted_at->diffForHumans() }}
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="md:col-span-2 xl:col-span-4 bg-white rounded-2xl p-10 border border-gray-100 text-center text-sm text-gray-500">
                            No assigned documents match this filter.
                        </div>
                    @endforelse
                </div>

                @if ($reviewDocuments->hasPages())
                    <div>{{ $reviewDocuments->links() }}</div>
                @endif

                @if ($selectedReviewDocument)
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                        <div class="flex items-center gap-4 min-w-0">
                            <span class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center flex-shrink-0">
                                <i class="ph ph-file-text text-3xl"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-bold text-lg text-gray-900 truncate">{{ $selectedReviewDocument->original_filename }}</h2>
                                <p class="text-sm text-gray-600 mt-1">{{ $selectedReviewDocument->user->name }}</p>
                                <p class="text-xs text-gray-500 mt-2">
                                    Uploaded {{ $selectedReviewDocument->submitted_at->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}
                                    · {{ $selectedReviewDocument->formattedFileSize() }}
                                    · {{ \Illuminate\Support\Str::headline($selectedReviewDocument->status->value) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3 flex-shrink-0">
                            <a href="{{ route('documents.download', $selectedReviewDocument) }}" class="px-4 py-2.5 bg-[#0e9f6e] text-white text-xs font-bold rounded-xl flex items-center gap-2">
                                <i class="ph ph-download-simple"></i>
                                Download
                            </a>
                            <a href="{{ route('documents.view', $selectedReviewDocument) }}" target="_blank" rel="noopener" class="px-4 py-2.5 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl">
                                View Full Document
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                        @foreach ([
                            ['label' => 'Approved', 'value' => $documentReviewStats['approved'], 'icon' => 'ph-check-circle', 'class' => 'border-emerald-500 text-emerald-600'],
                            ['label' => 'Revisions', 'value' => $documentReviewStats['revisions'], 'icon' => 'ph-warning', 'class' => 'border-amber-500 text-amber-600'],
                            ['label' => 'Comments', 'value' => $documentReviewStats['comments'], 'icon' => 'ph-chat', 'class' => 'border-blue-500 text-blue-600'],
                            ['label' => 'Critical', 'value' => $documentReviewStats['critical'], 'icon' => 'ph-x-circle', 'class' => 'border-red-500 text-red-600'],
                        ] as $stat)
                            <div class="bg-white rounded-2xl p-5 border-l-4 {{ $stat['class'] }} shadow-sm flex items-center gap-4">
                                <i class="ph {{ $stat['icon'] }} text-3xl"></i>
                                <div>
                                    <span class="text-xs text-gray-500 block">{{ $stat['label'] }}</span>
                                    <span class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)] gap-6">
                        <section class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            <h2 class="font-bold text-gray-900 mb-5">Document Preview</h2>
                            @if ($selectedReviewDocument->file_type === 'pdf')
                                <iframe
                                    src="{{ route('documents.view', $selectedReviewDocument) }}"
                                    title="Secure preview of {{ $selectedReviewDocument->original_filename }}"
                                    class="w-full min-h-[650px] rounded-xl border border-gray-200 bg-gray-50"
                                ></iframe>
                            @else
                                <div class="min-h-[500px] rounded-xl bg-gray-50 border border-gray-200 flex flex-col items-center justify-center text-center p-8">
                                    <i class="ph ph-file-doc text-6xl text-blue-500"></i>
                                    <h3 class="font-bold text-gray-900 mt-4">Word document preview</h3>
                                    <p class="text-sm text-gray-500 mt-2 max-w-md">For security and formatting accuracy, download or open the DOCX file using the authorized controls above.</p>
                                </div>
                            @endif
                        </section>

                        <aside class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm self-start">
                            <h2 class="font-bold text-gray-900 mb-5">Comments &amp; Feedback</h2>
                            <div class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
                                @forelse ($documentReviewComments as $comment)
                                    <article @class([
                                        'rounded-xl border-l-4 p-4',
                                        'bg-gray-50 border-gray-400' => $comment->severity === 'comment',
                                        'bg-amber-50 border-amber-500' => $comment->severity === 'revision',
                                        'bg-red-50 border-red-500' => $comment->severity === 'critical',
                                        'opacity-60' => $comment->resolved_at !== null,
                                    ])>
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 class="font-bold text-sm text-gray-900">{{ $comment->author->name }}</h3>
                                                <p class="text-[10px] text-gray-500">{{ \Illuminate\Support\Str::headline($comment->severity) }}</p>
                                            </div>
                                            <span class="text-[10px] text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-gray-700 mt-3 whitespace-pre-line">{{ $comment->comment }}</p>
                                        <div class="flex items-center justify-between mt-3">
                                            <span class="text-[10px] text-gray-500">
                                                {{ $comment->page_number ? 'Page '.$comment->page_number : 'General comment' }}
                                            </span>
                                            @if ($comment->resolved_at === null)
                                                <form method="POST" action="{{ route('adviser.documents.comments.resolve', [$selectedReviewDocument, $comment]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-[11px] font-bold text-blue-700">Resolve</button>
                                                </form>
                                            @else
                                                <span class="text-[10px] font-bold text-emerald-700">Resolved</span>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <p class="text-sm text-gray-500 text-center py-8">No review comments yet.</p>
                                @endforelse
                            </div>

                            <form method="POST" action="{{ route('adviser.documents.comments.store', $selectedReviewDocument) }}" class="mt-5 pt-5 border-t border-gray-100 space-y-3">
                                @csrf
                                <div class="grid grid-cols-2 gap-3">
                                    <select name="severity" required class="px-3 py-2 border border-gray-200 rounded-xl text-xs">
                                        <option value="comment">Comment</option>
                                        <option value="revision">Revision</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                    <input type="number" name="page_number" min="1" max="10000" placeholder="Page (optional)" class="px-3 py-2 border border-gray-200 rounded-xl text-xs">
                                </div>
                                <textarea name="comment" rows="4" minlength="2" maxlength="5000" required placeholder="Add a comment..." class="w-full px-3 py-3 border border-gray-200 rounded-xl text-sm resize-none"></textarea>
                                <button type="submit" class="w-full py-3 bg-[#0e9f6e] text-white text-xs font-bold rounded-xl">Post Comment</button>
                            </form>
                        </aside>
                    </div>

                    @if (in_array($selectedReviewDocument->status->value, ['pending', 'submitted', 'under_review'], true))
                        <form method="POST" action="{{ route('adviser.documents.review', $selectedReviewDocument) }}" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            @csrf
                            @method('PATCH')
                            <h2 class="font-bold text-gray-900">Review Actions</h2>
                            <textarea
                                name="review_notes"
                                rows="3"
                                maxlength="10000"
                                placeholder="Review notes are required when requesting revisions or rejecting a document."
                                class="w-full mt-4 px-4 py-3 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:border-[#0e5c3a]"
                            ></textarea>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <button type="submit" name="decision" value="accepted" class="py-3 bg-[#0e9f6e] text-white text-sm font-bold rounded-xl">
                                    <i class="ph ph-check-circle mr-1"></i> Approve Document
                                </button>
                                <button type="submit" name="decision" value="revision_requested" class="py-3 bg-amber-500 text-white text-sm font-bold rounded-xl">
                                    <i class="ph ph-warning mr-1"></i> Request Revisions
                                </button>
                                <button type="submit" name="decision" value="rejected" class="py-3 bg-red-600 text-white text-sm font-bold rounded-xl">
                                    <i class="ph ph-x-circle mr-1"></i> Reject Document
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            <p class="text-sm text-gray-600">
                                Final decision:
                                <span class="font-bold text-gray-900">{{ \Illuminate\Support\Str::headline($selectedReviewDocument->status->value) }}</span>
                            </p>
                        </div>
                    @endif
                @endif
            </div>

            <!-- TAB: Revision Management -->
            <div x-show="activeTab === 'revisions'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Revision Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Track requested changes and review revised student documents</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                    @foreach ([
                        ['label' => 'Open', 'value' => $revisionStats['open']],
                        ['label' => 'In Progress', 'value' => $revisionStats['in_progress']],
                        ['label' => 'Submitted', 'value' => $revisionStats['submitted']],
                        ['label' => 'Resolved', 'value' => $revisionStats['resolved']],
                        ['label' => 'Total', 'value' => $revisionStats['total']],
                    ] as $stat)
                        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                            <p class="text-xs font-semibold text-gray-500">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="tab" value="revisions">
                    <input
                        type="search"
                        name="revision_q"
                        value="{{ $revisionSearch }}"
                        maxlength="100"
                        placeholder="Search by student, ID, email, or revision title..."
                        class="flex-1 h-12 px-4 rounded-2xl border border-gray-200 bg-white text-sm focus:outline-none focus:border-[#0e5c3a]"
                    >
                    <select
                        name="revision_status"
                        onchange="this.form.submit()"
                        class="md:w-48 h-12 px-4 rounded-2xl border border-gray-200 bg-white text-sm focus:outline-none focus:border-[#0e5c3a]"
                    >
                        <option value="submitted" @selected($revisionStatus === 'submitted')>Submitted</option>
                        <option value="open" @selected($revisionStatus === 'open')>Open</option>
                        <option value="in_progress" @selected($revisionStatus === 'in_progress')>In progress</option>
                        <option value="resolved" @selected($revisionStatus === 'resolved')>Resolved</option>
                        <option value="all" @selected($revisionStatus === 'all')>All requests</option>
                    </select>
                </form>

                <div class="space-y-4">
                    @forelse ($revisionRequests as $revisionRequest)
                        @php
                            $latestRevisionDocument = $revisionRequest->submittedDocuments->first();
                        @endphp
                        <article class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-5">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-bold text-gray-900">{{ $revisionRequest->title }}</h2>
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-[9px] font-bold uppercase">
                                            {{ \Illuminate\Support\Str::headline($revisionRequest->status->value) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2">
                                        {{ $revisionRequest->assignee->name }}
                                        @if ($revisionRequest->assignee->student_id)
                                            · {{ $revisionRequest->assignee->student_id }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 leading-6 mt-3">{{ $revisionRequest->instructions }}</p>

                                    @if ($latestRevisionDocument)
                                        <div class="flex flex-wrap items-center gap-3 mt-4">
                                            <span class="text-xs font-semibold text-gray-700">{{ $latestRevisionDocument->original_filename }}</span>
                                            <a href="{{ route('documents.view', $latestRevisionDocument) }}" class="text-xs font-bold text-[#0e5c3a]">View</a>
                                            <a href="{{ route('documents.download', $latestRevisionDocument) }}" class="text-xs font-bold text-[#0e5c3a]">Download</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col gap-2 xl:w-64">
                                    @if ($revisionRequest->status->value === 'submitted')
                                        <form method="POST" action="{{ route('adviser.revisions.resolve', $revisionRequest) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="notes" maxlength="5000" placeholder="Resolution note (optional)" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs mb-2">
                                            <button type="submit" class="w-full px-4 py-2.5 bg-[#0e9f6e] text-white text-xs font-bold rounded-xl">Resolve Revision</button>
                                        </form>
                                    @endif

                                    @if (in_array($revisionRequest->status->value, ['submitted', 'resolved'], true))
                                        <form method="POST" action="{{ route('adviser.revisions.reopen', $revisionRequest) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="notes" maxlength="5000" placeholder="Reason for reopening (optional)" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs mb-2">
                                            <button type="submit" class="w-full px-4 py-2.5 bg-amber-500 text-white text-xs font-bold rounded-xl">Reopen Revision</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center text-sm text-gray-500">
                            No revision requests found.
                        </div>
                    @endforelse
                </div>

                {{ $revisionRequests->links() }}
            </div>

            <!-- TAB: Consultation Records -->
            <div x-show="activeTab === 'consultation'" x-cloak class="space-y-6">
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Consultation Records</h1>
                    <p class="text-sm text-gray-500 mt-1">Review requests and monitor consultations for your assigned researchers</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                    @foreach ([
                        ['label' => 'Pending', 'value' => $consultationStats['pending'], 'icon' => 'ph-clock', 'iconClass' => 'bg-orange-100 text-orange-600'],
                        ['label' => 'Approved', 'value' => $consultationStats['approved'], 'icon' => 'ph-check', 'iconClass' => 'bg-emerald-100 text-emerald-600'],
                        ['label' => 'Completed', 'value' => $consultationStats['completed'], 'icon' => 'ph-check-circle', 'iconClass' => 'bg-blue-100 text-blue-600'],
                        ['label' => 'Rejected', 'value' => $consultationStats['rejected'], 'icon' => 'ph-x', 'iconClass' => 'bg-red-100 text-red-500'],
                        ['label' => 'Total', 'value' => $consultationStats['total'], 'icon' => 'ph-chat-circle-dots', 'iconClass' => 'bg-purple-100 text-purple-600'],
                    ] as $stat)
                        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700">{{ $stat['label'] }}</span>
                                <span class="w-10 h-10 rounded-xl {{ $stat['iconClass'] }} flex items-center justify-center">
                                    <i class="ph {{ $stat['icon'] }} text-xl"></i>
                                </span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900 mt-3">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-col md:flex-row gap-4">
                    <input type="hidden" name="tab" value="consultation">
                    <div class="relative flex-1">
                        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                        <input
                            type="search"
                            name="consultation_q"
                            value="{{ $consultationSearch }}"
                            maxlength="100"
                            placeholder="Search by student, research title, or agenda..."
                            class="w-full h-12 pl-12 pr-4 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:border-[#0e5c3a]"
                        >
                    </div>
                    <div class="relative md:w-44">
                        <i class="ph ph-funnel absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400 pointer-events-none"></i>
                        <select
                            name="consultation_status"
                            onchange="this.form.submit()"
                            class="w-full h-12 pl-12 pr-9 rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 appearance-none focus:outline-none focus:border-[#0e5c3a]"
                        >
                            <option value="pending" @selected($consultationStatus === 'pending')>Pending</option>
                            <option value="approved" @selected($consultationStatus === 'approved')>Approved</option>
                            <option value="completed" @selected($consultationStatus === 'completed')>Completed</option>
                            <option value="rejected" @selected($consultationStatus === 'rejected')>Rejected</option>
                            <option value="all" @selected($consultationStatus === 'all')>All requests</option>
                        </select>
                        <i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                    </div>
                </form>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Consultation Requests</h2>
                    </div>

                    @forelse ($consultationRequests as $consultationRequest)
                        <article class="p-6 border-b border-gray-100 last:border-b-0 space-y-4">
                            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-gray-900">{{ $consultationRequest->student_name ?: 'Student researcher' }}</h3>
                                        <span @class([
                                            'px-2.5 py-1 rounded-full text-[9px] font-bold uppercase',
                                            'bg-orange-50 text-orange-700' => $consultationRequest->status === 'pending',
                                            'bg-emerald-50 text-emerald-700' => $consultationRequest->status === 'approved',
                                            'bg-blue-50 text-blue-700' => $consultationRequest->status === 'completed',
                                            'bg-red-50 text-red-700' => $consultationRequest->status === 'rejected',
                                        ])>
                                            {{ $consultationRequest->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ $consultationRequest->research_title ?: 'Research project' }}</p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500 mt-3">
                                        <span>
                                            <i class="ph ph-calendar-blank mr-1"></i>
                                            {{ $consultationRequest->preferred_at->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}
                                        </span>
                                        <span>
                                            <i class="ph ph-video-camera mr-1"></i>
                                            {{ \Illuminate\Support\Str::headline($consultationRequest->consultation_mode) }}
                                        </span>
                                        @if ($consultationRequest->student_email)
                                            <span>{{ $consultationRequest->student_email }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-700 mt-3 whitespace-pre-line">{{ $consultationRequest->agenda }}</p>

                                    @if ($consultationRequest->review_notes)
                                        <p class="text-xs text-gray-500 mt-3">
                                            <span class="font-bold text-gray-700">Adviser note:</span>
                                            {{ $consultationRequest->review_notes }}
                                        </p>
                                    @endif
                                </div>

                                @if ($consultationRequest->status === 'pending')
                                    <div class="flex flex-col sm:flex-row gap-3 lg:w-auto">
                                        <form method="POST" action="{{ route('adviser.consultations.reject', $consultationRequest->id) }}" class="flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="text"
                                                name="review_notes"
                                                maxlength="2000"
                                                placeholder="Optional reason"
                                                class="w-40 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-red-300"
                                            >
                                            <button type="submit" class="px-4 py-2 border border-red-200 text-red-700 hover:bg-red-50 text-xs font-bold rounded-xl">
                                                Reject
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('adviser.consultations.approve', $consultationRequest->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-full px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                                                Approve
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($consultationRequest->status !== 'approved')
                                    <p class="text-xs text-gray-500 flex-shrink-0">
                                        Reviewed {{ $consultationRequest->reviewed_at?->diffForHumans() }}
                                    </p>
                                @endif
                            </div>

                            @if ($consultationRequest->status === 'approved')
                                <details class="rounded-xl border border-emerald-100 bg-emerald-50/40">
                                    <summary class="cursor-pointer px-4 py-3 text-xs font-bold text-[#0e5c3a]">
                                        Record completed consultation
                                    </summary>
                                    <form method="POST" action="{{ route('adviser.consultations.complete', $consultationRequest->id) }}" class="p-4 pt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Consulted at</label>
                                            <input
                                                type="datetime-local"
                                                name="consulted_at"
                                                value="{{ now(config('ndmu-rmas.timezone'))->format('Y-m-d\TH:i') }}"
                                                required
                                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Location</label>
                                            <input
                                                type="text"
                                                name="location"
                                                maxlength="255"
                                                placeholder="Room or online"
                                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                            >
                                        </div>
                                        @if ($consultationRequest->consultation_mode === 'online')
                                            <div class="md:col-span-2">
                                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Meeting URL</label>
                                                <input
                                                    type="url"
                                                    name="meeting_url"
                                                    maxlength="2048"
                                                    placeholder="https://..."
                                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                                >
                                            </div>
                                        @endif
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Discussion summary</label>
                                            <textarea
                                                name="discussion"
                                                rows="4"
                                                minlength="10"
                                                maxlength="10000"
                                                required
                                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                            ></textarea>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Recommendations</label>
                                            <textarea
                                                name="recommendations"
                                                rows="3"
                                                maxlength="10000"
                                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                            ></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Next consultation (optional)</label>
                                            <input
                                                type="datetime-local"
                                                name="next_consultation_at"
                                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-[#0e5c3a]"
                                            >
                                        </div>
                                        <div class="flex items-end justify-end">
                                            <button type="submit" class="w-full md:w-auto px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl">
                                                Save Consultation Record
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <div class="min-h-56 p-12 flex flex-col items-center justify-center text-center">
                            <i class="ph ph-chat-circle-dots text-6xl text-gray-300"></i>
                            <h2 class="font-bold text-gray-900 mt-4">No consultation requests found</h2>
                            <p class="text-sm text-gray-500 mt-2">Requests from your assigned researchers will appear here.</p>
                        </div>
                    @endforelse

                    @if ($consultationRequests->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $consultationRequests->links() }}
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="font-bold text-gray-900">Completed Consultation History</h2>
                    </div>

                    @forelse ($consultationRecords as $record)
                        <article class="p-6 border-b border-gray-100 last:border-b-0">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $record->research_title ?: 'Research consultation' }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Illuminate\Support\Carbon::parse($record->consulted_at)->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}
                                        · {{ \Illuminate\Support\Str::headline($record->consultation_mode) }}
                                        @if ($record->location)
                                            · {{ $record->location }}
                                        @endif
                                    </p>
                                </div>
                                @if ($record->next_consultation_at)
                                    <span class="text-xs text-blue-700 bg-blue-50 px-3 py-1.5 rounded-full">
                                        Next: {{ \Illuminate\Support\Carbon::parse($record->next_consultation_at)->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}
                                    </span>
                                @endif
                            </div>
                            @if ($record->agenda)
                                <p class="text-sm text-gray-700 mt-3"><span class="font-bold">Agenda:</span> {{ $record->agenda }}</p>
                            @endif
                            @if ($record->recommendations)
                                <p class="text-sm text-gray-700 mt-2"><span class="font-bold">Recommendations:</span> {{ $record->recommendations }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="p-10 text-center text-sm text-gray-500">
                            No completed consultation records are available yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB: Research Repository -->
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-6 animate-fade-in">
                @if (session('document_success'))
                    <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('document_success') }}
                    </div>
                @endif

                @if ($errors->has('document'))
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first('document') }}
                    </div>
                @endif

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-5">
                    <div>
                        <div class="flex items-center gap-2 text-xs mb-3">
                            <i class="ph ph-book-open text-[#0e5c3a] text-lg"></i>
                            <a href="{{ route('adviser.dashboard', ['tab' => 'dashboard']) }}" class="text-gray-500 hover:text-[#0e5c3a]">Dashboard</a>
                            <span class="text-gray-300">/</span>
                            <span class="font-bold text-[#0e5c3a]">Research Repository</span>
                        </div>
                        <h1 class="text-3xl font-extrabold font-heading text-gray-900 tracking-tight">Research Repository</h1>
                        <p class="text-sm text-gray-500 mt-1">Manage, upload, and track authorized research files.</p>
                    </div>
                    <button
                        type="button"
                        @click="showRepositoryUploadModal = true"
                        class="px-5 py-3 bg-[#0e7050] hover:bg-[#0a5a40] text-white text-sm font-bold rounded-xl shadow-md flex items-center justify-center gap-2 transition-colors"
                    >
                        <i class="ph ph-upload-simple text-lg"></i>
                        Upload Document
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    @foreach ([
                        ['label' => 'Total Files', 'value' => $repositoryStats['total'], 'icon' => 'ph-file-text', 'iconClass' => 'bg-emerald-50 text-[#0e7050]'],
                        ['label' => 'Approved', 'value' => $repositoryStats['approved'], 'icon' => 'ph-check-circle', 'iconClass' => 'bg-green-50 text-green-600'],
                        ['label' => 'Pending Review', 'value' => $repositoryStats['pending'], 'icon' => 'ph-clock', 'iconClass' => 'bg-orange-50 text-orange-600'],
                        ['label' => 'For Evaluation', 'value' => $repositoryStats['evaluation'], 'icon' => 'ph-clipboard-text', 'iconClass' => 'bg-purple-50 text-purple-600'],
                    ] as $repositoryStat)
                        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center gap-4">
                            <span class="w-11 h-11 rounded-xl {{ $repositoryStat['iconClass'] }} flex items-center justify-center">
                                <i class="ph {{ $repositoryStat['icon'] }} text-2xl"></i>
                            </span>
                            <div>
                                <span class="text-2xl font-extrabold text-gray-900">{{ $repositoryStat['value'] }}</span>
                                <span class="block text-xs text-gray-500">{{ $repositoryStat['label'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('adviser.dashboard') }}" class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex flex-col md:flex-row gap-3">
                    <input type="hidden" name="tab" value="repository">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-lg"></i>
                        </span>
                        <input
                            type="search"
                            name="repository_q"
                            value="{{ $repositorySearch }}"
                            maxlength="100"
                            placeholder="Search documents or researcher name..."
                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#0e7050]"
                        >
                    </div>
                    <div class="flex gap-3">
                        <select
                            name="repository_status"
                            onchange="this.form.submit()"
                            class="min-w-44 px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 focus:outline-none focus:border-[#0e7050]"
                        >
                            <option value="all" @selected($repositoryStatus === 'all')>All Status</option>
                            <option value="approved" @selected($repositoryStatus === 'approved')>Approved</option>
                            <option value="pending" @selected($repositoryStatus === 'pending')>Pending Review</option>
                            <option value="evaluation" @selected($repositoryStatus === 'evaluation')>For Evaluation</option>
                            <option value="revisions" @selected($repositoryStatus === 'revisions')>Revisions Requested</option>
                            <option value="rejected" @selected($repositoryStatus === 'rejected')>Rejected</option>
                        </select>
                        <button type="submit" class="px-5 py-3 bg-[#0e5c3a] text-white text-sm font-bold rounded-xl">
                            Search
                        </button>
                    </div>
                </form>

                <div class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-5">
                    @forelse ($repositoryDocuments as $repositoryDocument)
                        @php
                            $repositoryStatusPresentation = match ($repositoryDocument->status) {
                                \App\Enums\DocumentStatus::Accepted => ['label' => 'Approved', 'class' => 'bg-green-50 text-green-700 border-green-200', 'icon' => 'ph-check-circle'],
                                \App\Enums\DocumentStatus::UnderReview => ['label' => 'For Evaluation', 'class' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => 'ph-clock'],
                                \App\Enums\DocumentStatus::RevisionRequested => ['label' => 'Revisions Requested', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'ph-warning'],
                                \App\Enums\DocumentStatus::Rejected => ['label' => 'Rejected', 'class' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'ph-x-circle'],
                                \App\Enums\DocumentStatus::Draft => ['label' => 'Draft', 'class' => 'bg-gray-50 text-gray-700 border-gray-200', 'icon' => 'ph-pencil'],
                                default => ['label' => 'Pending Review', 'class' => 'bg-orange-50 text-orange-700 border-orange-200', 'icon' => 'ph-clock'],
                            };
                            $isPdf = $repositoryDocument->file_type === 'pdf';
                            $documentTitle = pathinfo($repositoryDocument->original_filename, PATHINFO_FILENAME);
                        @endphp

                        <article class="bg-white rounded-2xl border border-gray-100 border-t-4 {{ $isPdf ? 'border-t-red-500' : 'border-t-blue-500' }} shadow-sm p-5 flex flex-col min-h-64">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl {{ $isPdf ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500' }} flex items-center justify-center">
                                        <i class="ph ph-file-text text-2xl"></i>
                                    </span>
                                    <span class="px-2.5 py-1 rounded-md border {{ $isPdf ? 'border-red-200 bg-red-50 text-red-600' : 'border-blue-200 bg-blue-50 text-blue-600' }} text-[10px] font-bold uppercase">
                                        {{ $repositoryDocument->file_type }}
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full border {{ $repositoryStatusPresentation['class'] }} text-[10px] font-semibold flex items-center gap-1">
                                    <i class="ph {{ $repositoryStatusPresentation['icon'] }}"></i>
                                    {{ $repositoryStatusPresentation['label'] }}
                                </span>
                            </div>

                            <div class="mt-5 flex-1 min-w-0">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-[#b38728]">
                                    {{ $isPdf ? 'PDF Document' : 'Word Document' }}
                                </p>
                                <h2 class="text-lg font-extrabold text-gray-900 mt-1 truncate" title="{{ $repositoryDocument->original_filename }}">
                                    {{ $documentTitle }}
                                </h2>
                                <p class="text-xs text-gray-500 mt-2 line-clamp-2">
                                    {{ $repositoryDocument->user->program ?: $repositoryDocument->user->email }}
                                </p>
                                <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-400 mt-5">
                                    <span>{{ $repositoryDocument->formattedFileSize() }}</span>
                                    <span>•</span>
                                    <span>{{ $repositoryDocument->submitted_at->timezone(config('ndmu-rmas.timezone'))->format('M j, Y') }}</span>
                                    <span>•</span>
                                    <span class="truncate max-w-40">
                                        {{ $repositoryDocument->user_id === $adviser->getKey() ? 'You' : $repositoryDocument->user->name }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-4 mt-4 border-t border-gray-100">
                                <a
                                    href="{{ route('documents.view', $repositoryDocument) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="py-2.5 rounded-xl bg-emerald-50 text-[#0e7050] text-xs font-bold text-center flex items-center justify-center gap-2"
                                >
                                    <i class="ph ph-eye"></i>
                                    View
                                </a>
                                <a
                                    href="{{ route('documents.download', $repositoryDocument) }}"
                                    class="py-2.5 rounded-xl bg-blue-50 text-blue-600 text-xs font-bold text-center flex items-center justify-center gap-2"
                                >
                                    <i class="ph ph-download-simple"></i>
                                    Download
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="lg:col-span-2 2xl:col-span-3 min-h-72 bg-white rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center justify-center text-center p-8">
                            <i class="ph ph-folder-open text-6xl text-gray-300"></i>
                            <h2 class="font-bold text-gray-900 mt-4">No repository documents found</h2>
                            <p class="text-sm text-gray-500 mt-2">Authorized uploads from you and your assigned researchers will appear here.</p>
                        </div>
                    @endforelse
                </div>

                @if ($repositoryDocuments->hasPages())
                    <div>
                        {{ $repositoryDocuments->links() }}
                    </div>
                @endif
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings', [
                    'avatarInitials' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($adviser->name, 0, 1)),
                    'userName' => $adviser->name,
                    'emailAddress' => $adviser->email,
                    'userRole' => 'Research Adviser',
                    'userRoleBadge' => 'RESEARCH ADVISER',
                    'department' => $adviser->department ?: 'Not assigned',
                    'userId' => $adviser->student_id ?: 'Not assigned',
                    'portalType' => 'Faculty Portal',
                    'accessLevel' => 'Faculty & Guidance Access'
                ])
            </div>

            <!-- TAB: Assigned Researchers (User Management) -->
            <div x-show="activeTab === 'researchers'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">User Management</h1>
                    <p class="text-xs text-gray-455 mt-1">Manage accounts, approve registrations, and create staff users</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Users -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-slate-100/80 text-slate-655 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-users"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.length">26</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Total Users</span>
                        </div>
                    </div>

                    <!-- Pending Approval -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.filter(u => u.status === 'Pending').length">5</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Pending Approval</span>
                        </div>
                    </div>

                    <!-- Active Accounts -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.filter(u => u.status === 'Active').length">21</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Active Accounts</span>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-red-50 text-red-505 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block">0</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Rejected</span>
                        </div>
                    </div>
                </div>

                <!-- Subtabs navigation -->
                <div class="flex items-center gap-6 border-b border-gray-200 pb-1">
                    <button 
                        @click="managementSubTab = 'all'" 
                        :class="managementSubTab === 'all' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold border-b-2 pb-3' : 'text-gray-500 hover:text-gray-800 pb-3'" 
                        class="text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                    >
                        <span>All Users</span>
                        <span :class="managementSubTab === 'all' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-100 text-gray-500'" class="rounded-full px-2 py-0.5 text-[9px] font-bold" x-text="managementUsers.length">26</span>
                    </button>
                    
                    <button 
                        @click="managementSubTab = 'pending'" 
                        :class="managementSubTab === 'pending' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold border-b-2 pb-3' : 'text-gray-500 hover:text-gray-800 pb-3'" 
                        class="text-xs font-semibold flex items-center gap-2 transition-all cursor-pointer"
                    >
                        <span>Pending Students</span>
                        <span :class="managementSubTab === 'pending' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-100 text-gray-500'" class="rounded-full px-2 py-0.5 text-[9px] font-bold" x-text="managementUsers.filter(u => u.status === 'Pending').length">5</span>
                    </button>
                    
                    <button 
                        @click="managementSubTab = 'create'" 
                        :class="managementSubTab === 'create' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold border-b-2 pb-3' : 'text-gray-500 hover:text-gray-800 pb-3'" 
                        class="text-xs font-semibold transition-all cursor-pointer"
                    >
                        Create User
                    </button>
                </div>

                <!-- Tab views -->
                <div class="space-y-6">
                    <!-- Tab: All Users & Pending Students Table -->
                    <div x-show="managementSubTab !== 'create'" class="space-y-6">
                        <!-- Filters row -->
                        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                            <!-- Search -->
                            <div class="relative w-full md:w-80">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                                    <i class="ph ph-magnifying-glass text-sm"></i>
                                </span>
                                <input
                                    type="text"
                                    x-model="userSearchQuery"
                                    placeholder="Search by name or email..."
                                    class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                                >
                            </div>

                            <!-- Right filters -->
                            <div class="flex items-center gap-3 w-full md:w-auto">
                                <select 
                                    x-model="userRoleFilter" 
                                    class="w-full md:w-44 px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-xs text-gray-850 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                                    style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em auto; padding-right: 2.25rem;"
                                >
                                    <option value="all">All Roles</option>
                                    <option value="administrator">Administrator</option>
                                    <option value="college dean">College Dean</option>
                                    <option value="research facilitator">Research Facilitator</option>
                                    <option value="research adviser">Research Adviser</option>
                                    <option value="student researcher">Student Researcher</option>
                                </select>

                                <button @click="userSearchQuery = ''; userRoleFilter = 'all';" class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-sm transition-all cursor-pointer">
                                    <i class="ph ph-arrows-clockwise text-base"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Users table card -->
                        <div class="bg-white rounded-3xl border border-gray-100/50 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-400 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                                            <th class="px-6 py-4">Name</th>
                                            <th class="px-6 py-4">Email</th>
                                            <th class="px-6 py-4">Role</th>
                                            <th class="px-6 py-4">Status</th>
                                            <th class="px-6 py-4">Department</th>
                                            <th class="px-6 py-4">Created</th>
                                            <th class="px-6 py-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 text-gray-700">
                                        <template x-for="user in filteredUsers()" :key="user.email">
                                            <tr class="hover:bg-gray-50/30 transition-colors">
                                                <!-- Name with initial badge -->
                                                <td class="px-6 py-4 flex items-center gap-3">
                                                    <div :class="user.initialsBg" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0" x-text="user.initials">S</div>
                                                    <div class="leading-tight">
                                                        <span class="font-bold text-gray-850 block" x-text="user.name">User Name</span>
                                                        <template x-if="user.hasTempPw">
                                                            <span class="bg-amber-100 text-amber-800 text-[9px] px-1.5 py-0.5 rounded font-bold mt-1 inline-block">Temp password</span>
                                                        </template>
                                                    </div>
                                                </td>
                                                <!-- Email -->
                                                <td class="px-6 py-4 text-gray-600 font-medium" x-text="user.email">email@ndmu.edu.ph</td>
                                                <!-- Role badge -->
                                                <td class="px-6 py-4">
                                                    <span :class="user.roleClass" class="px-2.5 py-0.5 rounded text-[10px] font-bold" x-text="user.role">Role</span>
                                                </td>
                                                <!-- Status -->
                                                <td class="px-6 py-4">
                                                    <span :class="user.status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" class="px-2.5 py-0.5 rounded text-[10px] font-bold" x-text="user.status">Active</span>
                                                </td>
                                                <!-- Department -->
                                                <td class="px-6 py-4 text-gray-500 font-semibold truncate max-w-[180px]" x-text="user.dept">Department</td>
                                                <!-- Created -->
                                                <td class="px-6 py-4 text-gray-400 font-medium" x-text="user.date">2024-01-01</td>
                                                <!-- Actions -->
                                                <td class="px-6 py-4">
                                                    <template x-if="user.isSystem">
                                                        <span class="text-gray-400 font-medium text-[11px]">System</span>
                                                    </template>
                                                    <template x-if="!user.isSystem">
                                                        <button @click="if (confirm(`Are you sure you want to delete ${user.name}?`)) { managementUsers = managementUsers.filter(item => item.email !== user.email); }" class="text-red-500 hover:text-red-750 transition-colors cursor-pointer">
                                                            <i class="ph ph-trash text-lg"></i>
                                                        </button>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="filteredUsers().length === 0">
                                            <tr>
                                                <td colspan="7" class="px-6 py-12 text-center text-gray-450 font-medium">
                                                    No users found matching current filters.
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Create User Form -->
                    <div x-show="managementSubTab === 'create'" class="bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 md:p-8 space-y-6">
                        <div class="border-b border-gray-100 pb-4">
                            <h3 class="text-base font-bold text-gray-800 font-heading">Register New User</h3>
                            <p class="text-xs text-gray-455 mt-1">Add a new academic evaluator, facilitator, advisor, or student researcher.</p>
                        </div>
                        
                        <form x-ref="createForm" @submit.prevent="
                            const name = $refs.newName.value.trim();
                            const email = $refs.newEmail.value.trim();
                            const role = $refs.newRole.value;
                            const dept = $refs.newDept.value;
                            
                            if (!name || !email || !role || !dept) {
                                alert('Please fill in all fields.');
                                return;
                            }
                            
                            let roleClass = 'bg-emerald-50 border border-emerald-100 text-emerald-700';
                            if (role === 'Administrator') roleClass = 'bg-blue-50 border border-blue-100 text-blue-700';
                            else if (role === 'Student Researcher') roleClass = 'bg-gray-50 border border-gray-100 text-gray-700';

                            managementUsers.unshift({
                                name: name,
                                initials: name.charAt(0).toUpperCase(),
                                initialsBg: 'bg-[#0f766e] text-white',
                                email: email,
                                role: role,
                                roleClass: roleClass,
                                status: 'Active',
                                dept: dept,
                                date: new Date().toISOString().split('T')[0],
                                isSystem: false,
                                hasTempPw: true
                            });
                            
                            $refs.createForm.reset();
                            managementSubTab = 'all';
                            alert('Account successfully registered with temporary password!');
                        " class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl">
                            <!-- Full Name -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Full Name</label>
                                <input
                                    x-ref="newName"
                                    type="text"
                                    placeholder="e.g. Dr. Miguel Torres"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-850 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                                >
                            </div>

                            <!-- Email Address -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                                <input
                                    x-ref="newEmail"
                                    type="email"
                                    placeholder="e.g. m.torres@ndmu.edu.ph"
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-855 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                                >
                            </div>

                            <!-- Role Select -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wider block">System Role</label>
                                <select 
                                    x-ref="newRole" 
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-850 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                                    style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.25em auto;"
                                >
                                    <option value="" disabled selected>Select system role</option>
                                    <option value="Research Adviser">Research Adviser</option>
                                    <option value="Research Facilitator">Research Facilitator</option>
                                    <option value="Panelist">Panelist</option>
                                    <option value="Student Researcher">Student Researcher</option>
                                </select>
                            </div>

                            <!-- Department/College -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Department / college</label>
                                <select 
                                    x-ref="newDept" 
                                    required
                                    class="w-full px-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-850 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                                    style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.25em auto;"
                                >
                                    <option value="" disabled selected>Select department</option>
                                    <option value="College of Information Technology">College of Information Technology</option>
                                    <option value="College of Engineering">College of Engineering</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Office of the College Dean">Office of the College Dean</option>
                                </select>
                            </div>

                            <!-- Submit button -->
                            <div class="md:col-span-2 pt-2">
                                <button type="submit" class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl shadow-lg shadow-[#0e5c3a]/15 transition-all cursor-pointer">
                                    Create User Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB: Proposal Review -->
            <div x-show="activeTab === 'proposal'" x-cloak class="space-y-8 animate-fade-in">
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
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="proposalProposals.filter(p => p.status === 'Revisions').length">0</span>
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
                                        <p class="text-[11px] text-gray-500 font-medium mt-1.5" x-text="`Proposal ID: ${p.id}`">Proposal ID: PROP-2026-001</p>
                                        <p class="text-[11px] text-gray-500 font-medium mt-0.5" x-text="`Submitted: ${p.submitted}`">Submitted: March 5, 2026</p>
                                    </div>
                                    <span :class="p.statusClass" class="flex-shrink-0 self-start animate-pulse" x-text="p.status">Approved</span>
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
                                    <button @click="alert(`Downloading PDF for: ${p.title}`)" class="px-5 py-2.5 bg-white border border-gray-250 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
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

            <!-- TAB: Research Monitoring -->
            <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Research Lifecycle Tracker</h1>
                    <p class="text-xs text-gray-455 mt-1">Track your research progress through each milestone</p>
                </div>

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-sm font-bold text-gray-800 block">Overall Progress</span>
                            <span class="text-xs text-gray-455 mt-1 block">Machine Learning Applications in Agricultural Pest Detection</span>
                        </div>
                        <span class="text-xl font-black text-emerald-600 tracking-tight">42% Complete</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: 42%;"></div>
                    </div>

                    <!-- Milestone Counts Grid -->
                    <div class="grid grid-cols-3 gap-4 text-center pt-2">
                        <div>
                            <span class="text-xl font-bold text-emerald-600 block" x-text="monitoringMilestones.filter(m => m.status === 'Completed').length">6</span>
                            <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">Completed</span>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-amber-500 block" x-text="monitoringMilestones.filter(m => m.status === 'In Progress').length">2</span>
                            <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">In Progress</span>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-gray-400 block" x-text="monitoringMilestones.filter(m => m.status === 'Pending').length">4</span>
                            <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider block mt-0.5">Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Milestones Timeline Wrapper -->
                <div class="bg-white rounded-3xl border border-gray-100/50 shadow-sm p-6 md:p-8 space-y-6">
                    <h2 class="text-sm font-bold text-gray-855 font-heading tracking-wide">Research Milestones</h2>

                    <!-- Timeline Vertical line track -->
                    <div class="relative pl-12 md:pl-16 space-y-8">
                        <div class="absolute left-6 md:left-8 top-3 bottom-3 w-0.5 bg-gray-150 -translate-x-1/2"></div>

                        <template x-for="(m, idx) in monitoringMilestones" :key="idx">
                            <div class="relative flex flex-col md:flex-row items-start gap-4">
                                <!-- Bullet Circle -->
                                <div class="absolute left-[-24px] md:left-[-32px] top-1.5 w-8 h-8 rounded-full border-4 border-white shadow-sm flex items-center justify-center text-white -translate-x-1/2 flex-shrink-0"
                                     :class="m.status === 'Completed' ? 'bg-emerald-500' : (m.status === 'In Progress' ? 'bg-amber-500 animate-pulse' : 'bg-gray-100 border-gray-200')">
                                    <template x-if="m.status === 'Completed'">
                                        <i class="ph ph-check text-xs font-bold"></i>
                                    </template>
                                    <template x-if="m.status === 'In Progress'">
                                        <i class="ph ph-clock text-xs font-bold"></i>
                                    </template>
                                    <template x-if="m.status === 'Pending'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    </template>
                                </div>

                                <!-- Milestone card content -->
                                <div class="w-full rounded-2xl p-5 border transition-all duration-200"
                                     :class="m.status === 'Completed' ? 'bg-[#f0fdf4] border-emerald-100' : (m.status === 'In Progress' ? 'bg-[#fffbeb] border-amber-200' : 'bg-white border-gray-100')">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <h3 class="font-extrabold text-sm text-gray-800" x-text="m.title">Milestone Title</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider block w-fit"
                                              :class="m.status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : (m.status === 'In Progress' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-500')"
                                              x-text="m.status">Status</span>
                                    </div>

                                    <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold mt-1">
                                        <i class="ph ph-calendar-blank"></i>
                                        <span x-text="m.date">Feb 15, 2026</span>
                                    </div>

                                    <template x-if="m.desc">
                                        <div class="mt-3 text-xs font-medium"
                                             :class="m.status === 'Completed' ? 'text-emerald-700' : 'text-amber-705'">
                                            <span x-text="m.status === 'Completed' ? '✓ ' : ''"></span>
                                            <span x-text="m.desc">Description text</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="flex items-center gap-3">
                    <button @click="alert('Updating monitoring milestones...')" class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                        Update Progress
                    </button>
                    <button @click="alert('Downloading Timeline...')" class="px-5 py-3 bg-white border border-gray-250 hover:bg-gray-50 text-gray-707 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                        Download Timeline
                    </button>
                </div>
            </div>

            <!-- TAB: Defense Endorsement -->
            <div x-show="activeTab === 'endorsement'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">My Defense Schedule</h1>
                    <p class="text-xs text-gray-455 mt-1">View your assigned defense schedule and details</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Scheduled -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Total Scheduled</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="defenseSchedules.length">3</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#10b981] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <!-- This Week -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">This Week</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block">2</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Pending</span>
                            <span class="text-3xl font-bold text-gray-850 mt-2 block" x-text="defenseSchedules.filter(s => s.status === 'Pending').length">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-550 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar-blank"></i>
                        </span>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Completed</span>
                            <span class="text-3xl font-bold text-gray-855 mt-2 block">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar-check"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
                    <span class="text-gray-400 pl-1"><i class="ph ph-funnel text-base"></i></span>
                    <select 
                        x-model="defenseTypeFilter" 
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.2em auto;"
                    >
                        <option value="all">All Defense Types</option>
                        <option value="proposal defense">Proposal Defense</option>
                        <option value="final defense">Final Defense</option>
                    </select>

                    <select 
                        x-model="defenseStatusFilter" 
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.2em auto;"
                    >
                        <option value="all">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <!-- Defense Schedule Cards List -->
                <div class="space-y-6">
                    <template x-for="(sched, idx) in filteredSchedules()" :key="idx">
                        <div :class="sched.leftBorder" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-4 hover:border-gray-200 transition-all duration-200">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <h3 class="font-extrabold text-sm text-gray-800" x-text="sched.type">Proposal Defense</h3>
                                    <span :class="sched.statusClass" class="ml-3" x-text="sched.status">Scheduled</span>
                                </div>
                                <button @click="alert(`Viewing schedule details for: ${sched.title}`)" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors cursor-pointer">
                                    <i class="ph ph-eye text-base"></i>
                                </button>
                            </div>

                            <!-- Title & Student -->
                            <div>
                                <span class="font-bold text-[#0e5c3a] text-sm block" x-text="sched.title">AI-Powered Traffic Management</span>
                                <span class="text-xs text-gray-500 font-semibold block mt-1" x-text="`Student: ${sched.student}`">Student: Juan Del</span>
                            </div>

                            <!-- Date / Time / Venue Details Row -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-gray-50">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-base"><i class="ph ph-calendar-blank"></i></span>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Date</span>
                                        <span class="text-xs text-gray-700 font-bold block mt-0.5" x-text="sched.date">May 25, 2026</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-base"><i class="ph ph-clock"></i></span>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Time</span>
                                        <span class="text-xs text-gray-700 font-bold block mt-0.5" x-text="sched.time">9:00 AM - 11:00 AM</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center text-base"><i class="ph ph-map-pin"></i></span>
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Venue</span>
                                        <span class="text-xs text-gray-700 font-bold block mt-0.5" x-text="sched.venue">Room 405, Research Building</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Panelists Badge row -->
                            <template x-if="sched.panels.length > 0">
                                <div class="pt-2">
                                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Panel Members</span>
                                    <div class="flex flex-wrap gap-2.5 mt-2">
                                        <template x-for="p in sched.panels" :key="p">
                                            <span class="bg-gray-50 border border-gray-150 text-gray-600 font-bold px-3 py-1 rounded-full text-[11px]" x-text="p">Panelist</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="filteredSchedules().length === 0">
                        <div class="bg-white rounded-3xl p-12 border border-gray-100 text-center text-gray-455 font-semibold shadow-sm">
                            No defense schedules found matching current filters.
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Evaluation Records -->
            <div x-show="activeTab === 'evaluations'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Evaluation & Grading</h1>
                    <p class="text-xs text-gray-455 mt-1">Research defense evaluation and scoring system</p>
                </div>

                <!-- Big Solid Green Card -->
                <div class="bg-emerald-500 rounded-3xl p-6 md:p-8 text-white shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative overflow-hidden">
                    <div class="space-y-1 z-10">
                        <span class="text-xs text-white/80 font-bold tracking-wider uppercase block">Overall Research Score</span>
                        <span class="text-5xl font-black block tracking-tight">90.0%</span>
                        <div class="flex items-center gap-1.5 text-amber-300 font-bold text-xs pt-2">
                            <span>★</span>
                            <span>Excellent Performance</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 z-10 md:text-right">
                        <span class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center text-4xl text-white">
                            <i class="ph ph-award"></i>
                        </span>
                        <div>
                            <span class="text-sm font-extrabold block">Proposal Defense</span>
                            <span class="text-[10px] text-white/80 font-medium block mt-0.5">May 10, 2026</span>
                        </div>
                    </div>
                </div>

                <!-- Scoring Breakdown & Panel Comments (2 Columns grid) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left: Scoring Breakdown -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                        <h2 class="text-sm font-bold text-gray-855 font-heading">Scoring Breakdown</h2>

                        <div class="space-y-5">
                            <template x-for="item in evaluationBreakdown" :key="item.label">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-gray-750" x-text="item.label">Research Originality</span>
                                        <span class="font-extrabold text-[#0e5c3a]" x-text="`${item.score}/${item.max}`">23/25</span>
                                    </div>
                                    <div class="w-full h-2 bg-gray-50 rounded-full overflow-hidden border border-gray-100/50">
                                        <div class="h-full bg-emerald-500 rounded-full" :style="`width: ${item.percent};`"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-50">
                            <span class="text-sm font-extrabold text-gray-800">Total Score</span>
                            <span class="text-xl font-black text-[#0e5c3a]">90/100</span>
                        </div>
                    </div>

                    <!-- Right: Panel Comments -->
                    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                        <h2 class="text-sm font-bold text-gray-855 font-heading">Panel Comments</h2>

                        <div class="space-y-4">
                            <template x-for="c in evaluationComments" :key="c.name">
                                <div :class="c.borderClass" class="rounded-2xl p-4 border space-y-3">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <h3 class="font-extrabold text-xs text-gray-800" x-text="c.name">Dr. Maria Santos</h3>
                                            <span class="text-[10px] text-gray-400 font-semibold block mt-0.5" x-text="c.title">Panel Chair</span>
                                        </div>
                                        <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                            <template x-for="i in Array.from({length: c.rating})">
                                                <span>★</span>
                                            </template>
                                            <template x-for="i in Array.from({length: 5 - c.rating})">
                                                <span class="text-gray-200">★</span>
                                            </template>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 leading-relaxed font-medium" x-text="c.comment">Comment text</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Panel Recommendations (Full Width below) -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-6">
                    <h2 class="text-sm font-bold text-gray-850 font-heading">Panel Recommendations</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Strengths -->
                        <div class="bg-emerald-50/10 border-l-4 border-l-emerald-500 border border-emerald-100/50 rounded-2xl p-5 space-y-3">
                            <h3 class="text-xs font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="ph ph-trend-up"></i> Strengths
                            </h3>
                            <ul class="space-y-2 text-xs text-gray-650 font-medium">
                                <li class="flex items-start gap-2"><span class="text-emerald-600">✓</span> Clear research objectives and methodology</li>
                                <li class="flex items-start gap-2"><span class="text-emerald-600">✓</span> Comprehensive data collection and analysis</li>
                                <li class="flex items-start gap-2"><span class="text-emerald-600">✓</span> Well-structured presentation</li>
                                <li class="flex items-start gap-2"><span class="text-emerald-600">✓</span> Strong defense of research findings</li>
                            </ul>
                        </div>

                        <!-- Areas for Improvement -->
                        <div class="bg-amber-50/10 border-l-4 border-l-amber-500 border border-amber-100/50 rounded-2xl p-5 space-y-3">
                            <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="ph ph-warning-circle"></i> Areas for Improvement
                            </h3>
                            <ul class="space-y-2 text-xs text-gray-650 font-medium">
                                <li class="flex items-start gap-2"><span class="text-amber-600">•</span> Expand literature review with recent studies</li>
                                <li class="flex items-start gap-2"><span class="text-amber-600">•</span> Include more diverse data samples</li>
                                <li class="flex items-start gap-2"><span class="text-amber-600">•</span> Strengthen theoretical framework</li>
                                <li class="flex items-start gap-2"><span class="text-amber-600">•</span> Add more visual data representations</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Final Recommendation Card -->
                    <div class="pt-6 border-t border-gray-50">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Final Recommendation</span>
                        <span class="text-sm font-extrabold text-emerald-600 block mt-1">PASSED - Proceed to Final Defense</span>
                        <p class="text-xs text-gray-500 font-medium mt-1">The panel recommends addressing the minor revisions before the final defense.</p>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="flex items-center gap-3">
                    <button @click="alert('Downloading Evaluation Report...')" class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                        Download Evaluation Report
                    </button>
                    <button @click="alert('Printing Certificate...')" class="px-5 py-3 bg-white border border-gray-250 hover:bg-gray-50 text-gray-707 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                        Print Certificate
                    </button>
                </div>
            </div>

            <!-- TAB: Official Adviser Forms -->
            <div x-show="activeTab === 'forms'" x-cloak class="space-y-6 animate-fade-in">
                @include('pages.adviser.forms.index')
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'classes', 'requests', 'consultation', 'docreview', 'revisions', 'repository', 'forms', 'settings', 'researchers', 'proposal', 'monitoring', 'endorsement', 'evaluations'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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
                        <h3 class="font-bold text-gray-800 text-sm" x-text="selectedNotification?.title"></h3>
                        <span class="text-[10px] text-gray-400" x-text="selectedNotification?.time"></span>
                    </div>
                </div>
                <button @click="selectedNotification = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <div class="space-y-2">
                <span class="px-2 py-0.5 rounded text-[9px] font-semibold" :class="selectedNotification?.badgeClass" x-text="selectedNotification?.badge"></span>
                <p class="text-xs text-gray-650 leading-relaxed" x-text="selectedNotification?.description"></p>
            </div>
            <div class="pt-4 flex justify-end">
                <button @click="selectedNotification = null" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Repository Upload Modal -->
    <div x-show="showRepositoryUploadModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showRepositoryUploadModal = false" class="bg-white rounded-3xl w-full max-w-lg p-6 shadow-xl space-y-5">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-gray-900">Upload Repository Document</h3>
                    <p class="text-xs text-gray-500 mt-1">PDF or DOCX only, up to 10 MB.</p>
                </div>
                <button type="button" @click="showRepositoryUploadModal = false" class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <form
                method="POST"
                action="{{ route('adviser.repository.documents.store') }}"
                enctype="multipart/form-data"
                class="space-y-4"
                x-data="{ uploading: false }"
                @submit="if (uploading) { $event.preventDefault() } else { uploading = true }"
            >
                @csrf
                <input type="hidden" name="submission_token" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                <div>
                    <label for="repository_document" class="block text-xs font-bold text-gray-700 mb-2">Research document</label>
                    <input
                        id="repository_document"
                        name="document"
                        type="file"
                        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        required
                        class="block w-full text-sm text-gray-600 border border-gray-200 rounded-xl file:mr-4 file:border-0 file:bg-emerald-50 file:px-4 file:py-3 file:text-xs file:font-bold file:text-[#0e5c3a]"
                    >
                    <p class="text-[11px] text-gray-400 mt-2">Files are signature-checked, renamed securely, and stored outside the public web directory.</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showRepositoryUploadModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="uploading"
                        class="px-5 py-2.5 bg-[#0e5c3a] disabled:opacity-60 text-white text-xs font-bold rounded-xl flex items-center gap-2"
                    >
                        <i class="ph ph-upload-simple"></i>
                        <span x-text="uploading ? 'Uploading...' : 'Upload Document'">Upload Document</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Class Modal -->
    <div x-show="showClassModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showClassModal = false" class="bg-white rounded-3xl w-full max-w-md p-6 shadow-xl space-y-4">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-gray-800 text-sm">Create New Research Class</h3>
                <button @click="showClassModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            <form method="POST" action="{{ route('adviser.classes.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="creation_token" value="{{ old('creation_token', (string) Illuminate\Support\Str::uuid()) }}">
                <div>
                    <label for="class_name" class="block text-xs font-bold text-gray-600 mb-1.5">Class Name</label>
                    <input id="class_name" name="name" type="text" value="{{ old('name') }}" minlength="3" maxlength="120" required placeholder="e.g. Software Engineering Capstone" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="class_description" class="block text-xs font-bold text-gray-600 mb-1.5">Description</label>
                    <textarea id="class_description" name="description" rows="3" maxlength="1000" placeholder="Optional class description" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all resize-none">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="max_students" class="block text-xs font-bold text-gray-600 mb-1.5">Student Limit</label>
                    <input id="max_students" name="max_students" type="number" value="{{ old('max_students', 50) }}" min="1" max="100" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] transition-all">
                    @error('max_students')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <p class="text-[10px] text-gray-400">A unique secure 8-character class code will be generated automatically.</p>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="showClassModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
