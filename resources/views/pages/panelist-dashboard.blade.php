@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'assigned-papers', 'proposal-eval', 'final-eval', 'recommendations', 'schedule', 'repository', 'forms', 'notifications', 'settings'];
    $initialTab = in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard';
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
    selectedDefense: null,
    assignedPapersSearchQuery: '',
    assignedPapersStatusFilter: 'all',
    proposalSearchQuery: '',
    proposalStatusFilter: 'all',
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
    recommendationComments: [
        { name: 'Dr. Maria Santos', role: 'Adviser', time: '2 hours ago', text: 'Please expand this section with more recent studies from 2024-2026.', page: 'Page 12', borderClass: 'border-l-4 border-l-amber-500 border border-gray-100 bg-amber-50/5' },
        { name: 'Dr. John Reyes', role: 'Panelist', time: '5 hours ago', text: 'Excellent data presentation. Well organized.', page: 'Page 18', borderClass: 'border-l-4 border-l-emerald-500 border border-gray-100 bg-emerald-50/5' },
        { name: 'Prof. Anna Garcia', role: 'Technical Editor', time: '1 day ago', text: 'Check citation format on this page - should follow APA 7th edition.', page: 'Page 5', borderClass: 'border-l-4 border-l-amber-500 border border-gray-100 bg-amber-50/5' }
    ],
    newRecommendationCommentText: '',
    postRecommendationComment() {
        if (this.newRecommendationCommentText.trim() === '') return;
        this.recommendationComments.push({
            name: 'Prof. Patricia Cruz',
            role: 'Panelist',
            time: 'Just now',
            text: this.newRecommendationCommentText,
            page: 'General',
            borderClass: 'border-l-4 border-l-blue-500 border border-gray-100 bg-blue-50/5'
        });
        this.newRecommendationCommentText = '';
    },
    repositorySearchQuery: '',
    repositoryStatusFilter: 'all',
    repositoryDocuments: [
        {
            title: 'Chapter 1 – Introduction',
            chapter: 'CHAPTER 1',
            desc: 'Background of the study, research objectives, and significance.',
            fileSize: '2.4 MB',
            date: 'May 10, 2026',
            author: 'Maria Santos',
            fileType: 'PDF',
            fileTypeClass: 'bg-red-50 border border-red-200 text-red-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'Reviewed',
            statusClass: 'bg-blue-50 border border-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph-file-pdf text-red-500 bg-red-50'
        },
        {
            title: 'Chapter 2 – Literature Review',
            chapter: 'CHAPTER 2',
            desc: 'Synthesis of related studies and theoretical framework.',
            fileSize: '3.8 MB',
            date: 'May 12, 2026',
            author: 'Maria Santos',
            fileType: 'PDF',
            fileTypeClass: 'bg-red-50 border border-red-200 text-red-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'Pending Review',
            statusClass: 'bg-amber-50 border border-amber-200 text-amber-705 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph-file-pdf text-red-500 bg-red-50'
        },
        {
            title: 'Chapter 3 – Methodology',
            chapter: 'CHAPTER 3',
            desc: 'Research design, sampling, data gathering procedures.',
            fileSize: '2.1 MB',
            date: 'May 15, 2026',
            author: 'Maria Santos',
            fileType: 'PDF',
            fileTypeClass: 'bg-red-50 border border-red-200 text-red-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'For Evaluation',
            statusClass: 'bg-purple-50 border border-purple-200 text-purple-700 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph-file-pdf text-red-500 bg-red-50'
        },
        {
            title: 'Survey Questionnaire',
            chapter: 'APPENDIX A',
            desc: 'Validated questionnaire used for primary data collection.',
            fileSize: '856 KB',
            date: 'Apr 20, 2026',
            author: 'Maria Santos',
            fileType: 'DOCX',
            fileTypeClass: 'bg-blue-50 border border-blue-200 text-blue-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'Approved',
            statusClass: 'bg-emerald-50 border border-emerald-250 text-emerald-705 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-blue-500',
            icon: 'ph-file-doc text-blue-500 bg-blue-50'
        },
        {
            title: 'Research Proposal – Final Draft',
            chapter: 'PROPOSAL',
            desc: 'Full research proposal approved for continuation.',
            fileSize: '1.5 MB',
            date: 'Mar 5, 2026',
            author: 'Maria Santos',
            fileType: 'PDF',
            fileTypeClass: 'bg-red-50 border border-red-200 text-red-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'Approved',
            statusClass: 'bg-emerald-50 border border-emerald-250 text-emerald-705 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-red-500',
            icon: 'ph-file-pdf text-red-500 bg-red-50'
        },
        {
            title: 'Instrument Validation Form',
            chapter: 'APPENDIX B',
            desc: 'Expert validation results for research instruments.',
            fileSize: '620 KB',
            date: 'Apr 28, 2026',
            author: 'Maria Santos',
            fileType: 'DOCX',
            fileTypeClass: 'bg-blue-50 border border-blue-200 text-blue-700 font-bold px-2.5 py-0.5 rounded-lg text-[9px]',
            status: 'Pending Review',
            statusClass: 'bg-amber-50 border border-amber-200 text-amber-705 font-bold px-2 py-0.5 rounded-full text-[9px]',
            topBorder: 'border-t-4 border-t-blue-500',
            icon: 'ph-file-doc text-blue-500 bg-blue-50'
        }
    ],
    filteredRepositoryDocuments() {
        return this.repositoryDocuments.filter(doc => {
            if (this.repositoryStatusFilter !== 'all' && doc.status.toLowerCase() !== this.repositoryStatusFilter.toLowerCase()) return false;
            if (this.repositorySearchQuery.trim() !== '') {
                const q = this.repositorySearchQuery.toLowerCase();
                return doc.title.toLowerCase().includes(q) || doc.author.toLowerCase().includes(q);
            }
            return true;
        });
    },
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
    defenseSearchQuery: '',
    defenseTypeFilter: 'all',
    defenseStatusFilter: 'all',
    defenses: [
        {
            id: 1,
            student: 'Juan Del' + 'a Cruz',
            type: 'Proposal Defense',
            date: 'May 25, 2026',
            time: '9:00 AM - 11:00 AM',
            title: 'AI-Powered Traffic Management ' + 'System',
            venue: 'Room 405, Research Building',
            panel: ['Dr. Antonio Santos', 'Dr. John Reyes', 'Prof. Anna Garcia'],
            status: 'Scheduled',
            leftBorder: 'border-l-4 border-l-[#10b981]',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
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
            status: 'Scheduled',
            leftBorder: 'border-l-4 border-l-[#10b981]',
            statusClass: 'bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
        },
        {
            id: 3,
            student: 'Your Research',
            type: 'Proposal Defense',
            date: 'July 15, 2026',
            time: 'TBA',
            title: 'Machine Learning in Agricultural Pest Detection',
            venue: 'TBA',
            panel: [],
            status: 'Pending',
            leftBorder: 'border-l-4 border-l-amber-500',
            statusClass: 'bg-amber-50 border border-amber-100 text-amber-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]'
        }
    ],
    filteredDefenses() {
        return this.defenses.filter(d => {
            if (this.defenseTypeFilter !== 'all' && d.type.toLowerCase() !== this.defenseTypeFilter.toLowerCase()) return false;
            if (this.defenseStatusFilter !== 'all' && d.status.toLowerCase() !== this.defenseStatusFilter.toLowerCase()) return false;
            if (this.defenseSearchQuery.trim() !== '') {
                const q = this.defenseSearchQuery.toLowerCase();
                return d.title.toLowerCase().includes(q) || d.student.toLowerCase().includes(q);
            }
            return true;
        });
    },

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
            <div class="space-y-1.5 pt-4 border-t border-white/10">
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
                <!-- Hero Header Card Section -->
                <div class="relative overflow-hidden bg-white rounded-2xl p-8 border border-slate-200/60 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <!-- Subtle abstract NDMU logo watermark -->
                    <div class="absolute -right-6 -bottom-6 opacity-[0.04] pointer-events-none">
                        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="w-64 h-auto">
                    </div>

                    <div class="relative z-10 space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                            <span>{{ now()->timezone(config('ndmu-rmas.timezone'))->format('l, F j, Y') }}</span>
                            <span>•</span>
                            <span class="text-[#0e5c3a] font-bold">Research Panelist Portal</span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold font-heading text-slate-900 tracking-tight">Welcome back, Dr. Antonio Santos</h1>
                        <p class="text-xs text-slate-500 max-w-xl">Review and evaluate research proposal & final defense presentations • Defense Panelist</p>
                    </div>
                </div>

                <!-- Stats Cards Row (4 Columns matching layout) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Upcoming Defenses -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-calendar"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Upcoming Defenses</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">5</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Pending Evaluations -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Pending Evaluations</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">3</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-clipboard-text"></i>
                        </span>
                    </div>

                    <!-- Completed Reviews -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-star"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Completed Reviews</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">24</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Average Score -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-purple-50/80 text-purple-700 border border-purple-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-chart-line-up"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Average Score</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">87%</span>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-trend-up"></i>
                        </span>
                    </div>
                </div>

                <!-- Scheduled Defense Panels section -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-6">
                    <h3 class="font-heading font-bold text-slate-900 text-sm pb-2 border-b border-slate-100 tracking-tight">Scheduled Defense Panels</h3>
                    
                    <div class="space-y-4">
                        <template x-for="def in defenses" :key="def.id">
                            <div class="border border-slate-200/60 bg-slate-50/30 rounded-xl p-5 flex items-center justify-between hover:border-emerald-200 hover:bg-slate-50/80 transition-all">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm" x-text="def.student">Student Name</h4>
                                    <span class="text-xs text-slate-500 block mt-0.5" x-text="def.type">Defense Type</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-slate-500 block font-medium" x-text="def.date">Date</span>
                                    <button @click="selectedDefense = def" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl mt-2 shadow-xs transition-colors cursor-pointer">
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
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Assigned</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="assignedPapers.length">5</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-files"></i>
                        </span>
                    </div>

                    <!-- For Review -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-clock"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">For Review</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="assignedPapers.filter(p => p.status === 'For Review').length">1</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-hourglass"></i>
                        </span>
                    </div>

                    <!-- Under Review -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-notebook"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Under Review</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="assignedPapers.filter(p => p.status === 'Under Review').length">1</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-pencil-line"></i>
                        </span>
                    </div>

                    <!-- Evaluated -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-purple-50/80 text-purple-700 border border-purple-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-check"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Evaluated</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="assignedPapers.filter(p => ['Evaluated', 'Approved'].includes(p.status)).length">2</span>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-check-circle"></i>
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
                <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm overflow-hidden">
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
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-check-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Approved</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => p.status === 'Approved').length">1</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-check-fat"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-clock"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Pending</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => p.status === 'Pending').length">0</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-hourglass"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-red-50/80 text-red-700 border border-red-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-x-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Revisions</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => p.status === 'Revisions').length">0</span>
                        </div>
                        <span class="text-red-500 text-xl font-bold">
                            <i class="ph ph-warning"></i>
                        </span>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Proposals</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.length">1</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-files"></i>
                        </span>
                    </div>
                </div>

                <!-- Main Proposal Card -->
                <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 p-6 space-y-6">
                    <h2 class="text-sm font-bold text-slate-900 font-heading tracking-wide">Research Proposal</h2>
                    
                    <div class="space-y-4">
                        <template x-for="p in filteredProposals()" :key="p.id">
                            <div class="bg-white border border-slate-200/60 rounded-xl p-6 space-y-4 shadow-2xs hover:shadow-xs transition-all">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div>
                                        <h3 class="font-extrabold text-sm text-slate-900 leading-snug" x-text="p.title">Machine Learning Applications in Agricultural Pest Detection</h3>
                                        <p class="text-[10px] text-slate-500 font-semibold mt-1.5" x-text="`Proposal ID: ${p.id}`">Proposal ID: PROP-2026-001</p>
                                        <p class="text-[10px] text-slate-500 font-semibold mt-0.5" x-text="`Submitted: ${p.submitted}`">Submitted: March 5, 2026</p>
                                    </div>
                                    <span :class="p.statusClass" class="flex-shrink-0 self-start text-[10px] font-black" x-text="p.status">Approved</span>
                                </div>

                                <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Reviewed by</span>
                                        <span class="text-xs text-slate-800 font-bold block mt-1" x-text="p.reviewedBy">Dr. Maria Santos</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Approval Date</span>
                                        <span class="text-xs text-slate-800 font-bold block mt-1" x-text="p.approvalDate">March 10, 2026</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <button @click="alert(`Viewing Proposal: ${p.title}`)" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                                        View Proposal
                                    </button>
                                    <button @click="alert(`Downloading PDF for: ${p.title}`)" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
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

            <!-- TAB: Final Defense Evaluation -->
            <div x-show="activeTab === 'final-eval'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Evaluation & Grading</h1>
                    <p class="text-xs text-gray-455 mt-1">Research defense evaluation and scoring system</p>
                </div>

                <!-- Big Solid Green Card -->
                <div class="bg-[#10b981] rounded-2xl p-6 md:p-8 text-white shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative overflow-hidden">
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
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-6">
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
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-6">
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
                <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-6">
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

            <!-- TAB: My Recommendations -->
            <div x-show="activeTab === 'recommendations'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Document Review System</h1>
                    <p class="text-xs text-gray-455 mt-1">Review and annotate research documents</p>
                </div>

                <!-- Document Info Card -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-pdf"></i>
                        </span>
                        <div>
                            <h2 class="font-extrabold text-sm text-gray-800 leading-snug">Chapter 3 - Research Methodology (Revised)</h2>
                            <p class="text-[11px] text-gray-505 font-semibold mt-1">Machine Learning Applications in Agricultural Pest Detection</p>
                            <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Uploaded: May 15, 2026 &nbsp;•&nbsp; Version 2.3 &nbsp;•&nbsp; 42 pages</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="alert('Downloading document...')" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer flex items-center gap-1.5">
                            <i class="ph ph-download-simple"></i> Download
                        </button>
                        <button @click="alert('Opening full document view...')" class="px-5 py-2.5 bg-white border border-gray-250 hover:bg-gray-50 text-gray-707 text-xs font-bold rounded-xl transition-colors cursor-pointer">
                            View Full Document
                        </button>
                    </div>
                </div>

                <!-- Stats Row (4 columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-check-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Approved</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">8</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-check-fat"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-warning"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Revisions</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">5</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-hourglass"></i>
                        </span>
                    </div>

                    <!-- Comments -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-chat-centered-text"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Comments</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="recommendationComments.length">12</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-chat-circle-dots"></i>
                        </span>
                    </div>

                    <!-- Critical -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-red-50/80 text-red-700 border border-red-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-x-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Critical</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">2</span>
                        </div>
                        <span class="text-red-500 text-xl font-bold">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Document Preview & Feedback List (Grid layout) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Document Preview (col-span-2) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 md:p-8 space-y-6">
                        <h2 class="text-sm font-bold text-gray-850 font-heading tracking-wide border-b border-gray-50 pb-3">Document Preview</h2>
                        
                        <div class="space-y-6 text-xs text-gray-655 leading-relaxed font-medium">
                            <h3 class="text-lg font-bold text-gray-800 font-heading">Chapter 3: Research Methodology</h3>
                            <p>
                                This chapter presents the research design, methods, and procedures employed in this study. The methodology encompasses the research approach, data collection instruments, sampling techniques, and data analysis methods.
                            </p>

                            <h4 class="text-sm font-bold text-gray-800 font-heading mt-4">3.1 Research Design</h4>
                            <p>
                                This study utilizes a quantitative research approach with an experimental design to evaluate the effectiveness of machine learning algorithms in detecting agricultural pests...
                            </p>

                            <h4 class="text-sm font-bold text-gray-800 font-heading mt-4">3.2 Data Collection</h4>
                            <p>
                                The data collection process involves capturing high-resolution images of crops from various agricultural sites across South Cotabato province...
                            </p>

                            <!-- Alert Box -->
                            <div class="bg-amber-50/50 border-l-4 border-l-amber-500 border border-amber-100/50 rounded-xl p-4 mt-6">
                                <p class="text-xs text-amber-800 font-medium leading-relaxed">
                                    <span class="font-bold">Reviewer Note:</span> Consider adding more details about the image preprocessing steps used in your methodology.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Comments & Feedback (col-span-1) -->
                    <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 space-y-6">
                        <h2 class="text-sm font-bold text-gray-855 font-heading tracking-wide border-b border-gray-50 pb-3">Comments & Feedback</h2>

                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            <template x-for="c in recommendationComments" :key="c.name + c.time">
                                <div :class="c.borderClass" class="rounded-2xl p-4 space-y-2">
                                    <div class="flex justify-between items-start gap-2">
                                        <div>
                                            <h3 class="font-extrabold text-xs text-gray-800" x-text="c.name">Dr. Maria Santos</h3>
                                            <span class="text-[10px] text-gray-400 font-semibold block mt-0.5" x-text="c.role">Adviser</span>
                                        </div>
                                        <span class="text-[9px] text-gray-400 font-semibold" x-text="c.time">2 hours ago</span>
                                    </div>
                                    <p class="text-xs text-gray-655 font-medium leading-relaxed" x-text="c.text">Please expand this section with more recent studies from 2024-2026.</p>
                                    <div class="flex items-center justify-between pt-1 text-[10px] text-gray-400 font-bold border-t border-gray-100/20">
                                        <span x-text="c.page">Page 12</span>
                                        <div class="flex gap-2">
                                            <button @click="alert('Replying to comment...')" class="text-gray-450 hover:text-emerald-700 cursor-pointer">Reply</button>
                                            <button @click="alert('Resolving comment...')" class="text-gray-450 hover:text-emerald-700 cursor-pointer">Resolve</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Write Comment Form -->
                        <div class="space-y-3 pt-3 border-t border-gray-50">
                            <textarea 
                                x-model="newRecommendationCommentText"
                                rows="3" 
                                placeholder="Add a comment..."
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-150 rounded-2xl text-xs text-gray-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all resize-none"
                            ></textarea>
                            <button 
                                @click="postRecommendationComment()"
                                class="w-full py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer"
                            >
                                Post Comment
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Review Actions Section -->
                <div class="bg-white rounded-2xl border border-gray-100/50 shadow-sm p-6 space-y-4">
                    <span class="text-[10px] text-gray-400 font-extrabold uppercase tracking-wider block">Review Actions</span>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button @click="alert('Document Approved Successfully.')" class="px-5 py-3 bg-[#10b981] hover:bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <i class="ph ph-check-circle text-base"></i> Approve Document
                        </button>
                        <button @click="alert('Revisions Requested.')" class="px-5 py-3 bg-[#f59e0b] hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <i class="ph ph-warning text-base"></i> Request Revisions
                        </button>
                        <button @click="alert('Document Rejected.')" class="px-5 py-3 bg-[#ef4444] hover:bg-red-655 text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <i class="ph ph-x-circle text-base"></i> Reject Document
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB: My Defense Schedule -->
            <div x-show="activeTab === 'schedule'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">My Defense Schedule</h1>
                    <p class="text-xs text-gray-455 mt-1">View your assigned defense schedule and details</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Scheduled -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-calendar"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Scheduled</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="defenses.length">3</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-calendar-check"></i>
                        </span>
                    </div>

                    <!-- This Week -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-clock"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">This Week</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">2</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-timer"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-calendar-blank"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Pending</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="defenses.filter(s => s.status === 'Pending').length">1</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-hourglass"></i>
                        </span>
                    </div>

                    <!-- Completed -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-purple-50/80 text-purple-700 border border-purple-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-calendar-check"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Completed</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block">0</span>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
                    <span class="text-gray-400 pl-1"><i class="ph ph-funnel text-base"></i></span>
                    <select 
                        x-model="defenseTypeFilter" 
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-850 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.2em auto;"
                    >
                        <option value="all">All Defense Types</option>
                        <option value="proposal defense">Proposal Defense</option>
                        <option value="final defense">Final Defense</option>
                    </select>

                    <select 
                        x-model="defenseStatusFilter" 
                        class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-855 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
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
                    <template x-for="(sched, idx) in filteredDefenses()" :key="idx">
                        <div :class="sched.leftBorder" class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md transition-all duration-200 space-y-4 hover:border-gray-200 transition-all duration-200">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <h3 class="font-extrabold text-sm text-gray-800" x-text="sched.type">Proposal Defense</h3>
                                    <span :class="sched.statusClass" class="ml-3" x-text="sched.status">Scheduled</span>
                                </div>
                                <button @click="selectedDefense = sched" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors cursor-pointer">
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
                            <template x-if="sched.panel && sched.panel.length > 0">
                                <div class="pt-2">
                                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Panel Members</span>
                                    <div class="flex flex-wrap gap-2.5 mt-2">
                                        <template x-for="p in sched.panel" :key="p">
                                            <span class="bg-gray-50 border border-gray-155 text-gray-600 font-bold px-3 py-1 rounded-full text-[11px]" x-text="p">Panelist</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="filteredDefenses().length === 0">
                        <div class="bg-white rounded-2xl p-12 border border-gray-100 text-center text-gray-455 font-semibold shadow-sm">
                            No defense schedules found matching current filters.
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Research Repository -->
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs -->
                <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                    <span class="hover:text-[#0e5c3a] cursor-pointer flex items-center gap-1.5" @click="activeTab = 'dashboard'"><i class="ph ph-layout"></i> Dashboard</span>
                    <span>/</span>
                    <span class="text-gray-800">Research Repository</span>
                </div>

                <!-- Title Block -->
                <div>
                    <h1 class="text-2xl font-bold font-heading text-gray-800">Research Repository</h1>
                    <p class="text-xs text-gray-455 mt-1">Review and evaluate assigned research documents.</p>
                </div>

                <!-- Stats Cards Row (4 Columns) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Files -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-emerald-50/80 text-[#0e5c3a] border border-emerald-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-file-search"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Total Files</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.length">6</span>
                        </div>
                        <span class="text-emerald-600 text-xl font-bold">
                            <i class="ph ph-files"></i>
                        </span>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-blue-50/80 text-blue-700 border border-blue-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-check-circle"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Approved</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => d.status === 'Approved').length">2</span>
                        </div>
                        <span class="text-blue-500 text-xl font-bold">
                            <i class="ph ph-check-fat"></i>
                        </span>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-amber-50/80 text-amber-700 border border-amber-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-clock"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Pending Review</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => d.status === 'Pending Review').length">2</span>
                        </div>
                        <span class="text-amber-500 text-xl font-bold">
                            <i class="ph ph-hourglass"></i>
                        </span>
                    </div>

                    <!-- For Evaluation -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between">
                        <div>
                            <span class="w-11 h-11 rounded-xl bg-purple-50/80 text-purple-700 border border-purple-100/60 flex items-center justify-center text-xl mb-3 shadow-2xs">
                                <i class="ph ph-textbox"></i>
                            </span>
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">For Evaluation</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => d.status === 'For Evaluation').length">1</span>
                        </div>
                        <span class="text-purple-500 text-xl font-bold">
                            <i class="ph ph-clipboard-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Search & Filters Row -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                            <i class="ph ph-magnifying-glass text-base"></i>
                        </span>
                        <input 
                            type="text" 
                            x-model="repositorySearchQuery"
                            placeholder="Search documents or researcher name..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-855 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <select 
                            x-model="repositoryStatusFilter" 
                            class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-855 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all appearance-none cursor-pointer pr-8"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 20 20%27 fill=%27%236b7280%27%3E%3Cpath fill-rule=%27evenodd%27 d=%27M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z%27 clip-rule=%27evenodd%27/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.2em auto;"
                        >
                            <option value="all">All Status</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="pending review">Pending Review</option>
                            <option value="for evaluation">For Evaluation</option>
                            <option value="approved">Approved</option>
                        </select>
                    </div>
                </div>

                <!-- Grid of Repository Document Cards (3-column layout) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="(doc, index) in filteredRepositoryDocuments()" :key="index">
                        <div :class="doc.topBorder" class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4 hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                            <!-- Card Header Badges -->
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 border text-[9px] font-bold rounded-lg" :class="doc.fileType === 'PDF' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-blue-50 border-blue-200 text-blue-700'" x-text="doc.fileType">PDF</span>
                                <span :class="doc.statusClass" x-text="doc.status">Reviewed</span>
                            </div>

                            <!-- Document Chapter, Icon, Title and Details -->
                            <div class="space-y-3 flex-1">
                                <div class="flex items-start gap-3">
                                    <span class="w-10 h-10 rounded-2xl flex items-center justify-center text-xl flex-shrink-0" :class="doc.fileType === 'PDF' ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500'">
                                        <i class="ph" :class="doc.fileType === 'PDF' ? 'ph-file-pdf' : 'ph-file-doc'"></i>
                                    </span>
                                    <div>
                                        <span class="text-[9px] text-amber-600 font-bold block uppercase tracking-wider" x-text="doc.chapter">CHAPTER 1</span>
                                        <h3 class="font-extrabold text-sm text-gray-800 leading-snug mt-0.5" x-text="doc.title">Chapter 1 – Introduction</h3>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-455 leading-relaxed font-medium" x-text="doc.desc">Background description of the study.</p>
                            </div>

                            <!-- Footer Details Row -->
                            <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-semibold pt-3 border-t border-gray-50/50">
                                <span x-text="doc.fileSize">2.4 MB</span>
                                <span>•</span>
                                <span x-text="doc.date">May 10, 2026</span>
                                <span>•</span>
                                <span x-text="doc.author">Maria Santos</span>
                            </div>

                            <!-- Action Buttons Grid -->
                            <div class="grid grid-cols-3 gap-2.5 pt-3">
                                <button @click="alert(`Opening preview for: ${doc.title}`)" class="py-2 rounded-xl bg-white border border-[#0e5c3a] hover:bg-emerald-50 text-[#0e5c3a] text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1">
                                    <i class="ph ph-eye"></i> View
                                </button>
                                <button @click="alert(`Downloading file: ${doc.title}`)" class="py-2 rounded-xl bg-white border border-blue-500 hover:bg-blue-50 text-blue-600 text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1">
                                    <i class="ph ph-download"></i> Download
                                </button>
                                <button @click="alert(`Evaluating document: ${doc.title}`)" class="py-2 rounded-xl bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1 shadow-sm">
                                    <i class="ph ph-check-square"></i> Evaluate
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="filteredRepositoryDocuments().length === 0">
                        <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-white rounded-2xl p-12 border border-gray-100 text-center text-gray-455 font-semibold shadow-sm">
                            No files found in research repository matching current search or filters.
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB: Official Panelist Forms -->
            <div x-show="activeTab === 'forms'" x-cloak class="space-y-6 animate-fade-in">
                @include('pages.panelist.forms.index')
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'settings', 'assigned-papers', 'proposal-eval', 'final-eval', 'recommendations', 'schedule', 'repository', 'forms'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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
        <div @click.away="selectedDefense = null" class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl space-y-4">
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
