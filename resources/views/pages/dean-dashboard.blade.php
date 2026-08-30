@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'pending', 'manuscript', 'appointments', 'schedule', 'reports', 'repository', 'notifications', 'settings'];
    $initialTab = in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard';
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
    dashboardUrl: @js(route('dean.dashboard')),
    persistTab(tab) {
        const url = new URL(this.dashboardUrl, window.location.origin);
        url.searchParams.set('tab', tab);

        if (`${url.pathname}${url.search}` === `${window.location.pathname}${window.location.search}`) return;

        window.Livewire?.navigate
            ? window.Livewire.navigate(url.toString())
            : window.location.assign(url.toString());
    },
    notificationsFilter: 'all',
    showDetailsModal: false,
    selectedRequest: null,
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
        { name: 'Isabelle Garcia', initials: 'I', initialsBg: 'bg-emerald-700 text-white', email: 'isabelle.garcia@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2025-08-11', isSystem: true },
        { name: 'Marco Villanueva', initials: 'M', initialsBg: 'bg-[#0f766e] text-white', email: 'marco.villanueva@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Active', dept: 'College of Engineering', date: '2025-08-12', isSystem: true },
        { name: 'Juan Dela Cruz', initials: 'J', initialsBg: 'bg-[#0f766e] text-white', email: 'juan.delacruz@ndmu.edu.ph', role: 'Student Researcher', roleClass: 'bg-gray-50 border border-gray-100 text-gray-700', status: 'Pending', dept: 'College of Engineering', date: '2026-05-28', isSystem: false },
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
    
    defenseTypeFilter: 'all',
    defenseStatusFilter: 'all',
    defenseSchedules: [
        {
            type: 'Proposal Defense',
            status: 'Scheduled',
            title: 'AI-Powered Traffic Management System',
            student: 'Juan Dela Cruz',
            date: 'May 25, 2026',
            time: '9:00 AM - 11:00 AM',
            venue: 'Room 405, Research Building',
            panels: ['Dr. Maria Santos', 'Dr. John Reyes', 'Prof. Anna Garcia'],
            borderColor: 'border-l-4 border-l-emerald-500',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
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
            borderColor: 'border-l-4 border-l-emerald-500',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
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
            borderColor: 'border-l-4 border-l-amber-500',
            statusClass: 'bg-amber-50 border border-amber-100 text-amber-700'
        }
    ],
    filteredSchedules() {
        return this.defenseSchedules.filter(s => {
            if (this.defenseTypeFilter !== 'all' && s.type.toLowerCase() !== this.defenseTypeFilter.toLowerCase()) return false;
            if (this.defenseStatusFilter !== 'all' && s.status.toLowerCase() !== this.defenseStatusFilter.toLowerCase()) return false;
            return true;
        });
    },

    repositorySearchQuery: '',
    repositoryStatusFilter: 'all',
    repositoryDocuments: [
        {
            type: 'PDF',
            status: 'Reviewed',
            chapter: 'CHAPTER 1',
            title: 'Chapter 1 – Introduction',
            desc: 'Background of the study, research objectives, and significance.',
            meta: '2.4 MB  •  May 10, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph ph-file-pdf text-red-500',
            badgeClass: 'bg-red-50 border border-red-100 text-red-750',
            statusClass: 'bg-blue-50 border border-blue-100 text-blue-700'
        },
        {
            type: 'PDF',
            status: 'Pending Review',
            chapter: 'CHAPTER 2',
            title: 'Chapter 2 – Literature Review',
            desc: 'Synthesis of related studies and theoretical framework.',
            meta: '3.8 MB  •  May 12, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph ph-file-pdf text-red-500',
            badgeClass: 'bg-red-50 border border-red-100 text-red-750',
            statusClass: 'bg-amber-50 border border-amber-100 text-amber-700'
        },
        {
            type: 'PDF',
            status: 'For Evaluation',
            chapter: 'CHAPTER 3',
            title: 'Chapter 3 – Methodology',
            desc: 'Research design, sampling, data gathering procedures.',
            meta: '2.1 MB  •  May 15, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph ph-file-pdf text-red-500',
            badgeClass: 'bg-red-50 border border-red-100 text-red-750',
            statusClass: 'bg-purple-50 border border-purple-100 text-purple-700'
        },
        {
            type: 'DOCX',
            status: 'Approved',
            chapter: 'APPENDIX A',
            title: 'Survey Questionnaire',
            desc: 'Validated questionnaire used for primary data collection.',
            meta: '856 KB  •  Apr 20, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-blue-500',
            icon: 'ph ph-file-word text-blue-500',
            badgeClass: 'bg-blue-50 border border-blue-100 text-blue-750',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
        },
        {
            type: 'PDF',
            status: 'Approved',
            chapter: 'PROPOSAL',
            title: 'Research Proposal – Final Draft',
            desc: 'Full research proposal approved for continuation.',
            meta: '1.5 MB  •  Mar 5, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph ph-file-pdf text-red-500',
            badgeClass: 'bg-red-50 border border-red-100 text-red-750',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700'
        },
        {
            type: 'DOCX',
            status: 'Pending Review',
            chapter: 'APPENDIX B',
            title: 'Instrument Validation Form',
            desc: 'Expert validation results for research instruments.',
            meta: '620 KB  •  Apr 28, 2026  •  Maria Santos',
            topBorder: 'border-t-4 border-t-blue-500',
            icon: 'ph ph-file-word text-blue-500',
            badgeClass: 'bg-blue-50 border border-blue-100 text-blue-750',
            statusClass: 'bg-amber-50 border border-amber-100 text-amber-700'
        }
    ],
    filteredDocuments() {
        return this.repositoryDocuments.filter(d => {
            if (this.repositoryStatusFilter !== 'all' && d.status.toLowerCase() !== this.repositoryStatusFilter.toLowerCase()) return false;
            if (this.repositorySearchQuery.trim() !== '') {
                const q = this.repositorySearchQuery.toLowerCase();
                return d.title.toLowerCase().includes(q) || d.chapter.toLowerCase().includes(q) || d.desc.toLowerCase().includes(q);
            }
            return true;
        });
    },

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
}"
    x-init="$watch('activeTab', (tab, previousTab) => {
        if (tab !== previousTab) $nextTick(() => persistTab(tab));
    })"
