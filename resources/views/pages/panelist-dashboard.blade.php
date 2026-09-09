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

    $rawDefenses = $assignedDefenses ?? collect();
    $formattedDefenses = collect($rawDefenses)->map(function ($s) {
        $panelList = is_array($s['panelists'] ?? null) ? array_column($s['panelists'], 'name') : [];
        return [
            'id' => $s['id'] ?? 0,
            'student' => $s['group_name'] ?? 'Research Group',
            'type' => $s['defense_type_label'] ?? 'Proposal Defense',
            'date' => $s['formatted_date'] ?? '',
            'time' => $s['formatted_time'] ?? '',
            'title' => $s['research_title'] ?? 'Untitled Research',
            'venue' => ($s['room_name'] ?? 'Room') . (! empty($s['room_code']) ? " ({$s['room_code']})" : ''),
            'panel' => $panelList,
            'status' => ucfirst($s['schedule_status'] ?? 'Scheduled'),
            'leftBorder' => ($s['schedule_status'] ?? '') === 'current' ? 'border-l-4 border-l-[#10b981]' : 'border-l-4 border-l-slate-300',
            'statusClass' => ($s['schedule_status'] ?? '') === 'current' ? 'bg-emerald-50 border border-emerald-100 text-emerald-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]' : 'bg-slate-100 border border-slate-200 text-slate-700 font-bold px-2.5 py-0.5 rounded-full text-[10px]',
            'can_initiate_res036' => (bool) ($s['can_initiate_res036'] ?? false),
            'res036_url' => $s['res036_url'] ?? '#',
        ];
    })->values()->toArray();
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

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('panelistDashboard', (config) => ({
        activeTab: config.initialTab,
        activeFormPhase: config.initialFormPhase,
        activeOfficialForm: config.initialOfficialForm,
        officialForms: config.officialForms,
        evaluationRounds: config.evaluationRounds || [],
        formsExpanded: config.initialTab === 'forms',
        dashboardUrl: config.dashboardUrl,
        persistTabTimer: null,
        queuePersistTab(tab) {
            window.clearTimeout(this.persistTabTimer);
            this.persistTabTimer = window.setTimeout(() => this.persistTab(tab), 0);
        },
        persistTab(tab) {
            const url = new URL(this.dashboardUrl, window.location.origin);
            url.searchParams.set('tab', tab);
            if (tab === 'forms' && this.activeOfficialForm) {
                url.searchParams.set('form', this.activeOfficialForm);
            }

            if (`${url.pathname}${url.search}` === `${window.location.pathname}${window.location.search}`) return;

            window.Livewire?.navigate
                ? window.Livewire.navigate(url.toString())
                : window.location.assign(url.toString());
        },
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
        selectedReviewPaper: config.selectedReviewPaper,
        recommendationComments: config.selectedReviewPaper?.comments || [],
        activePageNumber: 1,
        isSubmittingCritique: false,
        isDownloadingAnnotated: false,
        async downloadAnnotatedCopy() {
            if (!this.selectedReviewPaper || this.isDownloadingAnnotated) return;
            this.isDownloadingAnnotated = true;

            try {
                await window.downloadAnnotatedPdf(Object.assign({}, this.selectedReviewPaper, {
                    comments: this.recommendationComments,
                }));
            } catch (error) {
                console.error('Error creating annotated PDF:', error);
                alert(error instanceof Error ? error.message : 'Unable to create the annotated PDF.');
            } finally {
                this.isDownloadingAnnotated = false;
            }
        },
        async submitCritique(event) {
            const form = event.target;
            const formData = new FormData(form);
            this.isSubmittingCritique = true;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (res.ok && data.comment) {
                    this.recommendationComments.unshift(data.comment);
                    const txt = form.querySelector('textarea[name="comment"]');
                    if (txt) txt.value = '';

                    const targetPageNum = data.comment.page_number || this.activePageNumber || 1;
                    window.appendDocumentComment?.(Object.assign({}, data.comment, { page_number: targetPageNum }));
                } else if (data.message) {
                    alert(data.message);
                }
            } catch (err) {
                console.error('Error posting critique:', err);
            } finally {
                this.isSubmittingCritique = false;
            }
        },
        recommendationZoom: 100,
        zoomIn() { if (this.recommendationZoom < 160) this.recommendationZoom += 10; },
        zoomOut() { if (this.recommendationZoom > 60) this.recommendationZoom -= 10; },
        resetZoom() { this.recommendationZoom = 100; },
        switchReviewPaper(paperId) {
            const url = new URL(this.dashboardUrl, window.location.origin);
            url.searchParams.set('tab', 'recommendations');
            url.searchParams.set('document_id', paperId);
            window.Livewire?.navigate ? window.Livewire.navigate(url.toString()) : window.location.assign(url.toString());
        },
        repositorySearchQuery: '',
        repositoryStatusFilter: 'all',
        repositoryDocumentExamples: [
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
        repositoryDocuments: config.assignedPapers || [],
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
        proposalProposals: config.proposalPapers || [],
        filteredProposals() {
            return this.proposalProposals.filter(p => {
                if (this.proposalStatusFilter !== 'all' && p.status.toLowerCase() !== this.proposalStatusFilter.toLowerCase()) return false;
                if (this.proposalSearchQuery.trim() !== '') {
                    const q = this.proposalSearchQuery.toLowerCase();
                    return p.title.toLowerCase().includes(q) || String(p.id).toLowerCase().includes(q) || p.filename.toLowerCase().includes(q);
                }
                return true;
            });
        },
        assignedPapers: config.assignedPapers || [],
        filteredAssignedPapers() {
            return this.assignedPapers.filter(p => {
                if (this.assignedPapersStatusFilter !== 'all' && p.status.toLowerCase() !== this.assignedPapersStatusFilter.toLowerCase()) return false;
                if (this.assignedPapersSearchQuery.trim() !== '') {
                    const q = this.assignedPapersSearchQuery.toLowerCase();
                    return p.title.toLowerCase().includes(q) || p.adviser.toLowerCase().includes(q) || p.college.toLowerCase().includes(q) || (p.researchers && p.researchers.some(r => r.name.toLowerCase().includes(q)));
                }
                return true;
            });
        },
        defenseSearchQuery: '',
        defenseTypeFilter: 'all',
        defenseStatusFilter: 'all',
        defenses: config.defenses || [],
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
        ],
        init() {
            this.$watch('activeTab', (tab, previousTab) => {
                if (tab !== previousTab) this.queuePersistTab(tab);
                if (tab === 'recommendations') {
                    setTimeout(() => {
                        window.initializePdfViewers?.();
                        window.initializeDocxViewers?.();
                    }, 50);
                }
            });
            this.$watch('activeOfficialForm', (form, previousForm) => {
                if (this.activeTab === 'forms' && form !== previousForm) this.queuePersistTab('forms');
            });
            if (this.activeTab === 'recommendations') {
                setTimeout(() => {
                    window.initializePdfViewers?.();
                    window.initializeDocxViewers?.();
                }, 50);
            }
        }
    }));
});
</script>

<div
    class="min-h-screen flex font-sans bg-[#f4f7f6]"
    x-data="panelistDashboard({
        initialTab: @js($initialTab),
        initialFormPhase: @js($initialFormPhase),
        initialOfficialForm: @js($initialOfficialForm),
        officialForms: @js($officialForms),
        evaluationRounds: @js($evaluationRounds ?? []),
        dashboardUrl: @js(route('panelist.dashboard')),
        selectedReviewPaper: @js($selectedReviewPaper ?? null),
        assignedPapers: @js($assignedPapers ?? []),
        proposalPapers: @js($proposalPapers ?? []),
        defenses: @js(! empty($formattedDefenses) ? $formattedDefenses : [])
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
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()->name ?? 'Panelist', 0, 1)) }}
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ auth()->user()->name ?? 'Dr. Antonio Santos' }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">Defense Panelist</span>
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
                
                <!-- Pending Form Approvals Queue -->
                <a
                   href="{{ route('official-forms.workspace.index') }}"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-check-square-offset text-lg text-amber-300 transition-transform group-hover:scale-110"></i>
                        <span>Pending Form Approvals</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['forms'] ?? 0" label="forms awaiting approval" />
                    </div>
                </a>
                
                <!-- Assigned Research Papers -->
                <button 
                   type="button" 
                   @click="activeTab = 'assigned-papers'"
                   :class="activeTab === 'assigned-papers' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Assigned Research Papers</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['assigned-papers'] ?? 0" label="assigned papers requiring attention" />
                        <span x-show="activeTab === 'assigned-papers'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Proposal Evaluation -->
                <button 
                   type="button" 
                   @click="activeTab = 'proposal-eval'"
                   :class="activeTab === 'proposal-eval' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-scroll text-lg transition-transform group-hover:scale-110"></i>
                        <span>Proposal Evaluation</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['proposal-eval'] ?? 0" label="proposal evaluations requiring attention" />
                        <span x-show="activeTab === 'proposal-eval'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Final Defense Evaluation -->
                <button 
                   type="button" 
                   @click="activeTab = 'final-eval'"
                   :class="activeTab === 'final-eval' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-clipboard-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Final Defense Evaluation</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['final-eval'] ?? 0" label="final defense evaluations requiring attention" />
                        <span x-show="activeTab === 'final-eval'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- My Recommendations -->
                <button 
                   type="button" 
                   @click="activeTab = 'recommendations'"
                   :class="activeTab === 'recommendations' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chat-teardrop text-lg transition-transform group-hover:scale-110"></i>
                        <span>My Recommendations</span>
                    </div>
                    <span x-show="activeTab === 'recommendations'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <!-- My Defense Schedule -->
                <button 
                   type="button" 
                   @click="activeTab = 'schedule'"
                   :class="activeTab === 'schedule' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                   class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg transition-transform group-hover:scale-110"></i>
                        <span>My Defense Schedule</span>
                    </div>
                    <span x-show="activeTab === 'schedule'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

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

            <!-- Official Forms Section -->
            <div class="space-y-1.5 pt-4">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Official Forms</span>
                </div>

                <button
                    type="button"
                    @click="formsExpanded = ! formsExpanded; activeTab = 'forms'"
                    :class="activeTab === 'forms' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    :aria-expanded="formsExpanded"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-[13px] transition-all duration-200 text-left cursor-pointer group"
                >
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-pdf text-lg transition-transform group-hover:scale-110"></i>
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
                                class="w-full flex items-center justify-between gap-2 py-2 pl-4 pr-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors duration-200 text-[11px] font-semibold text-left"
                            >
                                <span class="flex min-w-0 items-start gap-2">
                                    <i class="ph ph-caret-right mt-0.5 shrink-0 text-[10px] transition-transform duration-200" :class="activeFormPhase === '{{ $phase }}' && 'rotate-90'"></i>
                                    <span class="leading-4">{{ $label }}</span>
                                </span>
                                <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#eebc3f]/20 px-1.5 text-[9px] font-bold text-[#eebc3f]">
                                    {{ $phaseForms->count() }}
                                </span>
                            </button>

                            <div x-show="activeFormPhase === '{{ $phase }}'" x-cloak x-transition class="mt-0.5 space-y-0.5 pl-2">
                                @foreach ($phaseForms as $code => $form)
                                    <button
                                        type="button"
                                        @click="activeTab = 'forms'; activeOfficialForm = '{{ $code }}'"
                                        :class="activeOfficialForm === '{{ $code }}' ? 'bg-[#eebc3f] text-[#09472d] ring-1 ring-white font-bold' : 'text-white/75 hover:text-white hover:bg-white/10'"
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
        <div class="flex-shrink-0 px-5 pb-5 mt-auto">
            <!-- Decorative Separator -->
            <div class="relative flex items-center justify-center my-3">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
            </div>

            <div class="space-y-1">
                <!-- Notifications -->
                <a href="{{ route('panelist.dashboard', ['tab' => 'notifications']) }}"
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
                <a href="{{ route('panelist.dashboard', ['tab' => 'settings']) }}"
                   wire:navigate
                   :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold text-[13px]'"
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 cursor-pointer">
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

            <!-- Right profile area matching "P / Dr. Evaluation Portal" -->
            <div class="flex items-center gap-4">
                <x-workspace-switcher current="panelist" />
                <x-notification-dropdown />
                
                <!-- Evaluation Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr(auth()->user()->name ?? 'P', 0, 1)) }}
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-slate-800">{{ auth()->user()->name ?? 'Dr. Panelist' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Evaluation Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8">
            <x-portal-feature-banner class="mb-8" :sections="[
                'assigned-papers' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Assigned Papers', 'description' => 'Access the research papers assigned to you for review.', 'icon' => 'ph-files'],
                'proposal-eval' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Proposal Evaluation', 'description' => 'Evaluate assigned proposal defenses using the approved criteria.', 'icon' => 'ph-clipboard-text'],
                'final-eval' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Final Evaluation', 'description' => 'Record final-defense scores and evidence-based feedback.', 'icon' => 'ph-medal'],