>
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white flex flex-col justify-between z-20 border-r border-emerald-800/40 shadow-2xl overflow-y-auto">
        <div class="flex-shrink-0">
            <!-- Brand Logo Header -->
            <div class="p-6 pb-4 flex items-center gap-3.5">
                <div class="p-2 bg-gradient-to-br from-white/15 to-white/5 rounded-2xl border border-white/20 shadow-lg backdrop-blur-md">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto drop-shadow-sm">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-black text-xl text-white tracking-tight">NDMU</span>
                    <span class="text-[9px] font-black text-[#eebc3f] tracking-[0.16em] uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Designer Decorative Underline under Logo -->
            <div class="px-6 my-2 flex items-center justify-center gap-2">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
                <div class="h-1 w-8 rounded-full bg-gradient-to-r from-[#eebc3f] to-[#ffd76f] shadow-[0_0_8px_rgba(238,188,63,0.7)]"></div>
                <div class="h-px flex-1 bg-gradient-to-l from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
            </div>

            <!-- Floating Profile Card -->
            <div class="px-5 py-3">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] border border-white/10 shadow-inner backdrop-blur-xs hover:bg-white/[0.09] transition-all">
                    <div class="relative w-10 h-10 rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-[#09472d] font-black flex items-center justify-center text-lg flex-shrink-0 shadow-md">
                        D
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ auth()->user()->name ?? 'Dr. Lourdes Castillo' }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">College Dean</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-5 py-3 space-y-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Navigation</span>
                </div>
                
                <!-- Dashboard -->
                <button 
                   type="button" 
                   @click="activeTab = 'dashboard'"
                   :class="activeTab === 'dashboard' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg transition-transform group-hover:scale-110"></i>
                        <span>Dashboard</span>
                    </div>
                    <span x-show="activeTab === 'dashboard'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
                
                <!-- Pending Approvals -->
                <button 
                   type="button" 
                   @click="activeTab = 'pending'"
                   :class="activeTab === 'pending' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard text-lg transition-transform group-hover:scale-110"></i>
                        <span>Pending Approvals</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['pending'] ?? 0" label="approvals requiring attention" />
                        <span x-show="activeTab === 'pending'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Manuscript Approvals -->
                <button 
                   type="button" 
                   @click="activeTab = 'manuscript'"
                   :class="activeTab === 'manuscript' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-certificate text-lg transition-transform group-hover:scale-110"></i>
                        <span>Manuscript Approvals</span>
                    </div>
                    <span x-show="activeTab === 'manuscript'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <!-- Faculty Appointments -->
                <button 
                   type="button" 
                   @click="activeTab = 'appointments'"
                   :class="activeTab === 'appointments' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-users text-lg transition-transform group-hover:scale-110"></i>
                        <span>Faculty Appointments</span>
                    </div>
                    <span x-show="activeTab === 'appointments'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <!-- Defense Schedules -->
                <button 
                   type="button" 
                   @click="activeTab = 'schedule'"
                   :class="activeTab === 'schedule' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg transition-transform group-hover:scale-110"></i>
                        <span>Defense Schedules</span>
                    </div>
                    <span x-show="activeTab === 'schedule'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <!-- Research Reports -->
                @can('reports.view')
                <a href="{{ route('dean.reports.index') }}"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Reports</span>
                    </div>
                </a>
                @endcan

                <!-- Research Repository -->
                <button 
                   type="button" 
                   @click="activeTab = 'repository'"
                   :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
            </div>

            <!-- Research Forms Section -->
            <div class="space-y-1.5 pt-4">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Official Forms</span>
                </div>
                
                <a 
                    href="{{ route('official-forms.workspace.index') }}"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg transition-transform group-hover:scale-110"></i>
                        <span>Official Forms Workspace</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['forms'] ?? 0" label="forms awaiting approval" />
                        <i class="ph ph-caret-right text-xs text-white/60"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-5 pb-5 mt-auto">
            <!-- Decorative Separator -->
            <div class="relative flex items-center justify-center my-3">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
            </div>

            <div class="space-y-1">
                <!-- Notifications -->
                <a href="{{ route('dean.dashboard', ['tab' => 'notifications']) }}"
                   wire:navigate
                   :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 cursor-pointer">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-bell text-lg"></i>
                        <span>Notifications</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['notifications'] ?? 0" label="unread notifications" />
                        <span x-show="activeTab === 'notifications'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </a>
                
                <!-- Settings -->
                <a href="#" 
                   @click.prevent="activeTab = 'settings'"
                   :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-gear text-lg"></i>
                        <span>Settings</span>
                    </div>
                    <span x-show="activeTab === 'settings'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="block" data-confirm-logout>
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white/70 hover:text-rose-200 hover:bg-rose-500/20 border border-transparent hover:border-rose-500/30 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer">
                        <i class="ph ph-sign-out text-lg"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
            <div class="flex items-center justify-center gap-2 text-[9px] text-white/40 text-center font-medium mt-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60"></span>
                <span>NDMU-RMAS © {{ now()->year }} · v1.0</span>
            </div>
        </div>
    </aside>

    <!-- Right Side: Content Area -->
    <div class="flex-1 flex flex-col min-h-screen pl-72">
        <!-- Top Nav Header -->
        <header class="h-20 bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-8 flex items-center justify-between sticky top-0 z-40 flex-shrink-0 transition-all">
            <!-- Search bar -->
            <div class="relative w-96">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i class="ph ph-magnifying-glass text-base"></i>
                </span>
                <input
                    type="text"
                    placeholder="Search research, documents, or tasks..."
                    class="w-full pl-10 pr-14 py-2.5 bg-slate-100/80 border border-slate-200/60 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-2 focus:ring-[#0e5c3a]/10 transition-all duration-200 shadow-2xs"
                >
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <kbd class="px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-white border border-slate-200 rounded-md shadow-2xs">Ctrl K</kbd>
                </div>
            </div>

            <!-- Right profile area matching "D / Dr. Dean's Portal" -->
            <div class="flex items-center gap-4">
                <x-workspace-switcher current="dean" />
                <x-notification-dropdown />
                
                <!-- Dean's Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                        D
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-slate-800">{{ auth()->user()->name ?? 'Dr. Dean' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Dean's Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8 space-y-8">
            <x-portal-feature-banner :sections="[
                'pending' => ['eyebrow' => 'College Dean Portal', 'title' => 'Pending Approvals', 'description' => 'Review research matters that require college-level approval.', 'icon' => 'ph-hourglass-medium'],
                'manuscript' => ['eyebrow' => 'College Dean Portal', 'title' => 'Manuscript Review', 'description' => 'Review authorized manuscripts and associated recommendations.', 'icon' => 'ph-file-search'],
                'schedule' => ['eyebrow' => 'College Dean Portal', 'title' => 'Defense Schedule', 'description' => 'View and oversee scheduled research defenses.', 'icon' => 'ph-calendar-check'],
                'appointments' => ['eyebrow' => 'College Dean Portal', 'title' => 'Appointments', 'description' => 'Review research personnel appointments and assignments.', 'icon' => 'ph-user-focus'],
                'reports' => ['eyebrow' => 'College Dean Portal', 'title' => 'College Reports', 'description' => 'Review research performance and compliance across CEAC.', 'icon' => 'ph-presentation-chart'],
                'repository' => ['eyebrow' => 'College Dean Portal', 'title' => 'Research Repository', 'description' => 'Access authorized college research records and documents.', 'icon' => 'ph-folder-open'],
            ]" />
            
            <!-- TAB: Dashboard (Active Default) -->
            <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-8 animate-fade-in">

                <!-- Rich Branded Command Hub & Quick Action Header -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#0a462c] p-6 md:p-8 text-white shadow-xl shadow-emerald-950/20 border border-emerald-600/30">
                    <!-- Ambient Glow & Watermark Logo -->
                    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
                    <div class="pointer-events-none absolute -left-12 -bottom-20 h-48 w-48 rounded-full bg-emerald-400/15 blur-2xl"></div>
                    <div class="pointer-events-none absolute right-6 top-1/2 -translate-y-1/2 opacity-[0.08]">
                        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="h-36 md:h-44 w-auto object-contain">
                    </div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f] font-black text-[10px] uppercase tracking-[0.16em]">
                                    <span class="w-2 h-2 rounded-full bg-[#eebc3f] animate-pulse"></span>
                                    Academic Year {{ now()->year }}-{{ now()->year + 1 }}
                                </span>
                                <span class="text-white/40 text-xs">•</span>
                                <span class="text-emerald-200 text-xs font-semibold tracking-wide">Office of the College Dean</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black font-heading text-white tracking-tight drop-shadow-xs">
                                Welcome back, {{ $dean->name }}!
                            </h1>
                            <p class="text-xs md:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                                Final academic oversight, research ethics compliance, manuscript review, and college-level approvals
                            </p>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'pending'"
                                class="px-4.5 py-2.5 bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 text-[#073823] text-xs font-black rounded-xl flex items-center gap-2 shadow-md shadow-amber-950/20 transition-all cursor-pointer"
                            >
                                <i class="ph ph-hourglass-medium text-base"></i>
                                <span>Pending Approvals</span>
                                @php
                                    $pendingForms = $pendingFormInstances->count();
                                @endphp
                                @if ($pendingForms > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-950 text-[#eebc3f]">
                                        {{ $pendingForms }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'manuscript'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-file-search text-base text-blue-300"></i>
                                <span>Manuscript Review</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'schedule'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-calendar-check text-base text-purple-300"></i>
                                <span>Defense Schedule</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'reports'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-chart-line-up text-base text-emerald-300"></i>
                                <span>College Reports</span>
                            </button>
                        </div>
                    </div>
                </div>

                <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions ?? []" />

                {{-- === 4 KPI METRIC CARDS === --}}
                @php
                    $pendingApprovalCount = $pendingFormInstances->count();
                @endphp
                <!-- Modern Vibrant 4-KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- KPI 1: Pending Approvals -->
                    <div
                        @click="activeTab = 'pending'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-amber-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-hourglass-medium"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-md shadow-amber-600/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-hourglass-medium"></i>
                                    @if ($pendingApprovalCount > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-rose-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <span>Sign-off</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Pending Approvals</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $pendingApprovalCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ $pendingApprovalCount > 0 ? 'Requires dean sign-off' : 'All approved' }}</span>
                                    <span class="font-bold {{ $pendingApprovalCount > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $pendingApprovalCount > 0 ? 'Action Needed' : 'Complete' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Active Research Projects -->
                    <div
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-emerald-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-scroll"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0e5c3a] text-white shadow-md shadow-emerald-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-scroll"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100 group-hover:bg-[#0e5c3a] group-hover:text-white transition-all">
                                    <span>Research</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Active Research</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">—</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Ongoing College Studies</span>
                                    <span class="font-bold text-[#0e5c3a]">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Faculty Appointments -->
                    <div
                        @click="activeTab = 'appointments'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-blue-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-user-focus"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 text-white shadow-md shadow-blue-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-user-focus"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <span>Faculty</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Appointments</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $appointmentsCount ?? '—' }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Advisers &amp; Panelists</span>
                                    <span class="font-bold text-blue-600">CEAC</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Approved Manuscripts -->
                    <div
                        @click="activeTab = 'manuscript'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-purple-600 to-pink-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-purple-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-book-open"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-800 text-white shadow-md shadow-purple-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-book-open"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100 group-hover:bg-purple-600 group-hover:text-white transition-all">
                                    <span>Manuscripts</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Manuscripts</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">—</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Approved by College</span>
                                    <span class="font-bold text-purple-600">Archived</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === MAIN WORKSPACE GRID === --}}
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

                    {{-- Left Col: Action Panels --}}
                    <div class="xl:col-span-8 space-y-5">
                        {{-- Pending Research Requests --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 bg-[#0e5c3a] flex items-center justify-between">
                                <div class="flex items-center gap-2 text-white">
                                    <i class="ph ph-file-text text-[#eebc3f] text-lg"></i>
                                    <h2 class="font-bold text-sm tracking-tight">Pending Research Requests</h2>
                                </div>
                                <button type="button" @click="activeTab = 'pending'" class="text-xs text-white/80 hover:text-white font-bold cursor-pointer">View All →</button>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <template x-for="req in requests" :key="req.id">
                                    <div class="px-6 py-4 flex items-center justify-between hover:bg-amber-50/20 transition">
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-slate-900 text-xs" x-text="req.title"></h4>
                                            <span class="text-[10px] text-slate-500">Student: <span class="font-bold text-slate-700" x-text="req.student"></span></span>
                                            <span class="text-[9px] text-slate-400 block" x-text="req.time"></span>
                                        </div>
                                        <span class="bg-amber-50 text-amber-700 border border-amber-200/60 px-2.5 py-0.5 rounded-full text-[10px] font-bold" x-text="req.status"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Final Manuscript Approvals --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 bg-[#0e5c3a] flex items-center justify-between">
                                <div class="flex items-center gap-2 text-white">
                                    <i class="ph ph-certificate text-[#eebc3f] text-lg"></i>
                                    <h2 class="font-bold text-sm tracking-tight">Final Manuscript Approvals</h2>
                                </div>
                                <button type="button" @click="activeTab = 'manuscript'" class="text-xs text-white/80 hover:text-white font-bold cursor-pointer">View All →</button>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <template x-for="ms in manuscripts" :key="ms.id">
                                    <div class="px-6 py-4 flex items-center justify-between hover:bg-purple-50/20 transition">
                                        <div class="space-y-0.5">
                                            <h4 class="font-bold text-slate-900 text-xs" x-text="ms.title"></h4>
                                            <span class="text-[10px] text-slate-500">Student: <span class="font-bold text-slate-700" x-text="ms.student"></span></span>
                                            <span class="text-[9px] text-slate-400 block" x-text="ms.phase"></span>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="ms.ratingClass" x-text="ms.rating"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Right Col: Quick Navigation --}}
                    <div class="xl:col-span-4 space-y-4">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100">
                                <h3 class="font-bold text-xs text-slate-900 flex items-center gap-2">
                                    <i class="ph ph-squares-four text-[#0e5c3a]"></i>
                                    Quick Navigation
                                </h3>
                            </div>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                @php
                                    $quickLinks = [
                                        ['tab' => 'schedule', 'icon' => 'ph-calendar-check', 'label' => 'Defense Schedule', 'color' => 'text-purple-600 bg-purple-50 border-purple-100 hover:bg-purple-100'],
                                        ['tab' => 'reports', 'icon' => 'ph-chart-line-up', 'label' => 'College Reports', 'color' => 'text-blue-600 bg-blue-50 border-blue-100 hover:bg-blue-100'],
                                        ['tab' => 'appointments', 'icon' => 'ph-user-focus', 'label' => 'Appointments', 'color' => 'text-teal-600 bg-teal-50 border-teal-100 hover:bg-teal-100'],
                                        ['tab' => 'repository', 'icon' => 'ph-folder-open', 'label' => 'Repository', 'color' => 'text-emerald-700 bg-emerald-50 border-emerald-100 hover:bg-emerald-100'],
                                    ];
                                @endphp
                                @foreach($quickLinks as $link)
                                    <button type="button" @click="activeTab = '{{ $link['tab'] }}'"
                                        class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border text-center transition cursor-pointer {{ $link['color'] }}">
                                        <i class="ph {{ $link['icon'] }} text-2xl"></i>
                                        <span class="text-[10px] font-bold leading-tight">{{ $link['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Recent Appointments Summary --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-users-three text-blue-500 text-base"></i>
                                    <h3 class="font-bold text-xs text-slate-900">Recent Appointments</h3>
                                </div>
                                <button type="button" @click="activeTab = 'appointments'" class="text-[10px] font-bold text-[#0e5c3a] hover:underline cursor-pointer">Manage</button>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <template x-for="app in appointments" :key="app.name">
                                    <div class="px-5 py-3 flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate" x-text="app.name"></p>
                                            <p class="text-[10px] text-slate-500" x-text="app.role + ' · ' + app.dept"></p>
                                        </div>
                                        <span class="shrink-0 text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100" x-text="app.status"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8 animate-fade-in">
                <x-notifications.center
                    :notifications="$userNotifications ?? collect()"
                    :unread-count="$userUnreadCount ?? 0"
                    :filter="$notificationFilter ?? 'all'"
                    :dashboard-route="route('dean.dashboard')"
                />
            </div>

            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings')
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
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-emerald-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Approved</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">1</span>
                        </div>
                        <span class="text-emerald-500 text-4xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-amber-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Pending</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">0</span>
                        </div>
                        <span class="text-amber-500 text-4xl">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">0</span>
                        </div>
                        <span class="text-red-500 text-4xl">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
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
                <div class="bg-white rounded-2xl p-6 border border-gray-100/50 shadow-sm space-y-6">
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
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-emerald-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Approved</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">8</span>
                        </div>
                        <span class="text-emerald-500 text-4xl">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-amber-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Revisions</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">5</span>
                        </div>
                        <span class="text-amber-500 text-4xl">
                            <i class="ph ph-warning"></i>
                        </span>
                    </div>

                    <!-- Comments -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-blue-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Comments</span>
                            <span class="text-3xl font-bold text-gray-800 mt-2 block">12</span>
                        </div>
                        <span class="text-blue-500 text-4xl">
                            <i class="ph ph-chat-text"></i>
                        </span>
                    </div>

                    <!-- Critical -->
                    <div class="bg-white rounded-2xl p-6 border-l-4 border-l-red-500 border-t border-r border-b border-gray-100/50 shadow-sm flex items-center justify-between">
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
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 space-y-6 flex flex-col justify-between">
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
                    <div class="lg:col-span-1 bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 flex flex-col justify-between space-y-6">
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
                <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 space-y-6">
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


            <!-- TAB: Faculty Appointments (User Management) -->
            <div x-show="activeTab === 'appointments'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">User Management</h1>
                    <p class="text-xs text-gray-455 mt-1">Manage accounts, approve registrations, and create staff users</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Users -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100/50 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-slate-100/80 text-slate-655 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-users"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.length">26</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Total Users</span>
                        </div>
                    </div>

                    <!-- Pending Approval -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100/50 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.filter(u => u.status === 'Pending').length">5</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Pending Approval</span>
                        </div>
                    </div>

                    <!-- Active Accounts -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100/50 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="managementUsers.filter(u => u.status === 'Active').length">21</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Active Accounts</span>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100/50 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-red-50 text-red-500 flex items-center justify-center text-xl flex-shrink-0">
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
                        <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                                        <tr>
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
                    <div x-show="managementSubTab === 'create'" class="bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 md:p-8 space-y-6">
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

            <!-- TAB: Defense Schedules -->
            <div x-show="activeTab === 'schedule'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">My Defense Schedule</h1>
                    <p class="text-xs text-gray-455 mt-1">View your assigned defense schedule and details</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Scheduled -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="defenseSchedules.length">3</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Total Scheduled</span>
                        </div>
                    </div>

                    <!-- This Week -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="defenseSchedules.filter(s => s.status === 'Scheduled').length">2</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">This Week</span>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-calendar-blank"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="defenseSchedules.filter(s => s.status === 'Pending').length">1</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Pending</span>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-calendar-check"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block">0</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Completed</span>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls Row -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-4">
                    <span class="text-gray-400 flex items-center justify-center text-lg flex-shrink-0 pl-1">
                        <i class="ph ph-funnel"></i>
                    </span>
                    
                    <div class="flex flex-wrap items-center gap-3 flex-grow">
                        <select 
                            x-model="defenseTypeFilter" 
                            class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em auto; padding-right: 2.25rem;"
                        >
                            <option value="all">All Defense Types</option>
                            <option value="proposal defense">Proposal Defense</option>
                            <option value="final defense">Final Defense</option>
                        </select>

                        <select 
                            x-model="defenseStatusFilter" 
                            class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em auto; padding-right: 2.25rem;"
                        >
                            <option value="all">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>

                <!-- Defense Cards List -->
                <div class="space-y-6">
                    <template x-for="(schedule, index) in filteredSchedules()" :key="index">
                        <div :class="schedule.borderColor" class="bg-white rounded-2xl p-6 border border-l-4 border-gray-100 shadow-sm flex flex-col gap-6 hover:border-gray-200 transition-all duration-200 relative">
                            <!-- Top Header Row of Card -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-extrabold text-sm text-gray-800" x-text="schedule.type">Proposal Defense</h3>
                                    <span :class="schedule.statusClass" class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider" x-text="schedule.status">Scheduled</span>
                                </div>
                                <button @click="alert(`Viewing details for: ${schedule.title}`)" class="w-8 h-8 rounded-full bg-blue-50/50 hover:bg-blue-50 text-blue-600 flex items-center justify-center transition-colors cursor-pointer">
                                    <i class="ph ph-eye text-base"></i>
                                </button>
                            </div>

                            <!-- Research Project Details -->
                            <div class="space-y-1">
                                <h4 class="font-extrabold text-base text-gray-900 leading-snug" x-text="schedule.title">Research Project Title</h4>
                                <p class="text-xs text-gray-500 font-semibold">Student: <span class="text-gray-700" x-text="schedule.student">Student Name</span></p>
                            </div>

                            <!-- Divider line -->
                            <div class="border-t border-gray-50"></div>

                            <!-- Date / Time / Venue Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Date -->
                                <div class="flex items-start gap-3">
                                    <span class="text-gray-400 text-lg flex items-center justify-center flex-shrink-0 pt-0.5">
                                        <i class="ph ph-calendar"></i>
                                    </span>
                                    <div class="leading-tight">
                                        <span class="text-[9.5px] font-bold text-gray-400 uppercase tracking-wider block">Date</span>
                                        <span class="text-xs text-gray-850 font-bold mt-1 block" x-text="schedule.date">May 25, 2026</span>
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="flex items-start gap-3">
                                    <span class="text-gray-400 text-lg flex items-center justify-center flex-shrink-0 pt-0.5">
                                        <i class="ph ph-clock"></i>
                                    </span>
                                    <div class="leading-tight">
                                        <span class="text-[9.5px] font-bold text-gray-400 uppercase tracking-wider block">Time</span>
                                        <span class="text-xs text-gray-850 font-bold mt-1 block" x-text="schedule.time">9:00 AM - 11:00 AM</span>
                                    </div>
                                </div>

                                <!-- Venue -->
                                <div class="flex items-start gap-3">
                                    <span class="text-gray-400 text-lg flex items-center justify-center flex-shrink-0 pt-0.5">
                                        <i class="ph ph-map-pin"></i>
                                    </span>
                                    <div class="leading-tight">
                                        <span class="text-[9.5px] font-bold text-gray-400 uppercase tracking-wider block">Venue</span>
                                        <span class="text-xs text-gray-855 font-bold mt-1 block" x-text="schedule.venue">Room 405</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Members Section -->
                            <template x-if="schedule.panels.length > 0">
                                <div class="space-y-3 pt-1">
                                    <p class="text-[9.5px] font-bold uppercase tracking-wider text-gray-400">Panel Members</p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <template x-for="panel in schedule.panels" :key="panel">
                                            <span class="bg-gray-50 border border-gray-100 rounded-xl px-3.5 py-1.5 text-xs text-gray-655 font-semibold" x-text="panel">Panel Member</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    
                    <template x-if="filteredSchedules().length === 0">
                        <div class="bg-white rounded-2xl p-12 border border-gray-100 text-center text-gray-450 font-semibold shadow-sm">
                            No defense schedules found matching current filters.
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Research Reports -->
            <div x-show="activeTab === 'reports'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title & Action Block -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Analytics & Reports</h1>
                        <p class="text-xs text-gray-455 mt-1">Research statistics and performance metrics</p>
                    </div>
                    <button @click="alert('Exporting Report...')" class="py-3 px-5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 shadow-md shadow-[#0e5c3a]/15 transition-all cursor-pointer">
                        <i class="ph ph-download text-sm"></i> Export Report
                    </button>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Research -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Total Research</span>
                            <span class="text-2xl font-bold text-gray-800 mt-2 block">174</span>
                            <span class="text-[10px] text-emerald-650 font-bold mt-1.5 block">+12% from last year</span>
                        </div>
                        <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-chart-bar"></i>
                        </span>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Completed</span>
                            <span class="text-2xl font-bold text-gray-800 mt-2 block">126</span>
                            <span class="text-[10px] text-[#2563eb] font-bold mt-1.5 block">72% completion rate</span>
                        </div>
                        <span class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-trend-up"></i>
                        </span>
                    </div>

                    <!-- In Progress -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">In Progress</span>
                            <span class="text-2xl font-bold text-gray-800 mt-2 block">48</span>
                            <span class="text-[10px] text-amber-600 font-bold mt-1.5 block font-sans">28% ongoing</span>
                        </div>
                        <span class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-chart-pie"></i>
                        </span>
                    </div>

                    <!-- Avg Duration -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-400 font-semibold block">Avg Duration</span>
                            <span class="text-2xl font-bold text-gray-800 mt-2 block">8.5</span>
                            <span class="text-[10px] text-gray-400 font-medium mt-1.5 block">months</span>
                        </div>
                        <span class="w-12 h-12 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-chart-line"></i>
                        </span>
                    </div>
                </div>

                <!-- Two Column Charts Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Research by Program -->
                    <div class="bg-white rounded-[2rem] border border-gray-100/50 shadow-sm p-6 space-y-6">
                        <h3 class="font-bold text-sm text-gray-800 font-heading">Research by Program</h3>
                        
                        <div class="space-y-4">
                            <!-- Progress Bar: Computer Science -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                    <span>Computer Science</span>
                                    <span>45</span>
                                </div>
                                <div class="bg-gray-100 rounded-full h-2 w-full">
                                    <div class="bg-emerald-600 h-2 rounded-full" style="width: 45%;"></div>
                                </div>
                            </div>

                            <!-- Progress Bar: Engineering -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                    <span>Engineering</span>
                                    <span>38</span>
                                </div>
                                <div class="bg-gray-100 rounded-full h-2 w-full">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 38%;"></div>
                                </div>
                            </div>

                            <!-- Progress Bar: Education -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                    <span>Education</span>
                                    <span>32</span>
                                </div>
                                <div class="bg-gray-100 rounded-full h-2 w-full">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: 32%;"></div>
                                </div>
                            </div>

                            <!-- Progress Bar: Business -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                                    <span>Business</span>
                                    <span>28</span>
                                </div>
                                <div class="bg-gray-100 rounded-full h-2 w-full">
                                    <div class="bg-amber-500 h-2 rounded-full" style="width: 28%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Monthly Submissions Bar Chart -->
                    <div class="bg-white rounded-[2rem] border border-gray-100/50 shadow-sm p-6 space-y-6 flex flex-col justify-between">
                        <h3 class="font-bold text-sm text-gray-800 font-heading">Monthly Submissions</h3>
                        
                        <!-- Dynamic CSS Bar Chart -->
                        <div class="flex flex-col justify-end flex-grow pt-4">
                            <!-- Bars Container -->
                            <div class="flex items-end gap-2 md:gap-3 h-36 px-2">
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[25%]" title="Jan"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[45%]" title="Feb"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[35%]" title="Mar"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[60%]" title="Apr"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[75%]" title="May"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[55%]" title="Jun"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[90%]" title="Jul"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[70%]" title="Aug"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[75%]" title="Sep"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[65%]" title="Oct"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[60%]" title="Nov"></div>
                                <div class="flex-grow bg-[#10b981] hover:bg-[#0e5c3a] transition-all rounded-t h-[50%]" title="Dec"></div>
                            </div>
                            
                            <!-- X Axis Labels -->
                            <div class="flex justify-between items-center text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-3 px-2 pt-2 border-t border-gray-100">
                                <span>Jan</span>
                                <span>Dec</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Research Repository -->
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Action Row -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <!-- Breadcrumbs -->
                        <div class="flex items-center gap-2 text-xs text-gray-400 font-semibold mb-2">
                            <i class="ph ph-layout text-sm"></i> 
                            <span>Dashboard</span> 
                            <span class="text-gray-300">/</span> 
                            <span class="text-[#0e5c3a]">Research Repository</span>
                        </div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Research Repository</h1>
                        <p class="text-xs text-gray-455 mt-1">Manage, upload, and track all your research files.</p>
                    </div>
                    <button @click="alert('Upload Document...')" class="py-3 px-5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 shadow-md shadow-[#0e5c3a]/15 transition-all cursor-pointer">
                        <i class="ph ph-upload text-sm"></i> Upload Document
                    </button>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Files -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-slate-100/80 text-slate-655 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="repositoryDocuments.length">6</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Total Files</span>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="repositoryDocuments.filter(d => d.status === 'Approved').length">2</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Approved</span>
                        </div>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="repositoryDocuments.filter(d => d.status === 'Pending Review').length">2</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">Pending Review</span>
                        </div>
                    </div>

                    <!-- For Evaluation -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-full bg-purple-50 text-purple-650 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-certificate"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block" x-text="repositoryDocuments.filter(d => d.status === 'For Evaluation').length">1</span>
                            <span class="text-[11px] text-gray-400 font-semibold block">For Evaluation</span>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls Row -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="relative w-full md:w-96">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-magnifying-glass text-sm"></i>
                        </span>
                        <input
                            type="text"
                            x-model="repositorySearchQuery"
                            placeholder="Search documents or researcher name..."
                            class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                        >
                    </div>

                    <!-- Right dropdown filter -->
                    <div class="relative w-full md:w-auto">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-455 pointer-events-none">
                            <i class="ph ph-funnel text-sm"></i>
                        </span>
                        <select 
                            x-model="repositoryStatusFilter" 
                            class="w-full md:w-44 pl-8 pr-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.75rem center; background-repeat: no-repeat; background-size: 1.25em auto;"
                        >
                            <option value="all">All Status</option>
                            <option value="approved">Approved</option>
                            <option value="pending review">Pending Review</option>
                            <option value="for evaluation">For Evaluation</option>
                            <option value="reviewed">Reviewed</option>
                        </select>
                    </div>
                </div>

                <!-- Document Grid (3 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <template x-for="(doc, idx) in filteredDocuments()" :key="idx">
                        <div :class="doc.topBorder" class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm flex flex-col justify-between gap-5 hover:border-gray-200 transition-all duration-200 relative">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-lg flex-shrink-0" 
                                         :class="doc.type === 'PDF' ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-600'">
                                        <i :class="doc.type === 'PDF' ? 'ph ph-file-pdf' : 'ph ph-file-word'"></i>
                                    </div>
                                    <span :class="doc.type === 'PDF' ? 'bg-red-50 border border-red-100 text-red-700' : 'bg-blue-50 border border-blue-100 text-blue-700'"
                                          class="px-2 py-0.5 rounded uppercase font-bold text-[9px] tracking-wider" 
                                          x-text="doc.type">PDF</span>
                                </div>
                                <span :class="doc.statusClass" class="px-2.5 py-0.5 rounded-full text-[9px] font-bold" x-text="doc.status">Reviewed</span>
                            </div>

                            <!-- Chapter info & Title -->
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block" x-text="doc.chapter">CHAPTER 1</span>
                                <h3 class="font-bold text-sm text-gray-850 leading-snug" x-text="doc.title">Chapter 1 – Introduction</h3>
                                <p class="text-xs text-gray-500 font-medium leading-relaxed pt-1" x-text="doc.desc">Background of the study, research objectives, and significance.</p>
                            </div>

                            <!-- File Meta details -->
                            <div class="text-[10px] text-gray-400 font-semibold" x-text="doc.meta">
                                2.4 MB  •  May 10, 2026  •  Maria Santos
                            </div>

                            <!-- Action Buttons Row -->
                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button @click="alert(`Viewing: ${doc.title}`)" class="py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-705 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                                    <i class="ph ph-eye text-sm"></i> View
                                </button>
                                <button @click="alert(`Downloading: ${doc.title}`)" class="py-2.5 bg-blue-55 border border-blue-100 hover:bg-blue-100 text-blue-650 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                                    <i class="ph ph-download text-sm"></i> Download
                                </button>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="filteredDocuments().length === 0">
                        <div class="col-span-1 md:col-span-3 bg-white rounded-3xl p-12 border border-gray-100 text-center text-gray-450 font-semibold shadow-sm">
                            No repository documents found matching current filters.
                        </div>
                    </template>
                </div>
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'settings', 'pending', 'manuscript', 'appointments', 'schedule', 'reports', 'repository'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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