'recommendations' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Recommendations', 'description' => 'Review and manage recommendations issued to research groups.', 'icon' => 'ph-lightbulb'],
                'schedule' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Defense Schedule', 'description' => 'View your assigned defense dates, venues, and research groups.', 'icon' => 'ph-calendar-check'],
                'repository' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Research Repository', 'description' => 'Securely access research documents assigned to your panel.', 'icon' => 'ph-folder-open'],
                'notifications' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Notifications', 'description' => 'Stay updated with defense assignments and panel evaluation notices.', 'icon' => 'ph-bell'],
                'settings' => ['eyebrow' => 'Panel Member Portal', 'title' => 'Account Settings', 'description' => 'Manage your profile, digital signature, and security preferences.', 'icon' => 'ph-gear'],
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
                                <span class="text-emerald-200 text-xs font-semibold tracking-wide">Research Panelist Portal</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black font-heading text-white tracking-tight drop-shadow-xs">
                                Welcome back, {{ $panelist->name }}!
                            </h1>
                            <p class="text-xs md:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                                Review assigned research papers, evaluate defense presentations, and score candidate presentations
                            </p>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'assigned-papers'"
                                class="px-4.5 py-2.5 bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 text-[#073823] text-xs font-black rounded-xl flex items-center gap-2 shadow-md shadow-amber-950/20 transition-all cursor-pointer"
                            >
                                <i class="ph ph-files text-base"></i>
                                <span>Assigned Papers</span>
                                @php
                                    $paperBadge = $sidebarBadges['assigned-papers'] ?? 0;
                                @endphp
                                @if ($paperBadge > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-950 text-[#eebc3f]">
                                        {{ $paperBadge }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'proposal-eval'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-scroll text-base text-[#eebc3f]"></i>
                                <span>Proposal Eval</span>
                                @php
                                    $proposalBadge = $sidebarBadges['proposal-eval'] ?? 0;
                                @endphp
                                @if ($proposalBadge > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-amber-950">
                                        {{ $proposalBadge }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'final-eval'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-medal text-base text-purple-300"></i>
                                <span>Final Eval</span>
                                @php
                                    $finalBadge = $sidebarBadges['final-eval'] ?? 0;
                                @endphp
                                @if ($finalBadge > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-purple-400 text-purple-950">
                                        {{ $finalBadge }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'schedule'"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-calendar-check text-base text-blue-300"></i>
                                <span>Defense Schedule</span>
                            </button>
                        </div>
                    </div>
                </div>

                <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions ?? []" />

                {{-- === 4 LIVE KPI METRIC CARDS === --}}
                @php
                    $upcomingPanelDefenses = collect($assignedDefenses ?? [])->filter(fn($d) => isset($d['starts_at']) && \Illuminate\Support\Carbon::parse($d['starts_at'])->isFuture())->count();
                    $pendingEvalCount = collect($evaluationRounds ?? [])->filter(fn($r) => in_array($r['status'] ?? null, ['open', 'in_progress']) && data_get($r, 'evaluation.status') !== 'submitted')->count();
                    $completedEvalCount = collect($evaluationRounds ?? [])->filter(fn($r) => data_get($r, 'evaluation.status') === 'submitted')->count();
                    $assignedPapersCount = count($assignedPapers ?? []);
                @endphp
                <!-- Modern Vibrant 4-KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- KPI 1: Assigned Papers -->
                    <div
                        @click="activeTab = 'assigned-papers'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-emerald-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-files"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0e5c3a] text-white shadow-md shadow-emerald-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-files"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100 group-hover:bg-[#0e5c3a] group-hover:text-white transition-all">
                                    <span>Papers</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Assigned Papers</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $assignedPapersCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Research Manuscripts</span>
                                    <span class="font-bold text-[#0e5c3a]">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Pending Evaluations -->
                    <div
                        @click="activeTab = 'proposal-eval'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-blue-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-clipboard-text"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 text-white shadow-md shadow-blue-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-clipboard-text"></i>
                                    @if ($pendingEvalCount > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-blue-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <span>Evaluations</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Pending Evaluations</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $pendingEvalCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ $pendingEvalCount > 0 ? 'Awaiting scoring' : 'All evaluated' }}</span>
                                    <span class="font-bold {{ $pendingEvalCount > 0 ? 'text-blue-600' : 'text-slate-400' }}">{{ $pendingEvalCount > 0 ? 'Action Needed' : 'Complete' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Completed Reviews -->
                    <div
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-amber-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-star"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-md shadow-amber-600/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-star"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <span>Completed</span>
                                    <i class="ph ph-check-circle"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Completed Reviews</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $completedEvalCount }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Submitted Evaluations</span>
                                    <span class="font-bold text-amber-600">Archived</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Upcoming Defenses -->
                    <div
                        @click="activeTab = 'schedule'"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-purple-600 to-pink-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-purple-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-calendar-check"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-800 text-white shadow-md shadow-purple-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-calendar-check"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100 group-hover:bg-purple-600 group-hover:text-white transition-all">
                                    <span>Schedule</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Upcoming Defenses</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $upcomingPanelDefenses }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Panel Defenses</span>
                                    <span class="font-bold text-purple-600">Calendar</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === DEFENSE PANELS & ACTION GRID === --}}
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    {{-- Left: Scheduled Defense Panels --}}
                    <div class="xl:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-4 rounded-full bg-[#0e5c3a]"></span>
                                <h2 class="font-bold text-sm text-slate-900">My Defense Schedule</h2>
                            </div>
                            <button type="button" @click="activeTab = 'schedule'"
                                class="text-xs text-[#0e5c3a] font-bold hover:underline flex items-center gap-1">
                                Full Schedule <i class="ph ph-arrow-right"></i>
                            </button>
                        </div>
                        <div class="divide-y divide-slate-50">
                            <template x-if="defenses.length === 0">
                                <div class="px-6 py-10 text-center">
                                    <i class="ph ph-calendar text-3xl text-slate-300"></i>
                                    <p class="text-sm font-semibold text-slate-500 mt-3">No Defense Schedules Assigned</p>
                                    <p class="text-xs text-slate-400 mt-1">When you are assigned as a panel member, defenses will appear here.</p>
                                </div>
                            </template>
                            <template x-for="def in defenses.slice(0, 5)" :key="def.id">
                                <div class="px-6 py-4" :class="def.leftBorder">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0 space-y-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="font-bold text-sm text-slate-900 truncate" x-text="def.student"></h4>
                                                <span :class="def.statusClass" x-text="def.status"></span>
                                            </div>
                                            <p class="text-xs text-slate-500 truncate" x-text="def.title"></p>
                                            <div class="flex items-center gap-3 text-[10px] text-slate-400 font-semibold">
                                                <span class="flex items-center gap-1"><i class="ph ph-tag text-xs"></i><span x-text="def.type"></span></span>
                                                <span class="flex items-center gap-1"><i class="ph ph-calendar-blank text-xs"></i><span x-text="def.date + ' ' + def.time"></span></span>
                                                <span class="flex items-center gap-1"><i class="ph ph-map-pin text-xs"></i><span x-text="def.venue"></span></span>
                                            </div>
                                        </div>
                                        <button @click="selectedDefense = def"
                                            class="shrink-0 px-3 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-[10px] font-bold rounded-xl shadow-xs transition cursor-pointer">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Right: Action Queue --}}
                    <div class="xl:col-span-4 space-y-5">
                        {{-- Evaluations Needing Attention --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-warning-circle text-blue-500 text-base"></i>
                                    <h3 class="font-bold text-xs text-slate-900">Evaluation Queue</h3>
                                </div>
                            </div>
                            <div class="divide-y divide-slate-50">
                                @if($pendingEvalCount === 0)
                                    <div class="px-5 py-7 text-center">
                                        <i class="ph ph-check-circle text-2xl text-emerald-400"></i>
                                        <p class="text-xs font-semibold text-slate-500 mt-2">All Evaluations Submitted!</p>
                                    </div>
                                @else
                                    @php($proposalPending = $sidebarBadges['proposal-eval'] ?? 0)
                                    @php($finalPending = $sidebarBadges['final-eval'] ?? 0)
                                    @if($proposalPending > 0)
                                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-blue-50/30 transition">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-sm shrink-0"><i class="ph ph-scroll"></i></div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800">Proposal Evaluations</p>
                                                    <p class="text-[10px] text-slate-500">{{ $proposalPending }} pending</p>
                                                </div>
                                            </div>
                                            <button type="button" @click="activeTab = 'proposal-eval'" class="shrink-0 px-2.5 py-1.5 rounded-lg bg-blue-500 text-white text-[10px] font-bold hover:bg-blue-600 cursor-pointer">Evaluate</button>
                                        </div>
                                    @endif
                                    @if($finalPending > 0)
                                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-purple-50/30 transition">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center text-sm shrink-0"><i class="ph ph-medal"></i></div>
                                                <div>
                                                    <p class="text-xs font-bold text-slate-800">Final Defense Evaluations</p>
                                                    <p class="text-[10px] text-slate-500">{{ $finalPending }} pending</p>
                                                </div>
                                            </div>
                                            <button type="button" @click="activeTab = 'final-eval'" class="shrink-0 px-2.5 py-1.5 rounded-lg bg-purple-500 text-white text-[10px] font-bold hover:bg-purple-600 cursor-pointer">Evaluate</button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Assigned Papers Quick View --}}
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-files text-emerald-500 text-base"></i>
                                    <h3 class="font-bold text-xs text-slate-900">Research Papers</h3>
                                </div>
                                <button type="button" @click="activeTab = 'assigned-papers'" class="text-[10px] font-bold text-[#0e5c3a] hover:underline cursor-pointer">See all</button>
                            </div>
                            <div class="divide-y divide-slate-50">
                                @forelse(collect($assignedPapers ?? [])->take(3) as $paper)
                                    <div class="px-5 py-3.5 space-y-0.5">
                                        <p class="text-xs font-bold text-slate-800 truncate">{{ $paper['title'] ?? 'Research Paper' }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $paper['group_name'] ?? ($paper['author'] ?? 'Group') }}</p>
                                        <span class="inline-block text-[9px] font-bold px-2 py-0.5 rounded-full
                                            @if(in_array($paper['status'] ?? '', ['For Review', 'Under Review'])) bg-blue-50 text-blue-700 border border-blue-100
                                            @elseif(($paper['status'] ?? '') === 'Reviewed') bg-emerald-50 text-emerald-700 border border-emerald-100
                                            @else bg-slate-100 text-slate-600 @endif">
                                            {{ $paper['status'] ?? 'Assigned' }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="px-5 py-7 text-center">
                                        <p class="text-xs text-slate-400">No papers assigned yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
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
                            <span x-text="`${assignedPapers.length} ${assignedPapers.length === 1 ? 'Paper' : 'Papers'} Assigned`"></span>
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
                            <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                                <tr>
                                    <th class="px-6 py-4 text-left">Research Title</th>
                                    <th class="px-6 py-4 text-left">Researchers</th>
                                    <th class="px-6 py-4 text-left">Adviser</th>
                                    <th class="px-6 py-4 text-left">Status</th>
                                    <th class="px-6 py-4 text-left">Defense Type</th>
                                    <th class="px-6 py-4 text-left">Submitted</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
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
                                                <a :href="paper.viewUrl" target="_blank" rel="noopener" class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-100 transition-colors cursor-pointer" title="View paper">
                                                    <i class="ph ph-eye text-sm"></i>
                                                </a>
                                                <a :href="paper.evaluationUrl" class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center hover:bg-emerald-100 transition-colors cursor-pointer" title="Open evaluation">
                                                    <i class="ph ph-file-text text-sm"></i>
                                                </a>
                                                <a :href="paper.downloadUrl" class="w-7 h-7 rounded-lg border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-gray-50 transition-colors cursor-pointer" title="Download paper">
                                                    <i class="ph ph-download text-sm"></i>
                                                </a>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Evaluated</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => p.status === 'Evaluated').length">0</span>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Awaiting Evaluation</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => ['Pending Defense', 'For Review'].includes(p.status)).length">0</span>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Under Review</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="proposalProposals.filter(p => p.status === 'Under Review').length">0</span>
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
                                        <p class="text-[10px] text-slate-500 font-semibold mt-1.5" x-text="p.filename">Proposal document</p>
                                        <p class="text-[10px] text-slate-500 font-semibold mt-0.5" x-text="`Submitted: ${p.submitted}`">Submitted: March 5, 2026</p>
                                    </div>
                                    <span :class="p.statusClass" class="flex-shrink-0 self-start text-[10px] font-black" x-text="p.status">Approved</span>
                                </div>

                                <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Research Adviser</span>
                                        <span class="text-xs text-slate-800 font-bold block mt-1" x-text="p.adviser">Not assigned</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Defense Stage</span>
                                        <span class="text-xs text-slate-800 font-bold block mt-1" x-text="p.defenseType">Proposal Defense</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-2">
                                    <a :href="p.viewUrl" target="_blank" rel="noopener" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                                        View Proposal
                                    </a>
                                    <a :href="p.downloadUrl" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                        Download <span x-text="p.fileType"></span>
                                    </a>
                                    <a :href="p.reviewUrl" class="px-5 py-2.5 bg-amber-50 border border-amber-200 hover:bg-amber-100 text-amber-800 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                        Review & Comment
                                    </a>
                                    <a x-show="p.status !== 'Pending Defense'" :href="p.evaluationUrl" class="px-5 py-2.5 bg-blue-50 border border-blue-200 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                        Open Evaluation
                                    </a>
                                </div>
                                <p x-show="p.status === 'Pending Defense'" class="text-[10px] font-semibold text-amber-700">
                                    The paper is available for advance reading. Formal scoring opens when the facilitator starts the evaluation round.
                                </p>
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

            <!-- TAB: My Recommendations / Paper Critique & Defense Review -->
            <div x-show="activeTab === 'recommendations'" x-cloak class="space-y-6 animate-fade-in">
                <!-- Title Block & Paper Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold font-heading text-gray-800">Manuscript Critique & Defense Review</h1>
                        <p class="text-xs text-gray-455 mt-1">Review assigned student manuscripts, preview DOCX/PDF inline, and provide official panel critiques.</p>
                    </div>

                    @if (count($assignedPapers ?? []) > 1)
                        <div class="flex items-center gap-2">
                            <label for="paper-select" class="text-xs font-bold text-slate-500 whitespace-nowrap">Active Paper:</label>
                            <select
                                id="paper-select"
                                @change="switchReviewPaper($event.target.value)"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs focus:border-[#0e5c3a] focus:outline-none cursor-pointer"
                            >
                                @foreach ($assignedPapers as $paper)
                                    <option value="{{ $paper['id'] }}" {{ ($selectedReviewPaper['id'] ?? null) === $paper['id'] ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($paper['title'], 40) }} ({{ $paper['defenseType'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                @if ($selectedReviewPaper)
                    @if (session('document_review_success'))
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800 flex items-center gap-2">
                            <i class="ph ph-check-circle text-base text-emerald-600"></i>
                            <span>{{ session('document_review_success') }}</span>
                        </div>
                    @endif
                    @if ($errors->has('document_review'))
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-bold text-red-800 flex items-center gap-2">
                            <i class="ph ph-warning-circle text-base text-red-600"></i>
                            <span>{{ $errors->first('document_review') }}</span>
                        </div>
                    @endif

                    <!-- Stats Row (4 columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Approved / Evaluated -->
                        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-xs hover:shadow-md transition-all flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Evaluated</span>
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5 block">
                                    {{ count(array_filter($assignedPapers ?? [], fn ($p) => in_array($p['status'] ?? '', ['Evaluated', 'Approved']))) }}
                                </span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-lg shadow-2xs">
                                <i class="ph ph-check-circle"></i>
                            </span>
                        </div>

                        <!-- Revisions / Under Review -->
                        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-xs hover:shadow-md transition-all flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Under Review</span>
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5 block">
                                    {{ count(array_filter($assignedPapers ?? [], fn ($p) => in_array($p['status'] ?? '', ['Under Review', 'For Review', 'Pending Defense']))) }}
                                </span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-lg shadow-2xs">
                                <i class="ph ph-clock"></i>
                            </span>
                        </div>

                        <!-- Comments -->
                        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-xs hover:shadow-md transition-all flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Comments</span>
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5 block" x-text="recommendationComments.length">
                                    {{ count($selectedReviewPaper['comments'] ?? []) }}
                                </span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-lg shadow-2xs">
                                <i class="ph ph-chat-centered-text"></i>
                            </span>
                        </div>

                        <!-- Critical Critiques -->
                        <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-xs hover:shadow-md transition-all flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Critical Critiques</span>
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight mt-0.5 block" x-text="recommendationComments.filter(c => c.severity === 'critical').length">
                                    {{ count(array_filter($selectedReviewPaper['comments'] ?? [], fn ($c) => ($c['severity'] ?? '') === 'critical')) }}
                                </span>
                            </div>
                            <span class="w-10 h-10 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-lg shadow-2xs">
                                <i class="ph ph-warning-circle"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Selected Paper Header Bar -->
                    <div class="bg-white rounded-2xl p-5 border border-slate-200/60 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-[#0e5c3a] border border-emerald-100 flex items-center justify-center text-xl shrink-0 shadow-2xs">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-black uppercase text-[#0e5c3a]">
                                        {{ $selectedReviewPaper['fileType'] }}
                                    </span>
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                        {{ $selectedReviewPaper['defenseType'] }}
                                    </span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedReviewPaper['statusClass'] }}">
                                        {{ $selectedReviewPaper['status'] }}
                                    </span>
                                </div>
                                <h2 class="font-extrabold text-sm text-slate-900 truncate mt-1" title="{{ $selectedReviewPaper['filename'] }}">
                                    {{ $selectedReviewPaper['filename'] }}
                                </h2>
                                <p class="text-xs text-slate-500 truncate mt-0.5">
                                    <strong class="text-slate-700">{{ $selectedReviewPaper['title'] }}</strong> · {{ $selectedReviewPaper['fileSize'] }} · Submitted {{ $selectedReviewPaper['submitted'] }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                            <!-- Zoom Controls for Preview -->
                            @if ($selectedReviewPaper['fileType'] === 'DOCX')
                                <div class="inline-flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                                    <button type="button" @click="zoomOut()" class="h-7 w-7 rounded-lg hover:bg-white flex items-center justify-center text-slate-700 transition cursor-pointer" title="Zoom Out">
                                        <i class="ph ph-minus text-xs font-bold"></i>
                                    </button>
                                    <span class="px-2 text-xs font-mono font-bold text-slate-700" x-text="recommendationZoom + '%'">100%</span>
                                    <button type="button" @click="zoomIn()" class="h-7 w-7 rounded-lg hover:bg-white flex items-center justify-center text-slate-700 transition cursor-pointer" title="Zoom In">
                                        <i class="ph ph-plus text-xs font-bold"></i>
                                    </button>
                                    <button type="button" @click="resetZoom()" class="ml-1 px-2 py-0.5 text-[10px] font-bold text-slate-500 hover:text-slate-900 rounded-md hover:bg-white transition cursor-pointer" title="Reset Zoom">
                                        Reset
                                    </button>
                                </div>
                            @endif

                            <a href="{{ $selectedReviewPaper['downloadUrl'] }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                <i class="ph ph-download-simple text-sm"></i>
                                <span>Original</span>
                            </a>

                            @if ($selectedReviewPaper['fileType'] === 'PDF')
                                <button
                                    type="button"
                                    @click="downloadAnnotatedCopy()"
                                    :disabled="isDownloadingAnnotated || recommendationComments.length === 0"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-900 hover:bg-amber-100 transition shadow-2xs disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <i class="ph text-sm" :class="isDownloadingAnnotated ? 'ph-spinner animate-spin' : 'ph-note-pencil'"></i>
                                    <span x-text="isDownloadingAnnotated ? 'Creating...' : 'Annotated PDF'">Annotated PDF</span>
                                </button>
                            @endif

                            <a href="{{ $selectedReviewPaper['evaluationUrl'] }}" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-3.5 py-2 text-xs font-bold text-white transition shadow-xs">
                                <i class="ph ph-check-circle text-sm"></i>
                                <span>Open Scoring</span>
                            </a>

                            <a href="{{ $selectedReviewPaper['viewUrl'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 transition shadow-2xs">
                                <i class="ph ph-arrows-out-simple text-sm"></i>
                                <span>Full Screen</span>
                            </a>
                        </div>
                    </div>

                    <!-- Split Screen: Document Preview (2/3) + Comments & Feedback (1/3) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        <!-- Left Panel: Live Document Preview -->
                        <section class="lg:col-span-2 h-[72vh] min-h-[560px] max-h-[850px] overflow-hidden bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col">
                            <div class="shrink-0 flex items-center justify-between border-b border-slate-100 px-5 py-3.5 bg-slate-50/50">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-book-open text-base text-slate-600"></i>
                                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Document Preview</h2>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 bg-white px-2 py-0.5 rounded-md border border-slate-200">
                                    Authorized Defense Viewport
                                </span>
                            </div>

                            <div class="min-h-0 flex-1 p-4 bg-slate-100/70 flex flex-col items-center overflow-y-auto overscroll-contain">
                                @if ($selectedReviewPaper['fileType'] === 'PDF')
                                    <div
                                        class="w-full flex flex-col items-center"
                                        data-pdf-viewer
                                        data-pdf-url="{{ route('documents.view', [$selectedReviewPaper['id'], 'raw' => 1]) }}"
                                        data-pdf-comments="{{ json_encode($selectedReviewPaper['comments'] ?? []) }}"
                                    >
                                        <div data-pdf-status class="py-24 text-center">
                                            <div class="inline-block h-9 w-9 animate-spin rounded-full border-4 border-emerald-600 border-r-transparent mb-3"></div>
                                            <p class="text-xs font-bold text-slate-700">Rendering manuscript preview in system...</p>
                                        </div>
                                        <div
                                            data-pdf-content
                                            class="hidden w-full transition-transform duration-150 origin-top"
                                            :style="`transform: scale(${recommendationZoom / 100});`"
                                        ></div>
                                    </div>
                                @elseif ($selectedReviewPaper['fileType'] === 'DOCX')
                                    <div
                                        class="w-full flex flex-col items-center"
                                        data-docx-viewer
                                        data-docx-url="{{ route('documents.view', [$selectedReviewPaper['id'], 'raw' => 1]) }}"
                                        data-docx-comments="{{ json_encode($selectedReviewPaper['comments'] ?? []) }}"
                                    >
                                        <div data-docx-status class="py-20 text-center">
                                            <div class="inline-block h-9 w-9 animate-spin rounded-full border-4 border-emerald-600 border-r-transparent mb-3"></div>
                                            <p class="text-xs font-bold text-slate-700">Rendering manuscript preview in system...</p>
                                        </div>
                                        <div
                                            data-docx-content
                                            class="hidden w-full transition-transform duration-150 origin-top"
                                            :style="`transform: scale(${recommendationZoom / 100});`"
                                        ></div>
                                    </div>
                                @else
                                    <div class="py-16 text-center text-slate-500">
                                        <i class="ph ph-file-dashed text-4xl mb-2 text-slate-400"></i>
                                        <p class="text-xs font-bold text-slate-700">In-browser preview is not supported for this format.</p>
                                        <a href="{{ $selectedReviewPaper['downloadUrl'] }}" class="mt-3 inline-block rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">
                                            Download Manuscript
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <!-- Right Panel: Comments & Feedback (Criticisms) -->
                        <aside class="h-[72vh] min-h-[560px] max-h-[850px] bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex flex-col gap-5 lg:sticky lg:top-40 overflow-hidden">
                            <div class="shrink-0 flex items-center justify-between border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-chats-circle text-base text-slate-600"></i>
                                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Comments & Feedback</h2>
                                </div>
                                <span class="rounded-full bg-blue-50 border border-blue-100 px-2 py-0.5 text-[10px] font-black text-blue-700" x-text="recommendationComments.length"></span>
                            </div>

                            <!-- Comment Feed -->
                            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain pr-1">
                                <template x-for="c in recommendationComments" :key="c.id || (c.name + c.time)">
                                    <div :class="c.borderClass || 'border border-slate-200 bg-slate-50/50'" class="rounded-xl p-3.5 space-y-2">
                                        <div class="flex justify-between items-start gap-2">
                                            <div>
                                                <h3 class="font-extrabold text-xs text-slate-800" x-text="c.name"></h3>
                                                <span class="text-[10px] text-slate-400 font-semibold block" x-text="c.role"></span>
                                            </div>
                                            <span class="text-[9px] text-slate-400 font-semibold whitespace-nowrap" x-text="c.time"></span>
                                        </div>
                                        <p class="text-xs text-slate-600 font-medium leading-relaxed whitespace-pre-line" x-text="c.text"></p>
                                        <div class="flex items-center justify-between pt-1 border-t border-slate-100/60 text-[10px] font-bold text-slate-400">
                                            <span x-text="c.page"></span>
                                            <span x-show="c.severity" :class="{
                                                'text-red-600': c.severity === 'critical',
                                                'text-amber-600': c.severity === 'revision',
                                                'text-blue-600': c.severity === 'comment'
                                            }" class="uppercase font-extrabold" x-text="c.severity"></span>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="recommendationComments.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-xs text-slate-400">
                                    No critiques or comments recorded yet for this paper.
                                </div>
                            </div>

                            <!-- Post Comment / Critique Form -->
                            <form method="POST" action="{{ $selectedReviewPaper['commentUrl'] }}" class="shrink-0 space-y-3 border-t border-slate-100 pt-4" @submit.prevent="submitCritique($event)" @document-page-change.window="activePageNumber = $event.detail.page">
                                @csrf
                                <textarea
                                    name="comment"
                                    rows="3"
                                    required
                                    minlength="2"
                                    maxlength="5000"
                                    placeholder="Write your defense critique / feedback..."
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:bg-white focus:border-[#0e5c3a] focus:ring-2 focus:ring-[#0e5c3a]/10 resize-none transition"
                                >{{ old('comment') }}</textarea>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Severity</label>
                                        <select name="severity" required class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none">
                                            <option value="comment">Suggestion</option>
                                            <option value="revision">Minor Revision</option>
                                            <option value="critical">Critical / Major</option>
                                        </select>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-[10px] font-bold text-slate-400 uppercase">Page Reference</label>
                                            <span class="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md inline-flex items-center gap-1">
                                                Page <span x-text="activePageNumber"></span>
                                            </span>
                                        </div>
                                        <input
                                            type="number"
                                            name="page_number"
                                            min="1"
                                            max="10000"
                                            x-model="activePageNumber"
                                            placeholder="e.g. 12"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-bold text-slate-800 focus:border-[#0e5c3a] focus:outline-none"
                                        >
                                    </div>
                                </div>

                                @error('comment')<p class="text-[10px] font-bold text-red-600">{{ $message }}</p>@enderror
                                @error('page_number')<p class="text-[10px] font-bold text-red-600">{{ $message }}</p>@enderror

                                <button
                                    type="submit"
                                    :disabled="isSubmittingCritique"
                                    class="w-full py-2.5 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <i class="ph ph-paper-plane-tilt text-sm font-bold" x-show="!isSubmittingCritique"></i>
                                    <div class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-r-transparent" x-show="isSubmittingCritique" x-cloak></div>
                                    <span x-text="isSubmittingCritique ? 'Posting Critique...' : 'Post Critique'"></span>
                                </button>
                                <p class="text-[10px] leading-relaxed text-slate-400">
                                    Panel comments are permanently stamped with your digital ID. You can also record formal scoring via the defense evaluation sheet.
                                </p>
                            </form>
                        </aside>
                    </div>

                    <!-- Bottom Review Actions Bar -->
                    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-xs p-5 space-y-3">
                        <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Defense Review Actions</span>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                            <a
                                href="{{ $selectedReviewPaper['evaluationUrl'] }}"
                                class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i class="ph ph-check-circle text-base"></i>
                                <span>Open Official Scoring (RES-036)</span>
                            </a>
                            <a
                                href="{{ $selectedReviewPaper['downloadUrl'] }}"
                                class="px-5 py-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i class="ph ph-download-simple text-base"></i>
                                <span>Download Original Manuscript</span>
                            </a>
                            @if ($selectedReviewPaper['fileType'] === 'PDF')
                                <button
                                    type="button"
                                    @click="downloadAnnotatedCopy()"
                                    :disabled="isDownloadingAnnotated || recommendationComments.length === 0"
                                    class="px-5 py-3 bg-amber-50 border border-amber-200 hover:bg-amber-100 text-amber-900 text-xs font-bold rounded-xl shadow-2xs transition flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <i class="ph text-base" :class="isDownloadingAnnotated ? 'ph-spinner animate-spin' : 'ph-note-pencil'"></i>
                                    <span x-text="isDownloadingAnnotated ? 'Creating Annotated PDF...' : 'Download Annotated PDF'">Download Annotated PDF</span>
                                </button>
                            @endif
                            <a
                                href="{{ route('panelist.dashboard', ['tab' => 'assigned-papers']) }}"
                                class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i class="ph ph-list-bullets text-base"></i>
                                <span>All Assigned Papers</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-8 py-16 text-center shadow-sm">
                        <i class="ph ph-file-magnifying-glass text-5xl text-slate-300"></i>
                        <h2 class="mt-4 text-base font-bold text-slate-800">No assigned manuscript available for review</h2>
                        <p class="mt-1 text-xs text-slate-500">You will see papers here once you are assigned to a scheduled defense panel.</p>
                        <a href="{{ route('panelist.dashboard', ['tab' => 'assigned-papers']) }}" class="mt-5 inline-flex rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">
                            View Assigned Papers
                        </a>
                    </div>
                @endif
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

                            <template x-if="sched.can_initiate_res036">
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">
                                        <i class="ph ph-check-circle text-emerald-600"></i> Eligible to Initiate RES-036 Evaluation
                                    </span>
                                    <a :href="sched.res036_url" class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-xs transition-all inline-flex items-center gap-1.5 cursor-pointer">
                                        <i class="ph ph-file-text"></i> Open RES-036 Form
                                    </a>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Evaluated</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => d.status === 'Evaluated').length">0</span>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">Pending Defense</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => d.status === 'Pending Defense').length">0</span>
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
                            <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider block">For Review</span>
                            <span class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1 block" x-text="repositoryDocuments.filter(d => ['For Review', 'Under Review'].includes(d.status)).length">0</span>
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
                            <option value="pending defense">Pending Defense</option>
                            <option value="for review">For Review</option>
                            <option value="under review">Under Review</option>
                            <option value="evaluated">Evaluated</option>
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
                                <a :href="doc.viewUrl" target="_blank" rel="noopener" class="py-2 rounded-xl bg-white border border-[#0e5c3a] hover:bg-emerald-50 text-[#0e5c3a] text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1">
                                    <i class="ph ph-eye"></i> View
                                </a>
                                <a :href="doc.downloadUrl" class="py-2 rounded-xl bg-white border border-blue-500 hover:bg-blue-50 text-blue-600 text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1">
                                    <i class="ph ph-download"></i> Download
                                </a>
                                <a :href="doc.evaluationUrl" class="py-2 rounded-xl bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold transition-all cursor-pointer text-center flex items-center justify-center gap-1 shadow-sm">
                                    <i class="ph ph-check-square"></i> Evaluate
                                </a>
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
            
            <!-- TAB: Notifications Center -->
            <div x-show="activeTab === 'notifications'" x-cloak class="space-y-8 animate-fade-in">
                <x-notifications.center
                    :notifications="$userNotifications ?? collect()"
                    :unread-count="$userUnreadCount ?? 0"
                    :filter="$notificationFilter ?? 'all'"
                    :dashboard-route="route('panelist.dashboard')"
                />
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings')
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
