@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'approvals', 'classes', 'join-requests', 'monitoring', 'screening', 'defenses', 'statistics', 'repository', 'forms', 'notifications', 'settings'];
    $initialTab = in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard';
    $classes = $classes ?? $researchClasses ?? collect();
    $classRequestStats = $classRequestStats ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
    $pendingTitleProposalScreeningCount = collect($titleProposalScreeningQueue ?? [])->count();
    $defenseSchedulingReadyCount = collect($adviserApprovedDefenseDocuments ?? [])->count();
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

    $defenseListData = collect($defenses ?? [])->map(fn($d) => [
        'id' => $d['id'] ?? null,
        'defense_id' => $d['defense_id'] ?? null,
        'type' => $d['defense_type_label'] ?? 'Research Defense',
        'status' => match ($d['defense_status'] ?? null) {
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Scheduled',
        },
        'title' => $d['research_title'] ?? ($d['group_name'] ?? 'Research Title'),
        'student' => $d['group_name'] ?? ('Group #' . ($d['group_id'] ?? '')),
        'date' => $d['formatted_date'] ?? 'TBA',
        'time' => $d['formatted_time'] ?? 'TBA',
        'starts_at' => $d['starts_at'] ?? null,
        'presentation_order' => $d['presentation_order'] ?? null,
        'presentation_order_label' => $d['presentation_order_label'] ?? null,
        'venue' => $d['room_name'] ?? ($d['room_code'] ?? 'TBA'),
        'panel' => array_map(fn($p) => [
            'name' => $p['name'],
            'position' => $p['position'] ?? null,
            'position_label' => $p['position_label'] ?? 'Panel Member',
        ], $d['panelists'] ?? []),
        'expected_current_schedule_id' => $d['id'] ?? null,
        'can_manage' => $d['can_manage'] ?? false,
        'evaluation_round' => $d['evaluation_round'] ?? null,
    ])->values()->all();
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
(() => {
    const registerFacilitatorDashboard = () => {
        if (!window.Alpine) return;
        window.Alpine.data('facilitatorDashboard', (config) => ({
            activeTab: config.initialTab,
            activeFormPhase: config.initialFormPhase,
            activeOfficialForm: config.initialOfficialForm,
            officialForms: config.officialForms,
            formsExpanded: config.initialTab === 'forms',
            dashboardUrl: config.dashboardUrl,
            showVenueManager: config.showVenueManager,
            showScheduleModal: config.showScheduleModal,
            defenseSchedulingGroups: config.defenseSchedulingGroups || [],
            defensePanelCandidates: config.defensePanelCandidates || [],
            defenseRooms: config.defenseRooms || [],
            facilitatorClasses: config.facilitatorClasses || [],
            scheduleForm: config.initialScheduleForm || {
                groupId: '',
                type: 'title_presentation',
                chairpersonId: '',
                memberOneId: '',
                memberTwoId: '',
            },
            defenseList: config.defenseList || [],
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
    showApprovalModal: false,
    selectedApproval: null,
    copiedCode: null,
    
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
    ],

    userManagementSubTab: 'all',
    userSearchQuery: '',
    userRoleFilter: 'All',
    newUserForm: {
        name: '',
        email: '',
        role: 'research-adviser',
        department: 'College of Information Technology',
        tempPassword: true
    },
    userList: [
        { name: 'System Administrator', email: 'admin@ndmu.edu.ph', role: 'system-administrator', status: 'Active', department: 'Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Dr. Lourdes Castillo', email: 'l.castillo@ndmu.edu.ph', role: 'college-dean', status: 'Active', department: 'Office of the College Dean', created: '2024-01-01', isSystem: true },
        { name: 'Dr. Rosario Dela Paz', email: 'r.dela-paz@ndmu.edu.ph', role: 'research-facilitator', status: 'Active', department: 'College of Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Engr. Jose Montero', email: 'j.montero@ndmu.edu.ph', role: 'research-facilitator', status: 'Active', department: 'College of Engineering', created: '2024-01-01', isSystem: true },
        { name: 'Dr. Reyna Garcia', email: 'r.garcia@ndmu.edu.ph', role: 'research-adviser', status: 'Active', department: 'College of Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Dr. Michael Tan', email: 'm.tan@ndmu.edu.ph', role: 'research-adviser', status: 'Active', department: 'College of Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Prof. Lucia Fernandez', email: 'l.fernandez@ndmu.edu.ph', role: 'research-adviser', status: 'Active', department: 'College of Engineering', created: '2024-01-01', isSystem: true },
        { name: 'Dr. Antonio Santos', email: 'a.santos@ndmu.edu.ph', role: 'panelist', status: 'Active', department: 'College of Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Engr. Gloria Reyes', email: 'g.reyes@ndmu.edu.ph', role: 'panelist', status: 'Active', department: 'College of Engineering', created: '2024-01-01', isSystem: true },
        { name: 'Prof. Ramon Bautista', email: 'r.bautista@ndmu.edu.ph', role: 'panelist', status: 'Active', department: 'College of Information Technology', created: '2024-01-01', isSystem: true },
        { name: 'Maria Santos', email: 'maria.santos@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2024-08-12' },
        { name: 'Carlo Mendoza', email: 'carlo.mendoza@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2024-08-12' },
        { name: 'Anna Lim', email: 'anna.lim@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2024-08-15' },
        { name: 'Felix Torres', email: 'felix.torres@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Engineering', created: '2024-08-20' },
        { name: 'Sofia Herrera', email: 'sofia.herrera@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Engineering', created: '2025-08-10' },
        { name: 'Rafael Ocampo', email: 'rafael.ocampo@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Engineering', created: '2025-08-10' },
        { name: 'Isabelle Garcia', email: 'isabelle.garcia@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Engineering', created: '2025-08-11' },
        { name: 'Marco Villanueva', email: 'marco.villanueva@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2025-08-12' },
        { name: 'Sarah Gonzales', email: 'sarah.gonzales@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2024-08-12' },
        { name: 'Elena Rodriguez', email: 'elena.rodriguez@ndmu.edu.ph', role: 'student-researcher', status: 'Active', department: 'College of Information Technology', created: '2024-09-05' },
        { name: 'Juan Dela Cruz', email: 'juan.delacruz@ndmu.edu.ph', role: 'student-researcher', status: 'Pending', department: 'College of Engineering', created: '2026-05-28' },
        { name: 'Ana Reyes', email: 'ana.reyes@ndmu.edu.ph', role: 'student-researcher', status: 'Pending', department: 'College of Information Technology', created: '2026-05-30' },
        { name: 'Kevin Aguila', email: 'kevin.aguila@ndmu.edu.ph', role: 'student-researcher', status: 'Pending', department: 'College of Engineering', created: '2026-06-01' },
        { name: 'Clara Nieto', email: 'clara.nieto@ndmu.edu.ph', role: 'student-researcher', status: 'Pending', department: 'College of Information Technology', created: '2026-06-01' },
        { name: 'Dante Flores', email: 'dante.flores@ndmu.edu.ph', role: 'student-researcher', status: 'Pending', department: 'College of Engineering', created: '2026-06-02' },
        { name: 'Dr. Miguel Torres', email: 'newadviser@ndmu.edu.ph', role: 'research-adviser', status: 'Active', department: 'College of Information Technology', created: '2026-06-01', hasTempPassword: true }
    ],

    get totalUsersCount() {
        return this.userList.length;
    },
    get pendingUsersCount() {
        return this.userList.filter(u => u.status === 'Pending').length;
    },
    get activeUsersCount() {
        return this.userList.filter(u => u.status === 'Active').length;
    },
    get rejectedUsersCount() {
        return this.userList.filter(u => u.status === 'Rejected').length;
    },

    get filteredUserList() {
        let list = this.userList;
        
        // Tab filtering
        if (this.userManagementSubTab === 'pending') {
            list = list.filter(u => u.status === 'Pending');
        }
        
        // Role filtering
        if (this.userRoleFilter !== 'All') {
            list = list.filter(u => {
                if (this.userRoleFilter === 'system-administrator') return u.role === 'system-administrator';
                if (this.userRoleFilter === 'college-dean') return u.role === 'college-dean';
                if (this.userRoleFilter === 'research-facilitator') return u.role === 'research-facilitator';
                if (this.userRoleFilter === 'research-adviser') return u.role === 'research-adviser';
                if (this.userRoleFilter === 'panelist') return u.role === 'panelist';
                if (this.userRoleFilter === 'student-researcher') return u.role === 'student-researcher';
                return true;
            });
        }
        
        // Search filtering
        if (this.userSearchQuery.trim() !== '') {
            let q = this.userSearchQuery.toLowerCase();
            list = list.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
        }
        
        return list;
    },

    approveUser(email) {
        let user = this.userList.find(u => u.email === email);
        if (user) {
            user.status = 'Active';
            alert(`Approved registration for ${user.name}`);
        }
    },

    rejectUser(email) {
        let userIndex = this.userList.findIndex(u => u.email === email);
        if (userIndex !== -1) {
            let user = this.userList[userIndex];
            user.status = 'Rejected';
            alert(`Rejected registration for ${user.name}`);
        }
    },

    deleteUser(email) {
        if (confirm(`Are you sure you want to delete user: ${email}?`)) {
            this.userList = this.userList.filter(u => u.email !== email);
        }
    },

    submitCreateUser() {
        if (!this.newUserForm.name || !this.newUserForm.email) {
            alert('Please fill in Name and Email fields.');
            return;
        }
        
        // Push user to list
        this.userList.push({
            name: this.newUserForm.name,
            email: this.newUserForm.email,
            role: this.newUserForm.role,
            status: 'Active',
            department: this.newUserForm.department,
            created: new Date().toISOString().split('T')[0],
            hasTempPassword: this.newUserForm.tempPassword
        });

        alert(`Successfully created user: ${this.newUserForm.name}`);

        // Reset form
        this.newUserForm.name = '';
        this.newUserForm.email = '';
        
        // Switch back to 'All Users' tab
        this.userManagementSubTab = 'all';
    },

    defenseTypeFilter: 'All',
    defenseStatusFilter: 'All',

    // --- Redesigned Committee Assignment State ---
    showClassCommitteeModal: false,
    showCustomGroupModal: false,
    classCommitteeForm: {
        classId: '',
        defenseType: 'proposal_defense',
        type: 'proposal_defense',
        chairpersonId: '',
        memberOneId: '',
        panelMember1Id: '',
        memberTwoId: '',
        panelMember2Id: '',
        applyScope: 'all',
        selectedGroupIds: [],
        overrideCustom: false,
        overwriteCustom: false,
        searchQuery: '',
        loading: false,
        errorMessage: '',
        successMessage: '',
        groupsData: [],
        classDefault: null,
    },
    customGroupForm: {
        group: null,
        defenseType: 'proposal_defense',
        chairpersonId: '',
        memberOneId: '',
        panelMember1Id: '',
        memberTwoId: '',
        panelMember2Id: '',
        loading: false,
        errorMessage: '',
    },

    get candidateFacultyList() {
        return this.defensePanelCandidates || [];
    },

    get classCommitteeGroups() {
        return this.classCommitteeForm.groupsData || [];
    },

    get customGroupData() {
        return this.customGroupForm.group;
    },

    get isSubmittingClassCommittee() {
        return this.classCommitteeForm.loading;
    },

    openClassCommitteeModal(classId = null, defenseType = 'proposal_defense') {
        if (!classId && this.facilitatorClasses.length > 0) {
            classId = this.facilitatorClasses[0].id;
        }
        this.classCommitteeForm.classId = classId ? String(classId) : '';
        this.classCommitteeForm.defenseType = defenseType;
        this.classCommitteeForm.type = defenseType;
        this.classCommitteeForm.errorMessage = '';
        this.classCommitteeForm.successMessage = '';
        this.showClassCommitteeModal = true;
        this.fetchClassCommitteeData();
    },

    onClassCommitteeClassChange() {
        this.fetchClassCommitteeData();
    },

    loadClassCommittees() {
        if (this.classCommitteeForm.type) {
            this.classCommitteeForm.defenseType = this.classCommitteeForm.type;
        }
        this.fetchClassCommitteeData();
    },

    async fetchClassCommitteeData() {
        if (!this.classCommitteeForm.classId) return;
        this.classCommitteeForm.loading = true;
        this.classCommitteeForm.errorMessage = '';
        const currentType = this.classCommitteeForm.type || this.classCommitteeForm.defenseType || 'proposal_defense';
        try {
            const resp = await fetch(`/facilitator/classes/${this.classCommitteeForm.classId}/defense-committees?defense_type=${currentType}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error('Failed to fetch class committee data.');
            const data = await resp.json();
            this.classCommitteeForm.groupsData = data.groups || [];
            this.classCommitteeForm.classDefault = data.class_committee || null;
            if (data.class_committee) {
                const chairId = String(data.class_committee.chairperson?.id || '');
                const m1 = data.class_committee.members?.find(m => m.position === 'member_1') || data.class_committee.members?.[0];
                const m2 = data.class_committee.members?.find(m => m.position === 'member_2') || data.class_committee.members?.[1];
                const m1Id = String(m1?.id || '');
                const m2Id = String(m2?.id || '');
                this.classCommitteeForm.chairpersonId = chairId;
                this.classCommitteeForm.memberOneId = m1Id;
                this.classCommitteeForm.panelMember1Id = m1Id;
                this.classCommitteeForm.memberTwoId = m2Id;
                this.classCommitteeForm.panelMember2Id = m2Id;
            } else {
                this.classCommitteeForm.chairpersonId = '';
                this.classCommitteeForm.memberOneId = '';
                this.classCommitteeForm.panelMember1Id = '';
                this.classCommitteeForm.memberTwoId = '';
                this.classCommitteeForm.panelMember2Id = '';
            }
            this.classCommitteeForm.selectedGroupIds = this.classCommitteeForm.groupsData.map(g => g.id);
        } catch (e) {
            this.classCommitteeForm.errorMessage = e.message || 'Error loading committee data.';
        } finally {
            this.classCommitteeForm.loading = false;
        }
    },

    selectAllCommitteeGroups() {
        this.classCommitteeForm.selectedGroupIds = (this.classCommitteeForm.groupsData || []).map(g => g.id);
    },

    deselectAllCommitteeGroups() {
        this.classCommitteeForm.selectedGroupIds = [];
    },

    get filteredClassCommitteeGroups() {
        const q = (this.classCommitteeForm.searchQuery || '').trim().toLowerCase();
        if (!q) return this.classCommitteeForm.groupsData || [];
        return (this.classCommitteeForm.groupsData || []).filter(g => 
            (g.name && g.name.toLowerCase().includes(q)) || 
            (g.title && g.title.toLowerCase().includes(q)) || 
            (g.adviser_name && g.adviser_name.toLowerCase().includes(q))
        );
    },

    toggleSelectAllGroups() {
        const filtered = this.filteredClassCommitteeGroups.map(g => g.id);
        const allSelected = filtered.length > 0 && filtered.every(id => this.classCommitteeForm.selectedGroupIds.includes(id));
        if (allSelected) {
            this.classCommitteeForm.selectedGroupIds = this.classCommitteeForm.selectedGroupIds.filter(id => !filtered.includes(id));
        } else {
            const set = new Set([...this.classCommitteeForm.selectedGroupIds, ...filtered]);
            this.classCommitteeForm.selectedGroupIds = Array.from(set);
        }
    },

    async submitClassCommittee() {
        const chair = this.classCommitteeForm.chairpersonId;
        const p1 = this.classCommitteeForm.panelMember1Id || this.classCommitteeForm.memberOneId;
        const p2 = this.classCommitteeForm.panelMember2Id || this.classCommitteeForm.memberTwoId;
        if (!chair || !p1 || !p2) {
            this.classCommitteeForm.errorMessage = 'Please select a Chairperson and two distinct Panel Members.';
            return;
        }
        if (new Set([chair, p1, p2]).size !== 3) {
            this.classCommitteeForm.errorMessage = 'The Chairperson and two Panel Members must be three different faculty members.';
            return;
        }
        this.classCommitteeForm.loading = true;
        this.classCommitteeForm.errorMessage = '';
        this.classCommitteeForm.successMessage = '';
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const targetGroupIds = this.classCommitteeForm.applyScope === 'all'
                ? []
                : this.classCommitteeForm.selectedGroupIds;
            const resp = await fetch(`/facilitator/classes/${this.classCommitteeForm.classId}/defense-committees`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    defense_type: this.classCommitteeForm.type || this.classCommitteeForm.defenseType,
                    chairperson_user_id: chair,
                    panel_user_ids: [p1, p2],
                    group_ids: targetGroupIds,
                    override_custom: this.classCommitteeForm.overwriteCustom || this.classCommitteeForm.overrideCustom,
                })
            });
            const data = await resp.json();
            if (!resp.ok) {
                throw new Error(data.message || 'Failed to assign committee.');
            }
            this.classCommitteeForm.successMessage = data.message || 'Committee assigned successfully.';
            await this.fetchClassCommitteeData();
        } catch (e) {
            this.classCommitteeForm.errorMessage = e.message;
        } finally {
            this.classCommitteeForm.loading = false;
        }
    },

    submitClassCommittees() {
        return this.submitClassCommittee();
    },

    openSingleGroupCustomize(group) {
        this.openCustomGroupModal(group);
    },

    openCustomGroupModal(group) {
        this.customGroupForm.group = group;
        this.customGroupForm.defenseType = this.classCommitteeForm.type || this.classCommitteeForm.defenseType;
        const chair = group.chairperson_id || group.committee?.chairperson_id || '';
        const m1 = group.member_1_id || group.committee?.panel_member_1_id || '';
        const m2 = group.member_2_id || group.committee?.panel_member_2_id || '';
        this.customGroupForm.chairpersonId = chair ? String(chair) : '';
        this.customGroupForm.memberOneId = m1 ? String(m1) : '';
        this.customGroupForm.panelMember1Id = m1 ? String(m1) : '';
        this.customGroupForm.memberTwoId = m2 ? String(m2) : '';
        this.customGroupForm.panelMember2Id = m2 ? String(m2) : '';
        this.customGroupForm.errorMessage = '';
        this.showCustomGroupModal = true;
    },

    async submitCustomGroupCommittee() {
        const chair = this.customGroupForm.chairpersonId;
        const p1 = this.customGroupForm.panelMember1Id || this.customGroupForm.memberOneId;
        const p2 = this.customGroupForm.panelMember2Id || this.customGroupForm.memberTwoId;
        if (!chair || !p1 || !p2) {
            this.customGroupForm.errorMessage = 'Please select a Chairperson and two distinct Panel Members.';
            return;
        }
        if (new Set([chair, p1, p2]).size !== 3) {
            this.customGroupForm.errorMessage = 'The Chairperson and two Panel Members must be three different faculty members.';
            return;
        }
        this.customGroupForm.loading = true;
        this.customGroupForm.errorMessage = '';
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/facilitator/classes/${this.classCommitteeForm.classId}/groups/${this.customGroupForm.group.id}/defense-committee`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    defense_type: this.customGroupForm.defenseType,
                    chairperson_user_id: chair,
                    panel_user_ids: [p1, p2],
                    is_custom: true,
                })
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'Failed to update custom committee.');
            this.showCustomGroupModal = false;
            await this.fetchClassCommitteeData();
        } catch (e) {
            this.customGroupForm.errorMessage = e.message;
        } finally {
            this.customGroupForm.loading = false;
        }
    },

    saveCustomGroupCommittee() {
        return this.submitCustomGroupCommittee();
    },

    async revertCustomGroupCommittee(group) {
        if (!confirm(`Revert ${(group.group_name || group.name)}'s committee back to the class default?`)) return;
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/facilitator/classes/${this.classCommitteeForm.classId}/groups/${group.id}/defense-committee`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    defense_type: this.classCommitteeForm.type || this.classCommitteeForm.defenseType,
                })
            });
            if (!resp.ok) {
                const data = await resp.json();
                throw new Error(data.message || 'Failed to revert committee.');
            }
            this.showCustomGroupModal = false;
            await this.fetchClassCommitteeData();
        } catch (e) {
            alert(e.message);
        }
    },

    resetGroupToClassCommittee() {
        return this.revertCustomGroupCommittee(this.customGroupForm.group);
    },

    // --- Redesigned Bulk Scheduling State ---
    showBulkScheduleModal: false,
    bulkClassGroups: [],
    bulkScheduleForm: {
        classId: '',
        defenseType: 'proposal_defense',
        type: 'proposal_defense',
        date: '',
        sessionDate: '',
        roomId: '',
        startsAtTime: '07:00',
        sessionStartTime: '07:00',
        endsAtTime: '18:00',
        sessionEndTime: '18:00',
        notes: '',
        selectedGroupIds: [],
        orderedGroups: [],
        loading: false,
        checkingConflicts: false,
        conflicts: [],
        errorMessage: '',
        searchQuery: '',
        classGroupsData: [],
    },

    get bulkConflictErrors() {
        return this.bulkScheduleForm.conflicts || [];
    },

    get bulkSelectedCount() {
        return (this.bulkClassGroups || []).filter(g => g.selected).length;
    },

    get groupsWithMissingCommittees() {
        return (this.bulkClassGroups || []).filter(g => g.selected && (!g.is_complete || !g.chairperson_id || !g.member_1_id || !g.member_2_id));
    },

    get isSubmittingBulkSchedule() {
        return this.bulkScheduleForm.loading;
    },

    openBulkScheduleModal() {
        if (!this.bulkScheduleForm.classId && this.facilitatorClasses.length > 0) {
            this.bulkScheduleForm.classId = String(this.facilitatorClasses[0].id);
        }
        if (!this.bulkScheduleForm.roomId && this.defenseRooms.length > 0) {
            this.bulkScheduleForm.roomId = String(this.defenseRooms[0].id);
        }
        if (!this.bulkScheduleForm.date) {
            const tmrw = new Date();
            tmrw.setDate(tmrw.getDate() + 1);
            const dateStr = tmrw.toISOString().split('T')[0];
            this.bulkScheduleForm.date = dateStr;
            this.bulkScheduleForm.sessionDate = dateStr;
        }
        this.bulkScheduleForm.sessionStartTime = this.bulkScheduleForm.startsAtTime || '07:00';
        this.bulkScheduleForm.sessionEndTime = this.bulkScheduleForm.endsAtTime || '18:00';
        this.bulkScheduleForm.type = this.bulkScheduleForm.defenseType || 'proposal_defense';
        this.bulkScheduleForm.conflicts = [];
        this.bulkScheduleForm.errorMessage = '';
        this.showBulkScheduleModal = true;
        this.fetchBulkClassGroups();
    },

    onBulkClassChange() {
        this.fetchBulkClassGroups();
    },

    loadBulkGroups() {
        if (this.bulkScheduleForm.type) {
            this.bulkScheduleForm.defenseType = this.bulkScheduleForm.type;
        }
        this.fetchBulkClassGroups();
    },

    async fetchBulkClassGroups() {
        if (!this.bulkScheduleForm.classId) return;
        this.bulkScheduleForm.loading = true;
        const currentType = this.bulkScheduleForm.type || this.bulkScheduleForm.defenseType || 'proposal_defense';
        try {
            const resp = await fetch(`/facilitator/classes/${this.bulkScheduleForm.classId}/defense-committees?defense_type=${currentType}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error('Failed to fetch class groups for scheduling.');
            const data = await resp.json();
            this.bulkScheduleForm.classGroupsData = data.groups || [];
            this.bulkClassGroups = (data.groups || []).map(g => ({
                ...g,
                selected: Boolean(g.is_complete && g.res033_complete),
            }));
            this.bulkScheduleForm.orderedGroups = this.bulkClassGroups.filter(g => g.selected);
            this.triggerConflictCheck();
        } catch (e) {
            this.bulkScheduleForm.errorMessage = e.message;
        } finally {
            this.bulkScheduleForm.loading = false;
        }
    },

    selectedOrderNumber(grp) {
        const selected = (this.bulkClassGroups || []).filter(g => g.selected);
        const idx = selected.findIndex(g => g.id === grp.id);
        return idx >= 0 ? idx + 1 : '—';
    },

    selectAllBulkGroups() {
        (this.bulkClassGroups || []).forEach(g => g.selected = Boolean(g.res033_complete));
        this.triggerConflictCheck();
    },

    deselectAllBulkGroups() {
        (this.bulkClassGroups || []).forEach(g => g.selected = false);
        this.triggerConflictCheck();
    },

    resetBulkOrder() {
        this.fetchBulkClassGroups();
    },

    moveBulkGroupUp(index) {
        if (index <= 0) return;
        const temp = this.bulkClassGroups[index - 1];
        this.bulkClassGroups[index - 1] = this.bulkClassGroups[index];
        this.bulkClassGroups[index] = temp;
        this.bulkClassGroups = [...this.bulkClassGroups];
        this.triggerConflictCheck();
    },

    moveBulkGroupDown(index) {
        if (index >= this.bulkClassGroups.length - 1) return;
        const temp = this.bulkClassGroups[index + 1];
        this.bulkClassGroups[index + 1] = this.bulkClassGroups[index];
        this.bulkClassGroups[index] = temp;
        this.bulkClassGroups = [...this.bulkClassGroups];
        this.triggerConflictCheck();
    },

    moveGroupUp(index) {
        this.moveBulkGroupUp(index);
    },

    moveGroupDown(index) {
        this.moveBulkGroupDown(index);
    },

    checkBulkConflicts() {
        this.triggerConflictCheck();
    },

    conflictCheckTimer: null,
    triggerConflictCheck() {
        clearTimeout(this.conflictCheckTimer);
        this.conflictCheckTimer = setTimeout(() => this.runConflictCheck(), 350);
    },

    async runConflictCheck() {
        const classId = this.bulkScheduleForm.classId;
        const date = this.bulkScheduleForm.sessionDate || this.bulkScheduleForm.date;
        const roomId = this.bulkScheduleForm.roomId;
        const startTime = this.bulkScheduleForm.sessionStartTime || this.bulkScheduleForm.startsAtTime || '07:00';
        const endTime = this.bulkScheduleForm.sessionEndTime || this.bulkScheduleForm.endsAtTime || '18:00';
        const selected = (this.bulkClassGroups || []).filter(g => g.selected);

        if (!classId || !date || !roomId || selected.length === 0) {
            this.bulkScheduleForm.conflicts = [];
            return;
        }
        this.bulkScheduleForm.checkingConflicts = true;
        try {
            const startsAt = `${date} ${startTime}:00`;
            const endsAt = `${date} ${endTime}:00`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/facilitator/defenses/bulk/check-conflicts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    research_class_id: classId,
                    defense_type: this.bulkScheduleForm.type || this.bulkScheduleForm.defenseType,
                    room_id: roomId,
                    starts_at: startsAt,
                    ends_at: endsAt,
                    ordered_group_ids: selected.map(g => g.id),
                })
            });
            const res = await resp.json();
            this.bulkScheduleForm.conflicts = res.conflicts || [];
        } catch (e) {
            console.error(e);
        } finally {
            this.bulkScheduleForm.checkingConflicts = false;
        }
    },

    async submitBulkSchedule() {
        const selected = (this.bulkClassGroups || []).filter(g => g.selected);
        if (selected.length === 0) {
            alert('Please select at least one research group to schedule.');
            return;
        }
        const incomplete = selected.filter(g => !g.is_complete || !g.chairperson_id || !g.member_1_id || !g.member_2_id);
        if (incomplete.length > 0) {
            alert(`Cannot schedule: ${incomplete.length} selected group(s) do not have complete committee assignments.`);
            return;
        }
        const date = this.bulkScheduleForm.sessionDate || this.bulkScheduleForm.date;
        const startTime = this.bulkScheduleForm.sessionStartTime || this.bulkScheduleForm.startsAtTime || '07:00';
        const endTime = this.bulkScheduleForm.sessionEndTime || this.bulkScheduleForm.endsAtTime || '18:00';
        if (!date || !this.bulkScheduleForm.roomId) {
            alert('Please select a date and presentation room.');
            return;
        }
        this.bulkScheduleForm.loading = true;
        this.bulkScheduleForm.errorMessage = '';
        try {
            const startsAt = `${date} ${startTime}:00`;
            const endsAt = `${date} ${endTime}:00`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/facilitator/defenses/bulk`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    research_class_id: this.bulkScheduleForm.classId,
                    defense_type: this.bulkScheduleForm.type || this.bulkScheduleForm.defenseType,
                    room_id: this.bulkScheduleForm.roomId,
                    starts_at: startsAt,
                    ends_at: endsAt,
                    ordered_group_ids: selected.map(g => g.id),
                    notes: this.bulkScheduleForm.notes,
                })
            });
            const data = await resp.json();
            if (!resp.ok) {
                throw new Error(data.message || 'Failed to create bulk defense schedule.');
            }
            window.location.assign('/facilitator/dashboard?tab=defenses');
        } catch (e) {
            alert(e.message);
        } finally {
            this.bulkScheduleForm.loading = false;
        }
    },

    get selectedDefenseGroup() {
        return this.defenseSchedulingGroups.find(group => String(group.id) === String(this.scheduleForm.groupId)) || null;
    },

    get eligibleDefenseSchedulingGroups() {
        const defenseType = this.scheduleForm.type || 'title_presentation';

        return (this.defenseSchedulingGroups || []).filter(
            group => Boolean(group.res033_eligibility?.[defenseType])
        );
    },

    get unavailableDefenseGroupCount() {
        return Math.max(0, (this.defenseSchedulingGroups || []).length - this.eligibleDefenseSchedulingGroups.length);
    },

    onDefenseTypeChange() {
        if (this.scheduleForm.groupId && !this.eligibleDefenseSchedulingGroups.some(
            group => String(group.id) === String(this.scheduleForm.groupId)
        )) {
            this.scheduleForm.groupId = '';
            this.scheduleForm.chairpersonId = '';
            this.scheduleForm.memberOneId = '';
            this.scheduleForm.memberTwoId = '';

            return;
        }

        this.onDefenseGroupChange();
    },

    get departmentFilteredPanelCandidates() {
        const group = this.selectedDefenseGroup;
        if (!group || !group.department) {
            return this.defensePanelCandidates;
        }
        const groupDept = group.department.toLowerCase();

        // Specific department normalization
        const isCsd = groupDept.includes('computer studies') || groupDept === 'csd';
        const isEece = groupDept.includes('electrical') || groupDept.includes('electronics') || groupDept === 'eece';
        const isCed = groupDept.includes('civil') || groupDept === 'ced';
        const isAd = groupDept.includes('architect') || groupDept === 'ad';

        const matched = this.defensePanelCandidates.filter(candidate => {
            if (!candidate.department) return false;
            const candDept = candidate.department.toLowerCase();

            if (isCsd) {
                return candDept.includes('computer studies') || candDept === 'csd';
            }
            if (isEece) {
                return candDept.includes('electrical') || candDept.includes('electronics') || candDept === 'eece';
            }
            if (isCed) {
                return candDept.includes('civil') || candDept === 'ced';
            }
            if (isAd) {
                return candDept.includes('architect') || candDept === 'ad';
            }

            return candDept === groupDept || candDept.includes(groupDept) || groupDept.includes(candDept);
        });

        if (matched.length === 0) {
            return this.defensePanelCandidates;
        }

        const defenseType = this.scheduleForm.type || 'title_presentation';
        const committee = group.committee_assignments?.[defenseType];
        const assignedIds = [committee?.chairperson_id, committee?.member_1_id, committee?.member_2_id]
            .filter(Boolean)
            .map(String);
        const assignedOutsideDepartment = this.defensePanelCandidates.filter(
            candidate => assignedIds.includes(String(candidate.id)) && !matched.some(item => String(item.id) === String(candidate.id))
        );

        return [...matched, ...assignedOutsideDepartment];
    },

    get eligibleChairpersons() {
        // Advisers are fully eligible to be selected as Chairperson
        return this.departmentFilteredPanelCandidates;
    },

    onDefenseGroupChange() {
        const group = this.selectedDefenseGroup;
        if (!group) {
            this.scheduleForm.chairpersonId = '';
            this.scheduleForm.memberOneId = '';
            this.scheduleForm.memberTwoId = '';

            return;
        }

        const defenseType = this.scheduleForm.type || 'title_presentation';
        const committee = group.committee_assignments?.[defenseType] || null;
        const hasSavedCommittee = Boolean(committee?.source_label);
        const chairpersonId = hasSavedCommittee ? committee.chairperson_id : group.chairperson_id;
        const memberOneId = hasSavedCommittee ? committee.member_1_id : group.member_1_id;
        const memberTwoId = hasSavedCommittee ? committee.member_2_id : group.member_2_id;

        group.active_committee_label = hasSavedCommittee ? committee.source_label : (group.has_title_chairperson ? 'Title presentation committee' : 'Previous defense committee');
        group.active_chairperson_id = chairpersonId;
        group.active_chairperson_name = hasSavedCommittee ? committee.chairperson_name : group.chairperson_name;
        group.active_member_1_id = memberOneId;
        group.active_member_1_name = hasSavedCommittee ? committee.member_1_name : group.member_1_name;
        group.active_member_2_id = memberTwoId;
        group.active_member_2_name = hasSavedCommittee ? committee.member_2_name : group.member_2_name;

        // Automatically populate the saved committee for the selected defense stage.
        const validChairIds = this.eligibleChairpersons.map(c => String(c.id));
        if (chairpersonId && validChairIds.includes(String(chairpersonId))) {
            this.scheduleForm.chairpersonId = String(chairpersonId);
        } else if (!validChairIds.includes(String(this.scheduleForm.chairpersonId))) {
            this.scheduleForm.chairpersonId = '';
        }

        // Pre-populate panel members if available and valid in department
        const validPanelIds = this.departmentFilteredPanelCandidates.map(c => String(c.id));
        if (memberOneId && validPanelIds.includes(String(memberOneId))) {
            this.scheduleForm.memberOneId = String(memberOneId);
        } else if (!validPanelIds.includes(String(this.scheduleForm.memberOneId))) {
            this.scheduleForm.memberOneId = '';
        }

        if (memberTwoId && validPanelIds.includes(String(memberTwoId))) {
            this.scheduleForm.memberTwoId = String(memberTwoId);
        } else if (!validPanelIds.includes(String(this.scheduleForm.memberTwoId))) {
            this.scheduleForm.memberTwoId = '';
        }
    },

    get totalScheduledCount() {
        return this.defenseList.filter(d => d.status !== 'Completed').length;
    },
    get thisWeekCount() {
        const now = new Date();
        const startOfWeek = new Date(now);
        startOfWeek.setHours(0, 0, 0, 0);
        startOfWeek.setDate(now.getDate() - now.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 7);

        return this.defenseList.filter(defense => {
            const startsAt = defense.starts_at ? new Date(defense.starts_at) : null;
            return defense.status === 'Scheduled' && startsAt && startsAt >= startOfWeek && startsAt < endOfWeek;
        }).length;
    },
    get pendingDefenseCount() {
        return this.defenseList.filter(d => d.status === 'Pending').length;
    },
    get completedDefenseCount() {
        return this.defenseList.filter(d => d.status === 'Completed').length;
    },

    get filteredDefenseList() {
        let list = this.defenseList;
        if (this.defenseTypeFilter !== 'All') {
            list = list.filter(d => d.type === this.defenseTypeFilter);
        }
        if (this.defenseStatusFilter !== 'All') {
            list = list.filter(d => d.status === this.defenseStatusFilter);
        }
        return list;
    },

    openScheduleModal() {
        this.showScheduleModal = true;
    },

    reportsApprovedCount: 8,
    reportsRevisionsCount: 5,
    reportsCommentsCount: 12,
    reportsCriticalCount: 2,
    reportsNewCommentText: '',
    reportsCommentsList: [
        {
            id: 1,
            author: 'Dr. Maria Santos',
            role: 'Adviser',
            time: '2 hours ago',
            content: 'Please expand this section with more recent studies from 2024-2026.',
            page: 'Page 12'
        },
        {
            id: 2,
            author: 'Dr. John Reyes',
            role: 'Panelist',
            time: '5 hours ago',
            content: 'Excellent data presentation. Well organized.',
            page: 'Page 18'
        },
        {
            id: 3,
            author: 'Prof. Anna Garcia',
            role: 'Technical Editor',
            time: '1 day ago',
            content: 'Check citation format on this page - should follow APA 7th edition.',
            page: 'Page 5'
        }
    ],

    postReportsComment() {
        if (!this.reportsNewCommentText.trim()) {
            alert('Please enter a comment.');
            return;
        }
        
        let newId = this.reportsCommentsList.length ? Math.max(...this.reportsCommentsList.map(c => c.id)) + 1 : 1;
        
        this.reportsCommentsList.push({
            id: newId,
            author: 'Dr. Rosario Dela Paz',
            role: 'Research Facilitator',
            time: 'Just now',
            content: this.reportsNewCommentText,
            page: 'Page 3'
        });

        this.reportsCommentsCount++;
        this.reportsNewCommentText = '';
        alert('Comment posted successfully!');
    },

    resolveReportsComment(id) {
        this.reportsCommentsList = this.reportsCommentsList.filter(c => c.id !== id);
        if (this.reportsCommentsCount > 0) {
            this.reportsCommentsCount--;
        }
        alert('Feedback comment resolved.');
    },

    approveReportsDocument() {
        this.reportsApprovedCount++;
        if (this.reportsCriticalCount > 0) {
            this.reportsCriticalCount--;
        }
        alert('Chapter 3 methodology document approved successfully!');
    },

    requestReportsRevisions() {
        this.reportsRevisionsCount++;
        alert('Requested revisions for Chapter 3 methodology document.');
    },

    rejectReportsDocument() {
        alert('Rejected Chapter 3 methodology document.');
    },

    repositorySearchQuery: '',
    repositoryStatusFilter: 'All',
    selectedRepositoryFile: null,
    repositoryFilesList: [],

    get repositoryStats() {
        let total = this.repositoryFilesList.length;
        let approved = this.repositoryFilesList.filter(f => f.status === 'Approved').length;
        let pending = this.repositoryFilesList.filter(f => f.status === 'Pending Review').length;
        let evaluation = this.repositoryFilesList.filter(f => f.status === 'For Evaluation').length;
        return { total, approved, pending, evaluation };
    },

    get filteredRepositoryFiles() {
        let list = this.repositoryFilesList;

        // Dropdown status filter
        if (this.repositoryStatusFilter !== 'All') {
            list = list.filter(f => f.status === this.repositoryStatusFilter);
        }

        // Search text filter
        if (this.repositorySearchQuery.trim() !== '') {
            let q = this.repositorySearchQuery.toLowerCase();
            list = list.filter(f => 
                f.title.toLowerCase().includes(q) || 
                f.description.toLowerCase().includes(q) || 
                f.subtitle.toLowerCase().includes(q) || 
                f.author.toLowerCase().includes(q)
            );
        }

        return list;
    },

    init() {
        this.$watch('activeTab', (tab, previousTab) => {
            if (tab !== previousTab) this.queuePersistTab(tab);
        });
        this.$watch('activeOfficialForm', (form, previousForm) => {
            if (this.activeTab === 'forms' && form !== previousForm) this.queuePersistTab('forms');
        });
    }
}));
    };

    if (window.Alpine) {
        registerFacilitatorDashboard();
    } else {
        document.addEventListener('alpine:init', registerFacilitatorDashboard);
    }
})();
</script>

<div
    class="min-h-screen flex font-sans bg-[#f4f7f6]"
    x-data="facilitatorDashboard({
        initialTab: @js($initialTab),
        initialFormPhase: @js($initialFormPhase),
        initialOfficialForm: @js($initialOfficialForm),
        officialForms: @js($officialForms),
        dashboardUrl: @js(route('facilitator.dashboard')),
        showVenueManager: @js(($defenseRooms ?? collect())->isEmpty() || (isset($errors) && ($errors->has('code') || $errors->has('name') || $errors->has('location_notes')))),
        showScheduleModal: @js(isset($errors) && ($errors->has('defense_schedule') || $errors->has('research_class_group_id') || $errors->has('defense_type') || $errors->has('room_id') || $errors->has('starts_at') || $errors->has('ends_at') || $errors->has('chairperson_user_id') || $errors->has('panel_user_ids'))),
        defenseSchedulingGroups: @js($defenseSchedulingGroups ?? []),
        defensePanelCandidates: @js($defensePanelCandidates ?? []),
        defenseRooms: @js($defenseRooms ?? []),
        facilitatorClasses: @js($facilitatorClasses ?? []),
        initialScheduleForm: {
            groupId: @js((string) old('research_class_group_id', '')),
            type: @js(old('defense_type', 'title_presentation')),
            chairpersonId: @js((string) old('chairperson_user_id', '')),
            memberOneId: @js((string) old('panel_user_ids.0', '')),
            memberTwoId: @js((string) old('panel_user_ids.1', '')),
        },
        defenseList: @js($defenseListData),
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
                        <x-current-user-avatar :user="auth()->user()" rounded="rounded-xl" />
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ auth()->user()->name ?? 'Facilitator' }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">Research Facilitator</span>
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

                <!-- Capstone Classes -->
                <button 
                    type="button" 
                    @click="activeTab = 'classes'"
                    :class="activeTab === 'classes' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chalkboard-teacher text-lg transition-transform group-hover:scale-110"></i>
                        <span>Capstone Classes</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['classes'] ?? 0" label="active classes" />
                        <span x-show="activeTab === 'classes'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Join Requests -->
                <button 
                    type="button" 
                    @click="activeTab = 'join-requests'"
                    :class="activeTab === 'join-requests' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-user-plus text-lg transition-transform group-hover:scale-110"></i>
                        <span>Join Requests</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['join_requests'] ?? 0" label="pending join requests" />
                        <span x-show="activeTab === 'join-requests'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Research Monitoring -->
                <button 
                    type="button" 
                    @click="activeTab = 'monitoring'"
                    :class="activeTab === 'monitoring' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-line-up text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Monitoring</span>
                    </div>
                    <span x-show="activeTab === 'monitoring'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>

                <!-- Research Screening -->
                <button 
                    type="button" 
                    @click="activeTab = 'screening'"
                    :class="activeTab === 'screening' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-search text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Screening</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['screening'] ?? 0" label="title proposal awaiting screening" />
                        <span x-show="activeTab === 'screening'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Defense Management -->
                <button 
                    type="button" 
                    @click="activeTab = 'defenses'"
                    :class="activeTab === 'defenses' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-calendar text-lg transition-transform group-hover:scale-110"></i>
                        <span>Defense Management</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-sidebar-count-badge :count="$sidebarBadges['defenses'] ?? 0" label="upcoming defenses" />
                        <span x-show="activeTab === 'defenses'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                    </div>
                </button>

                <!-- Research Statistics -->
                <a
                    href="{{ route('facilitator.dashboard', ['tab' => 'statistics']) }}"
                    :class="activeTab === 'statistics' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-chart-bar text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Statistics</span>
                    </div>
                    <span x-show="activeTab === 'statistics'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>

                <!-- Research Reports -->
                <a
                    href="{{ route('facilitator.reports.index') }}"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-file-text text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Reports</span>
                    </div>
                    <i class="ph ph-arrow-square-out text-sm text-white/50"></i>
                </a>

                <!-- Research Repository -->
                <button 
                    type="button" 
                    @click="activeTab = 'repository'"
                    :class="activeTab === 'repository' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 translate-x-1' : 'text-white/85 hover:text-white hover:bg-white/15 hover:translate-x-1 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-folder-open text-lg transition-transform group-hover:scale-110"></i>
                        <span>Research Repository</span>
                    </div>
                    <span x-show="activeTab === 'repository'" class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </button>
            </div>

            <!-- Official Forms Section -->
            <div class="space-y-1.5 pt-4">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Research Forms</span>
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
                            <button type="button" @click="activeTab = 'forms'; activeFormPhase = activeFormPhase === '{{ $phase }}' ? null : '{{ $phase }}'" class="w-full flex items-center justify-between gap-2 py-2 pl-4 pr-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors duration-200 text-[11px] font-semibold text-left">
                                <span class="flex min-w-0 items-start gap-2"><i class="ph ph-caret-right mt-0.5 shrink-0 text-[10px] transition-transform duration-200" :class="activeFormPhase === '{{ $phase }}' && 'rotate-90'"></i><span class="leading-4">{{ $label }}</span></span>
                                <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-[#eebc3f]/20 px-1.5 text-[9px] font-bold text-[#eebc3f]">{{ $phaseForms->count() }}</span>
                            </button>
                            <div x-show="activeFormPhase === '{{ $phase }}'" x-cloak x-transition class="mt-0.5 space-y-0.5 pl-2">
                                @foreach ($phaseForms as $code => $form)
                                    <button type="button" @click="activeTab = 'forms'; activeOfficialForm = '{{ $code }}'" :class="activeOfficialForm === '{{ $code }}' ? 'bg-[#eebc3f] text-[#09472d] ring-1 ring-white font-bold' : 'text-white/75 hover:text-white hover:bg-white/10'" class="w-full flex items-start gap-2 rounded-xl px-3 py-2 text-left transition-colors duration-200">
                                        <i class="ph ph-file-plus mt-0.5 shrink-0 text-sm"></i>
                                        <span class="min-w-0"><span class="block text-[10px] font-bold">{{ $code }}</span><span class="block text-[10px] leading-3.5">{{ $form['title'] }}</span></span>
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
                <a
                    href="{{ route('facilitator.dashboard', ['tab' => 'notifications']) }}"
                    wire:navigate
                    :class="activeTab === 'notifications' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
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
                <a
                    href="{{ route('facilitator.dashboard', ['tab' => 'settings']) }}"
                    wire:navigate
                    :class="activeTab === 'settings' ? 'bg-[#eebc3f] text-[#09472d] font-bold shadow-md' : 'text-white/85 hover:text-white hover:bg-white/15 font-semibold'"
                    class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 text-[13px] text-left cursor-pointer"
                >
                    <i class="ph ph-gear text-lg"></i>
                    <span>Settings</span>
                </a>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
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

            <!-- Right profile area matching "F / Dr. Facilitator Portal" -->
            <div class="flex items-center gap-4">
                <x-workspace-switcher current="facilitator" />
                <x-notification-dropdown />
                
                <!-- Facilitator Portal Profile Badge -->
                <div class="flex items-center gap-3 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs shadow-2xs">
                        <x-current-user-avatar :user="auth()->user()" />
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-slate-800">{{ auth()->user()->name ?? 'Facilitator' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 mt-0.5">Facilitator Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-grow p-8 space-y-8">
            <x-portal-feature-banner class="mb-8" :sections="[
                'classes' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Capstone Classes', 'description' => 'Create and manage research classes, student rosters, and research groups.', 'icon' => 'ph-chalkboard-teacher'],
                'join-requests' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Class Join Requests', 'description' => 'Review and approve or reject student class enrollment requests.', 'icon' => 'ph-user-plus'],
                'monitoring' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Research Monitoring', 'description' => 'Track milestone progress and progress status across research groups.', 'icon' => 'ph-chart-line-up'],
                'screening' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Research Screening', 'description' => 'Screen submitted research proposals prior to defense scheduling.', 'icon' => 'ph-file-search'],
                'defenses' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Defense Management', 'description' => 'Schedule proposal and final defenses, venues, and panel assignments.', 'icon' => 'ph-calendar'],
                'statistics' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Research Statistics', 'description' => 'Analyze institutional research metrics, throughput, and completion rates.', 'icon' => 'ph-chart-bar'],
                'reports' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Research Reports', 'description' => 'Generate comprehensive research performance and status reports.', 'icon' => 'ph-file-text'],
                'repository' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Research Repository', 'description' => 'Access and review institutional research archives.', 'icon' => 'ph-folder'],
                'notifications' => ['eyebrow' => 'Research Facilitator Portal', 'title' => 'Notifications', 'description' => 'View system notifications, pending approvals, and schedule updates.', 'icon' => 'ph-bell'],
            ]" />

            <!-- TAB: Capstone Classes -->
            <div x-show="activeTab === 'classes'" x-cloak>
                @include('pages.facilitator.classes')
            </div>

            <!-- TAB: Join Requests -->
            <div x-show="activeTab === 'join-requests'" x-cloak>
                @include('pages.facilitator.join-requests')
            </div>

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
                                <span class="text-emerald-200 text-xs font-semibold tracking-wide">{{ auth()->user()->department ?? 'Academic Research' }}</span>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black font-heading text-white tracking-tight drop-shadow-xs">
                                Welcome back, {{ auth()->user()->displayFirstName() }}!
                            </h1>
                            <p class="text-xs md:text-sm text-emerald-100/85 max-w-2xl leading-relaxed">
                                Department Research Facilitation &amp; Academic Program Operations
                            </p>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button
                                type="button"
                                @click="activeTab = 'classes'; queuePersistTab('classes')"
                                class="px-4.5 py-2.5 bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 text-[#073823] text-xs font-black rounded-xl flex items-center gap-2 shadow-md shadow-amber-950/20 transition-all cursor-pointer"
                            >
                                <i class="ph ph-plus-circle text-base"></i>
                                <span>Manage Classes</span>
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'screening'; queuePersistTab('screening')"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-file-search text-base text-[#eebc3f]"></i>
                                <span>Screening Queue</span>
                                @if ($pendingTitleProposalScreeningCount + $defenseSchedulingReadyCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-amber-950">
                                        {{ $pendingTitleProposalScreeningCount + $defenseSchedulingReadyCount }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'join-requests'; queuePersistTab('join-requests')"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-user-plus text-base text-blue-300"></i>
                                <span>Join Requests</span>
                                @if ((int)($classRequestStats['pending'] ?? 0) > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-blue-400 text-blue-950">
                                        {{ (int)($classRequestStats['pending'] ?? 0) }}
                                    </span>
                                @endif
                            </button>

                            <button
                                type="button"
                                @click="activeTab = 'defenses'; queuePersistTab('defenses')"
                                class="px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/15 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-xs hover:shadow-md transition-all cursor-pointer"
                            >
                                <i class="ph ph-calendar text-base text-purple-300"></i>
                                <span>Defenses</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Academic Pending Actions Widget -->
                <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions ?? []" />

                <!-- Modern Vibrant 4-KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- KPI 1: Active Capstone Classes -->
                    <div
                        @click="activeTab = 'classes'; queuePersistTab('classes')"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-emerald-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-chalkboard-teacher"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0e5c3a] text-white shadow-md shadow-emerald-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-chalkboard-teacher"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-[#0e5c3a] border border-emerald-100 group-hover:bg-[#0e5c3a] group-hover:text-white transition-all">
                                    <span>Classes</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Capstone Classes</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $classes->count() }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ $classes->sum('active_students_count') }} Students Enrolled</span>
                                    <span class="font-bold text-[#0e5c3a]">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Active Research Groups -->
                    <div
                        @click="activeTab = 'monitoring'; queuePersistTab('monitoring')"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-cyan-400 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-blue-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-users-three"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-700 text-white shadow-md shadow-blue-700/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                    <i class="ph ph-users-three"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                    <span>Cohorts</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Research Groups</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ $classes->sum('active_groups_count') }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>Across {{ $classes->count() }} class section{{ $classes->count() === 1 ? '' : 's' }}</span>
                                    <span class="font-bold text-blue-600">In Progress</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Action & Screening Queue -->
                    <div
                        @click="activeTab = 'screening'; queuePersistTab('screening')"
                        class="bg-white rounded-3xl p-6 border border-slate-200/70 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer"
                    >
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 opacity-80 group-hover:opacity-100 group-hover:h-1.5 transition-all"></div>
                        <div class="absolute -right-3 -bottom-3 text-slate-100/70 group-hover:text-amber-50 text-7xl font-bold transition-colors pointer-events-none -z-0 select-none">
                            <i class="ph ph-clipboard-text"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex items-center justify-between">
                                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 text-white shadow-md shadow-amber-600/20 flex items-center justify-center text-2xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                                    <i class="ph ph-clipboard-text"></i>
                                    @if ((int)($classRequestStats['pending'] ?? 0) + $pendingTitleProposalScreeningCount + $defenseSchedulingReadyCount > 0)
                                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-rose-500 rounded-full border-2 border-white animate-pulse"></span>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                    <span>Review</span>
                                    <i class="ph ph-arrow-up-right"></i>
                                </span>
                            </div>
                            <div class="mt-5">
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Pending Queue</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">
                                    {{ (int)($classRequestStats['pending'] ?? 0) + $pendingTitleProposalScreeningCount + $defenseSchedulingReadyCount }}
                                </span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ (int)($classRequestStats['pending'] ?? 0) }} Join Req · {{ $pendingTitleProposalScreeningCount + $defenseSchedulingReadyCount }} Docs</span>
                                    <span class="font-bold text-amber-600">Action Needed</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Defense Presentations -->
                    <div
                        @click="activeTab = 'defenses'; queuePersistTab('defenses')"
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
                                <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block">Defense Schedules</span>
                                <span class="text-3xl font-black text-slate-900 tracking-tight mt-1 block">{{ count($defenses) }}</span>
                                <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 pt-3 border-t border-slate-100">
                                    <span>{{ collect($defenses)->where('defense_status', 'scheduled')->count() }} Scheduled</span>
                                    <span class="font-bold text-purple-600">Calendar</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Two-Column Interactive Command Center (Grid 12: 8 cols left, 4 cols right) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Left Main Column (8 cols) -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Active Capstone Classes Workspace Hub -->
                        <div class="bg-white rounded-2xl p-6 md:p-7 border border-slate-200/60 shadow-xs space-y-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-[#0e5c3a]"></span>
                                        <h3 class="font-heading font-black text-slate-900 text-base tracking-tight">
                                            My Capstone Classes
                                        </h3>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">Active research classes under your direct academic facilitation</p>
                                </div>
                                <button
                                    type="button"
                                    @click="activeTab = 'classes'; queuePersistTab('classes')"
                                    class="text-xs font-bold text-[#0e5c3a] hover:text-[#09472d] flex items-center gap-1 cursor-pointer transition-colors"
                                >
                                    <span>View All Classes</span>
                                    <i class="ph ph-caret-right text-sm"></i>
                                </button>
                            </div>

                            <!-- Classes Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse ($classes as $researchClass)
                                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-[#0e5c3a]/40 p-5 shadow-2xs hover:shadow-md transition-all space-y-4">
                                        <!-- Header row -->
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $researchClass->is_active ? 'bg-emerald-50 text-[#0e5c3a] border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                                        {{ $researchClass->is_active ? 'Active' : 'Closed' }}
                                                    </span>
                                                </div>
                                                <h4 class="font-bold text-slate-900 text-sm mt-1.5 truncate">{{ $researchClass->name }}</h4>
                                                @if ($researchClass->description)
                                                    <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $researchClass->description }}</p>
                                                @endif
                                            </div>

                                            <!-- Join Code 1-Click Copy Pill -->
                                            <button
                                                type="button"
                                                @click="navigator.clipboard.writeText('{{ $researchClass->revealJoinCode() }}'); copiedCode = {{ $researchClass->id }}; setTimeout(() => copiedCode = null, 2000)"
                                                class="shrink-0 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100/80 border border-amber-200/80 text-amber-800 text-[11px] font-black tracking-wider flex items-center gap-1.5 cursor-pointer transition-all shadow-2xs"
                                                title="Click to copy join code"
                                            >
                                                <i class="ph" :class="copiedCode === {{ $researchClass->id }} ? 'ph-check-bold text-emerald-600' : 'ph-copy'"></i>
                                                <span x-text="copiedCode === {{ $researchClass->id }} ? 'COPIED!' : '{{ $researchClass->revealJoinCode() }}'"></span>
                                            </button>
                                        </div>

                                        <!-- Enrollment Capacity Bar -->
                                        <div class="space-y-1.5 pt-1">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-600">
                                                <span>Student Enrollment</span>
                                                <span>{{ $researchClass->active_students_count }} / {{ $researchClass->max_students }}</span>
                                            </div>
                                            <div class="h-2 bg-slate-200/70 rounded-full w-full overflow-hidden">
                                                <div
                                                    class="h-full rounded-full bg-gradient-to-r from-[#0e5c3a] to-emerald-400 transition-all duration-500"
                                                    style="width: {{ min(100, $researchClass->max_students > 0 ? ($researchClass->active_students_count / $researchClass->max_students) * 100 : 0) }}%"
                                                ></div>
                                            </div>
                                        </div>

                                        <!-- Footer / Action Link -->
                                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-1.5 text-slate-600 font-semibold text-[11px]">
                                                <i class="ph ph-users text-[#0e5c3a]"></i>
                                                <span>{{ $researchClass->active_groups_count }} Research Groups</span>
                                            </div>

                                            <a
                                                href="{{ route('facilitator.classes.show', $researchClass) }}"
                                                class="px-3 py-1.5 bg-white hover:bg-[#0e5c3a] text-[#0e5c3a] hover:text-white border border-[#0e5c3a]/30 font-bold text-[11px] rounded-xl shadow-2xs transition-all flex items-center gap-1"
                                            >
                                                <span>Open Workspace</span>
                                                <i class="ph ph-caret-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-10 text-center space-y-3">
                                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#0e5c3a] border border-emerald-100 flex items-center justify-center text-3xl mx-auto shadow-2xs">
                                            <i class="ph ph-chalkboard-teacher"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h4 class="font-bold text-slate-800 text-sm">No Capstone Classes Yet</h4>
                                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Create your first capstone research class to generate join codes and manage student cohorts.</p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="activeTab = 'classes'; queuePersistTab('classes')"
                                            class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer inline-flex items-center gap-2"
                                        >
                                            <i class="ph ph-plus-circle"></i>
                                            <span>Create First Class</span>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Research Lifecycle Pipeline Flow -->
                        <div class="bg-white rounded-2xl p-6 md:p-7 border border-slate-200/60 shadow-xs space-y-6">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                        <h3 class="font-heading font-black text-slate-900 text-base tracking-tight">
                                            Research Lifecycle Progression
                                        </h3>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">Overall milestone throughput across all managed research cohorts</p>
                                </div>
                                <button
                                    type="button"
                                    @click="activeTab = 'monitoring'; queuePersistTab('monitoring')"
                                    class="text-xs font-bold text-indigo-700 hover:text-indigo-900 flex items-center gap-1 cursor-pointer transition-colors"
                                >
                                    <span>Full Monitoring Board</span>
                                    <i class="ph ph-caret-right text-sm"></i>
                                </button>
                            </div>

                            <!-- 4-Stage Horizontal Pipeline -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Stage 1: Proposal Screening -->
                                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-base font-bold shadow-2xs">
                                            1
                                        </span>
                                        <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Phase I</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Title &amp; Proposal</span>
                                        <span class="text-lg font-black text-amber-700 mt-1 block">
                                            {{ $pendingTitleProposalScreeningCount }} In Screening
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-amber-200/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: {{ $pendingTitleProposalScreeningCount > 0 ? '60%' : '10%' }}"></div>
                                    </div>
                                </div>

                                <!-- Stage 2: Manuscript & Advising -->
                                <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/70 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-base font-bold shadow-2xs">
                                            2
                                        </span>
                                        <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Phase II</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Review &amp; Advising</span>
                                        <span class="text-lg font-black text-blue-700 mt-1 block">
                                            {{ $classes->sum('active_groups_count') }} Groups Active
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-blue-200/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" style="width: 75%"></div>
                                    </div>
                                </div>

                                <!-- Stage 3: Defense & Scoring -->
                                <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-200/70 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="w-8 h-8 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-base font-bold shadow-2xs">
                                            3
                                        </span>
                                        <span class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Phase III</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Defense &amp; Evaluation</span>
                                        <span class="text-lg font-black text-purple-700 mt-1 block">
                                            {{ count($defenses) }} Defenses
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-purple-200/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500 rounded-full" style="width: 50%"></div>
                                    </div>
                                </div>

                                <!-- Stage 4: Approval & Archiving -->
                                <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/70 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-[#0e5c3a] flex items-center justify-center text-base font-bold shadow-2xs">
                                            4
                                        </span>
                                        <span class="text-[10px] font-bold text-[#0e5c3a] uppercase tracking-wider">Phase IV</span>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 block">Final Archiving</span>
                                        <span class="text-lg font-black text-[#0e5c3a] mt-1 block">
                                            {{ collect($defenses)->where('defense_status', 'completed')->count() }} Completed
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-emerald-200/60 rounded-full overflow-hidden">
                                        <div class="h-full bg-[#0e5c3a] rounded-full" style="width: 80%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar Column (4 cols) -->
                    <div class="lg:col-span-4 space-y-8">
                        <!-- Urgent Review & Action Feed -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-bell-ringing text-amber-500 text-lg"></i>
                                    <h3 class="font-heading font-black text-slate-900 text-sm tracking-tight">Review &amp; Action Feed</h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Live</span>
                            </div>

                            <div class="space-y-3">
                                <!-- Join Requests item preview -->
                                @if ((int)($classRequestStats['pending'] ?? 0) > 0)
                                    <div
                                        @click="activeTab = 'join-requests'; queuePersistTab('join-requests')"
                                        class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-200/80 hover:bg-blue-100/60 transition-all cursor-pointer flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center text-base shrink-0 shadow-2xs">
                                                <i class="ph ph-user-plus"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 text-xs block truncate">Student Join Requests</span>
                                                <span class="text-[11px] text-blue-700 font-semibold">{{ (int)$classRequestStats['pending'] }} request{{ (int)$classRequestStats['pending'] === 1 ? '' : 's' }} awaiting approval</span>
                                            </div>
                                        </div>
                                        <i class="ph ph-caret-right text-blue-500 text-base"></i>
                                    </div>
                                @endif

                                <!-- Screening documents queue preview -->
                                @if ($pendingTitleProposalScreeningCount > 0)
                                    <div
                                        @click="activeTab = 'screening'; queuePersistTab('screening')"
                                        class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 hover:bg-amber-100/60 transition-all cursor-pointer flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center text-base shrink-0 shadow-2xs">
                                                <i class="ph ph-file-search"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 text-xs block truncate">Title Proposals</span>
                                                <span class="text-[11px] text-amber-700 font-semibold">{{ $pendingTitleProposalScreeningCount }} proposal{{ $pendingTitleProposalScreeningCount === 1 ? '' : 's' }} to screen</span>
                                            </div>
                                        </div>
                                        <i class="ph ph-caret-right text-amber-500 text-base"></i>
                                    </div>
                                @endif

                                <!-- Defense documents ready for scheduling -->
                                @if ($defenseSchedulingReadyCount > 0)
                                    <div
                                        @click="activeTab = 'screening'; queuePersistTab('screening')"
                                        class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200/80 hover:bg-emerald-100/60 transition-all cursor-pointer flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-9 h-9 rounded-xl bg-[#0e5c3a] text-white flex items-center justify-center text-base shrink-0 shadow-2xs">
                                                <i class="ph ph-calendar-plus"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <span class="font-bold text-slate-900 text-xs block truncate">Ready for Defense Scheduling</span>
                                                <span class="text-[11px] text-emerald-700 font-semibold">{{ $defenseSchedulingReadyCount }} group{{ $defenseSchedulingReadyCount === 1 ? '' : 's' }} endorsed</span>
                                            </div>
                                        </div>
                                        <i class="ph ph-caret-right text-emerald-600 text-base"></i>
                                    </div>
                                @endif

                                <!-- All Caught Up State -->
                                @if ((int)($classRequestStats['pending'] ?? 0) === 0 && $pendingTitleProposalScreeningCount === 0 && $defenseSchedulingReadyCount === 0)
                                    <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                                        <i class="ph ph-check-circle text-3xl text-emerald-600"></i>
                                        <p class="text-xs font-bold text-slate-800">All Caught Up!</p>
                                        <p class="text-[11px] text-slate-500">No pending student join requests or documents awaiting screening.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Upcoming Defenses Radar -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-calendar text-[#0e5c3a] text-lg"></i>
                                    <h3 class="font-heading font-black text-slate-900 text-sm tracking-tight">Upcoming Defenses</h3>
                                </div>
                                <button
                                    type="button"
                                    @click="activeTab = 'defenses'; queuePersistTab('defenses')"
                                    class="text-xs font-bold text-[#0e5c3a] hover:underline cursor-pointer"
                                >
                                    Calendar →
                                </button>
                            </div>

                            <div class="space-y-3">
                                @forelse (collect($defenses)->take(3) as $def)
                                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider {{ str_contains(strtolower($def['defense_type_label'] ?? ''), 'final') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-[#0e5c3a] border border-emerald-200' }}">
                                                {{ $def['defense_type_label'] ?? 'Defense' }}
                                            </span>
                                            <span class="text-[11px] font-bold text-slate-500">{{ $def['formatted_date'] ?? 'TBA' }}</span>
                                        </div>
                                        <h5 class="font-bold text-slate-900 text-xs line-clamp-1">{{ $def['research_title'] ?? ($def['group_name'] ?? 'Research Presentation') }}</h5>
                                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                                            <span class="truncate">{{ $def['group_name'] ?? 'Advisee Group' }}</span>
                                            <span class="font-bold text-slate-700 shrink-0">{{ $def['room_name'] ?? ($def['room_code'] ?? 'Room TBA') }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center rounded-xl bg-slate-50 border border-slate-100 space-y-2">
                                        <i class="ph ph-calendar-blank text-3xl text-slate-300"></i>
                                        <p class="text-xs font-bold text-slate-700">No Defenses Scheduled</p>
                                        <p class="text-[11px] text-slate-500">Upcoming defense sessions will appear here once scheduled.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Faculty Advisers Pool Summary -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-200/60 shadow-xs space-y-5">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-users text-[#0e5c3a] text-lg"></i>
                                    <h3 class="font-heading font-black text-slate-900 text-sm tracking-tight">Faculty Adviser Pool</h3>
                                </div>
                                <span class="text-[11px] font-bold text-slate-500">{{ count($classAdviserOptions ?? []) }} Active</span>
                            </div>

                            <div class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                                @forelse (collect($classAdviserOptions ?? [])->take(5) as $adv)
                                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="w-7 h-7 rounded-lg bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-[10px] shrink-0">
                                                {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($adv->name, 0, 1)) }}
                                            </div>
                                            <span class="font-bold text-slate-800 truncate">{{ $adv->name }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-semibold shrink-0">{{ $adv->department ?? 'Faculty' }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 text-center py-4">No active advisers found in faculty pool.</p>
                                @endforelse
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
                    :dashboard-route="route('facilitator.dashboard')"
                />
            </div>

            <!-- TAB: Research Monitoring -->
            <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-8">
                @if (isset($progressGroups))
                    <x-research-progress.facilitator-monitoring
                        :groups="$progressGroups"
                        :search="$progressSearch"
                        :group-status="$progressGroupStatus"
                        :group-id="$progressGroupId ?? null"
                        :all-filter-groups="$allFilterGroups ?? null"
                    />
                @else
                    <div class="rounded-3xl border border-gray-100 bg-white p-12 text-center text-gray-500">
                        Open Research Monitoring from the sidebar to load current group progress.
                    </div>
                @if (false)
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Research Monitoring</span>
                    </div>
                    
                    <div class="flex justify-between items-center flex-wrap gap-4">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Research Lifecycle Tracker</h1>
                            <p class="text-xs text-gray-450 mt-1">Track your research progress through each milestone</p>
                        </div>
                        
                        <!-- Project Selector Dropdown -->
                        <div class="flex items-center gap-3">
                            <label for="monitoring-project-select" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Select Project:</label>
                            <select 
                                id="monitoring-project-select"
                                x-model.number="selectedMonitoringId" 
                                class="bg-white border border-gray-250 text-gray-700 text-xs px-3.5 py-2 rounded-xl outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all cursor-pointer"
                            >
                                <template x-for="p in monitoringProjects" :key="p.id">
                                    <option :value="p.id" x-text="`[${p.code}] ${p.title}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Overall Progress Card -->
                <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-xl shadow-slate-200/30 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex-1 space-y-4 w-full">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Overall Progress</h3>
                            <span class="text-sm font-bold text-gray-700 mt-1 block" x-text="activeMonitoringProject.title">Machine Learning Applications in Agricultural Pest Detection</span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full">
                            <div class="h-3 bg-gray-100 rounded-full w-full overflow-hidden">
                                <div class="h-full bg-emerald-600 rounded-full transition-all duration-500" :style="`width: ${activeMonitoringStats.percent}%`"></div>
                            </div>
                        </div>

                        <!-- 3 Stats Counter Row -->
                        <div class="grid grid-cols-3 gap-4 pt-2">
                            <div class="text-center md:text-left">
                                <span class="text-2xl font-bold text-emerald-600 block" x-text="`${activeMonitoringStats.completed}`">6</span>
                                <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider mt-0.5">Completed</span>
                            </div>
                            <div class="text-center md:text-left border-x border-gray-100 px-4">
                                <span class="text-2xl font-bold text-amber-500 block" x-text="`${activeMonitoringStats.inProgress}`">2</span>
                                <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider mt-0.5">In Progress</span>
                            </div>
                            <div class="text-center md:text-left">
                                <span class="text-2xl font-bold text-gray-400 block" x-text="`${activeMonitoringStats.pending}`">4</span>
                                <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider mt-0.5">Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- Large Percent Indicator -->
                    <div class="flex-shrink-0 flex flex-col items-center justify-center p-4">
                        <div class="text-4xl font-extrabold text-emerald-600 tracking-tight font-heading flex items-baseline">
                            <span x-text="activeMonitoringStats.percent">42</span>
                            <span class="text-xl font-bold ml-0.5">%</span>
                        </div>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1 block">Complete</span>
                    </div>
                </div>

                <!-- Research Milestones Timeline Card -->
                <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-xl shadow-slate-200/30 space-y-6">
                    <h3 class="font-bold text-gray-850 text-base">Research Milestones</h3>
                    
                    <div class="relative pl-8 md:pl-12 space-y-6">
                        <!-- Vertical line connector -->
                        <div class="absolute left-[11px] md:left-[15px] top-4 bottom-4 w-0.5 bg-gray-100"></div>

                        <template x-for="(milestone, index) in activeMonitoringProject.milestones" :key="index">
                            <div class="relative flex flex-col md:flex-row gap-4 items-start">
                                <!-- Timeline icon badge -->
                                <div class="absolute -left-[27px] md:-left-[31px] top-1 w-5 h-5 md:w-6 md:h-6 rounded-full flex items-center justify-center border z-10"
                                     :class="{
                                         'bg-emerald-600 border-emerald-600 text-white shadow-sm': milestone.status === 'Completed',
                                         'bg-amber-500 border-amber-500 text-white shadow-sm': milestone.status === 'In Progress',
                                         'bg-white border-gray-250 text-gray-300': milestone.status === 'Pending'
                                     }"
                                >
                                    <template x-if="milestone.status === 'Completed'">
                                        <i class="ph ph-check text-[10px] md:text-xs font-extrabold"></i>
                                    </template>
                                    <template x-if="milestone.status === 'In Progress'">
                                        <i class="ph ph-clock text-[10px] md:text-xs font-extrabold"></i>
                                    </template>
                                    <template x-if="milestone.status === 'Pending'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-200"></span>
                                    </template>
                                </div>

                                <!-- Card item container -->
                                <div class="flex-1 w-full border rounded-2xl p-5 transition-all duration-300"
                                     :class="{
                                         'bg-[#f2fcf7]/50 border-emerald-100 hover:border-emerald-200 hover:bg-[#f2fcf7]/80': milestone.status === 'Completed',
                                         'bg-[#fffbf0] border-amber-100 hover:border-amber-200 hover:bg-[#fffbf0]/80 ring-4 ring-amber-500/5': milestone.status === 'In Progress',
                                         'bg-white border-gray-150 hover:border-gray-250': milestone.status === 'Pending'
                                     }"
                                >
                                    <div class="flex justify-between items-start gap-4 flex-wrap">
                                        <div class="space-y-1.5">
                                            <h4 class="font-bold text-gray-800 text-sm md:text-sm" x-text="milestone.title">Milestone Title</h4>
                                            
                                            <!-- Date -->
                                            <div class="flex items-center gap-2 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                                <i class="ph ph-calendar text-xs"></i>
                                                <span x-text="milestone.date">Feb 15, 2026</span>
                                            </div>
                                        </div>

                                        <!-- Right side status badge -->
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-extrabold tracking-wide uppercase border shadow-2xs transition-all duration-200"
                                              :class="{
                                                  'bg-emerald-600 border-emerald-700 text-white': milestone.status === 'Completed',
                                                  'bg-amber-500 border-amber-600 text-white': milestone.status === 'In Progress',
                                                  'bg-gray-100 border-gray-200 text-gray-500': milestone.status === 'Pending'
                                              }"
                                              x-text="milestone.status"
                                        >
                                            Completed
                                        </span>
                                    </div>

                                    <!-- Bottom subtext if present -->
                                    <template x-if="milestone.details">
                                        <div class="mt-3 pt-3 border-t border-gray-100/50 flex items-center gap-2">
                                            <span class="text-[10px] font-bold flex items-center gap-1.5"
                                                  :class="{
                                                      'text-emerald-700': milestone.status === 'Completed',
                                                      'text-amber-700': milestone.status === 'In Progress',
                                                      'text-gray-400': milestone.status === 'Pending'
                                                  }"
                                            >
                                                <span x-show="milestone.status === 'Completed'">✓</span>
                                                <span x-show="milestone.status === 'In Progress'">✦</span>
                                                <span x-text="milestone.details">All requirements met and approved</span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Buttons -->
                    <div class="pt-6 border-t border-gray-100 flex items-center gap-4 flex-wrap">
                        <button 
                            @click="showMonitoringEditModal = true"
                            class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md shadow-[#0e5c3a]/10 hover:shadow-lg transition-all duration-200 cursor-pointer"
                        >
                            <i class="ph ph-note-pencil text-base font-bold"></i>
                            <span>Update Progress</span>
                        </button>
                        
                        <button 
                            @click="alert('Generating timeline report PDF...')"
                            class="px-5 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-2xs hover:shadow-xs transition-all duration-200 cursor-pointer"
                        >
                            <i class="ph ph-download-simple text-base"></i>
                            <span>Download Timeline</span>
                        </button>
                    </div>
                </div>

                @endif
                @endif
            </div>

            <!-- TAB: Research Screening (Proposal Management) -->
            <div x-show="activeTab === 'screening'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">
                            <span>Facilitator Portal</span>
                            <span>/</span>
                            <span class="text-[#0e5c3a]">Research Screening</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
                            Research Proposal Screening
                        </h1>
                        <p class="mt-1 text-xs text-slate-500 font-medium">
                            Screen incoming Title Proposals and review adviser-approved defense manuscripts.
                        </p>
                    </div>
                    
                    <button
                        type="button"
                        @click="activeTab = 'repository'; queuePersistTab('repository')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 px-4.5 py-2.5 text-xs font-bold text-[#0e5c3a] shadow-2xs hover:shadow-xs transition-all cursor-pointer shrink-0"
                    >
                        <i class="ph ph-folder-open text-base text-[#eebc3f]"></i>
                        <span>Open Research Repository</span>
                    </button>
                </div>

                <!-- Title Proposals Awaiting Screening Section -->
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-400 via-orange-500 to-[#0e5c3a]"></div>
                    
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-800 flex items-center justify-center text-xl font-bold shadow-2xs">
                                <i class="ph ph-file-search"></i>
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black font-heading text-slate-900">Title Proposals Awaiting Screening</h2>
                                <p class="text-xs text-slate-500 font-medium">Approval here validates the document for Title Presentation (RES-026).</p>
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $pendingTitleProposalScreeningCount > 0 ? 'bg-amber-100 text-amber-900 border border-amber-200 animate-pulse' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $pendingTitleProposalScreeningCount }} {{ Str::plural('Queue', $pendingTitleProposalScreeningCount) }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        @forelse ($titleProposalScreeningQueue ?? [] as $titleDocument)
                            <article class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 sm:p-6 space-y-4 transition-all hover:bg-slate-50">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-md bg-amber-100 text-amber-800 font-mono text-[10px] font-black uppercase tracking-wider border border-amber-200">
                                                Version {{ $titleDocument->version_number }}
                                            </span>
                                            <span class="text-xs font-bold text-[#0e5c3a]">
                                                {{ $titleDocument->researchClassGroup?->name ?? 'Unassigned Group' }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm sm:text-base font-black text-slate-900 leading-snug">
                                            {{ $titleDocument->original_filename }}
                                        </h3>
                                        <p class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                            <i class="ph ph-user"></i>
                                            <span>Submitted by <strong>{{ $titleDocument->user?->name ?? 'Student' }}</strong> · {{ $titleDocument->submitted_at?->diffForHumans() ?? 'Recently' }}</span>
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <a
                                            href="{{ route('documents.view', $titleDocument) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition-colors"
                                        >
                                            <i class="ph ph-eye text-sm text-[#0e5c3a]"></i>
                                            <span>View</span>
                                        </a>
                                        <a
                                            href="{{ route('documents.download', $titleDocument) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition-colors"
                                        >
                                            <i class="ph ph-download-simple text-sm text-blue-600"></i>
                                            <span>Download</span>
                                        </a>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('facilitator.title-proposals.screen', $titleDocument) }}" class="pt-3 border-t border-slate-200/60 grid gap-3 md:grid-cols-[1fr_auto_auto] items-start">
                                    @csrf
                                    <textarea
                                        name="remarks"
                                        maxlength="2000"
                                        placeholder="Enter revision notes or screening endorsement comments..."
                                        class="w-full min-h-12 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                                    ></textarea>
                                    <button
                                        type="submit"
                                        name="decision"
                                        value="revision_required"
                                        class="rounded-xl border border-rose-200 bg-rose-50/80 hover:bg-rose-100 px-4 py-2.5 text-xs font-bold text-rose-700 transition-colors cursor-pointer"
                                    >
                                        Require Revision
                                    </button>
                                    <button
                                        type="submit"
                                        name="decision"
                                        value="approved_for_presentation"
                                        class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-5 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-950/20 transition-all cursor-pointer"
                                    >
                                        Approve for Title Presentation
                                    </button>
                                </form>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-10 text-center space-y-2">
                                <i class="ph ph-check-circle text-3xl text-slate-300"></i>
                                <p class="text-xs font-bold text-slate-700">Screening Queue Clear</p>
                                <p class="text-[11px] text-slate-400">No Title Proposal documents are currently awaiting your screening.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- Adviser-Approved Documents Ready for Scheduling -->
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-[#0e5c3a]"></div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl font-bold shadow-2xs">
                                <i class="ph ph-calendar-check"></i>
                            </span>
                            <div>
                                <h2 class="text-base sm:text-lg font-black font-heading text-slate-900">Adviser-Approved Documents Ready for Defense</h2>
                                <p class="text-xs text-slate-500 font-medium">Passed faculty adviser review and awaiting defense date and panel scheduling.</p>
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $defenseSchedulingReadyCount > 0 ? 'bg-blue-100 text-blue-900 border border-blue-200 animate-pulse' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            {{ $defenseSchedulingReadyCount }} Ready
                        </span>
                    </div>

                    <div class="space-y-4">
                        @forelse ($adviserApprovedDefenseDocuments ?? [] as $approvedDocument)
                            <article class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 sm:p-6 space-y-3 transition-all hover:bg-slate-50">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-black text-slate-900">{{ $approvedDocument->researchClassGroup?->name }}</span>
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[9px] font-black uppercase text-[#0e5c3a] border border-emerald-200">Adviser Endorsed</span>
                                            <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[9px] font-black uppercase text-blue-700 border border-blue-200">{{ $approvedDocument->stageLabel() }}</span>
                                        </div>
                                        <h3 class="text-sm sm:text-base font-bold text-slate-800">
                                            {{ $approvedDocument->original_filename }}
                                        </h3>
                                        <p class="text-xs text-slate-500 font-medium">
                                            Version {{ $approvedDocument->version_number }} · Adviser: <strong class="text-slate-700">{{ $approvedDocument->researchClassGroup?->adviser?->name ?? 'Not assigned' }}</strong>
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                                        <a href="{{ route('documents.view', $approvedDocument) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs">View</a>
                                        <a href="{{ route('documents.download', $approvedDocument) }}" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs">Download</a>
                                        <button type="button" @click="activeTab = 'defenses'; queuePersistTab('defenses'); $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))" class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-4 py-2 text-xs font-black text-white shadow-md shadow-emerald-950/20 cursor-pointer">
                                            Schedule Defense
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-8 text-center text-xs text-slate-400">
                                No adviser-approved defense document is waiting for scheduling.
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- 4 KPI Metrics Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Approved</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none">{{ $facilitatorScreeningStats['approved'] ?? 0 }}</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Pending</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none">{{ $facilitatorScreeningStats['pending'] ?? 0 }}</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0 {{ ($facilitatorScreeningStats['pending'] ?? 0) > 0 ? 'animate-pulse' : '' }}">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Revisions</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none">{{ $facilitatorScreeningStats['revisions'] ?? 0 }}</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-700 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Total Screened</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none">{{ $facilitatorScreeningStats['total'] ?? 0 }}</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Screening and Review History -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                        <div>
                            <h3 class="font-black font-heading text-slate-900 text-base sm:text-lg">Screening &amp; Review History</h3>
                            <p class="mt-0.5 text-xs text-slate-500 font-medium">Decided submissions are preserved below. Superseded versions remain available in the repository.</p>
                        </div>
                        <button type="button" @click="activeTab = 'repository'; queuePersistTab('repository')" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 text-xs font-bold text-[#0e5c3a] shadow-2xs">View Complete Repository</button>
                    </div>

                    <div class="space-y-4">
                        @forelse ($facilitatorScreeningHistory ?? [] as $historyDocument)
                            @php
                                $historyReview = $historyDocument->reviews->first();
                                $historyStatus = $historyDocument->status?->value ?? 'unknown';
                                $historyStatusClass = match ($historyStatus) {
                                    'approved_for_presentation', 'accepted' => 'bg-emerald-50 text-[#0e5c3a] border-emerald-200',
                                    'revision_requested' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <div class="border border-slate-200/80 rounded-2xl p-5 sm:p-6 space-y-4 bg-slate-50/40 hover:bg-slate-50 transition-colors">
                                <div class="flex justify-between items-start gap-4 flex-wrap">
                                    <div class="space-y-1">
                                        <h2 class="text-base sm:text-lg font-black text-slate-900 leading-snug">{{ $historyDocument->original_filename }}</h2>
                                        <div class="flex flex-wrap items-center gap-3 text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            <span>ID: <code class="text-slate-700 font-mono">DOC-{{ str_pad((string) $historyDocument->id, 6, '0', STR_PAD_LEFT) }}</code></span>
                                            <span>•</span>
                                            <span>{{ $historyDocument->researchClassGroup?->name ?? 'Unassigned group' }} · {{ $historyDocument->stageLabel() }} · Version {{ $historyDocument->version_number }}</span>
                                            <span>•</span>
                                            <span>Submitted: <strong class="text-slate-600">{{ $historyDocument->submitted_at?->format('M j, Y') ?? 'N/A' }}</strong></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black tracking-wider uppercase border {{ $historyStatusClass }}">{{ str($historyStatus)->headline() }}</span>
                                        @unless ($historyDocument->is_current)
                                            <span class="px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-black uppercase text-slate-500">Superseded</span>
                                        @endunless
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-3 border-t border-slate-200/60">
                                    <div>
                                        <span class="text-slate-400 font-extrabold uppercase tracking-wider block text-[9px]">Decision by</span>
                                        <span class="text-slate-900 font-bold block mt-0.5">{{ $historyReview?->reviewer?->name ?? 'Awaiting facilitator decision' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-extrabold uppercase tracking-wider block text-[9px]">Decision Date</span>
                                        <span class="text-slate-900 font-bold block mt-0.5">{{ $historyReview?->reviewed_at?->format('M j, Y g:i A') ?? 'Pending' }}</span>
                                    </div>
                                </div>

                                @if ($historyStatus === 'revision_requested')
                                    <div class="pt-3 border-t border-rose-100 flex items-center gap-2">
                                        <span class="text-xs font-bold text-rose-700">
                                            ⚠ This proposal is currently in revision. Awaiting student resubmission.
                                        </span>
                                    </div>
                                @endif

                                <div class="pt-3 border-t border-slate-200/60 flex gap-3 flex-wrap">
                                    <a
                                        href="{{ route('documents.view', $historyDocument) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-bold rounded-xl shadow-sm transition-all"
                                    >
                                        View Document
                                    </a>
                                    <a
                                        href="{{ route('documents.download', $historyDocument) }}"
                                        class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-2xs transition-all"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 py-10 text-center text-xs text-slate-400">No submitted document has entered facilitator screening history yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TAB: Defense Management (Defense Scheduling) -->
            <div x-show="activeTab === 'defenses'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">
                            <span>Facilitator Portal</span>
                            <span>/</span>
                            <span class="text-[#0e5c3a]">Defense Management</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
                            Defense Scheduling &amp; Panels
                        </h1>
                        <p class="mt-1 text-xs text-slate-500 font-medium">
                            Manage presentation rooms, assign panel evaluators, and schedule research defense sessions.
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            @click="showVenueManager = !showVenueManager"
                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors cursor-pointer"
                        >
                            <i class="ph ph-buildings text-base text-[#0e5c3a]"></i>
                            <span>Manage Venues</span>
                        </button>
                        <button
                            type="button"
                            @click="openClassCommitteeModal()"
                            class="inline-flex items-center gap-2 rounded-2xl border border-emerald-300 bg-emerald-50/80 hover:bg-emerald-100/80 px-4 py-2.5 text-xs font-bold text-[#0e5c3a] shadow-2xs transition-colors cursor-pointer"
                        >
                            <i class="ph ph-users-three text-base text-[#0e5c3a]"></i>
                            <span>Assign Class Panels</span>
                        </button>
                        <button
                            type="button"
                            @click="openBulkScheduleModal()"
                            @disabled(($defenseRooms ?? collect())->isEmpty())
                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 disabled:cursor-not-allowed disabled:bg-slate-300 text-white px-5 py-2.5 text-xs font-black shadow-md shadow-emerald-950/20 transition-all cursor-pointer"
                            title="{{ ($defenseRooms ?? collect())->isEmpty() ? 'Create an active venue before scheduling a defense.' : 'Schedule multiple research groups in a session window' }}"
                        >
                            <i class="ph ph-calendar-plus text-base text-[#eebc3f]"></i>
                            <span>Bulk Schedule Defenses</span>
                        </button>
                        <button
                            type="button"
                            @click="openScheduleModal()"
                            @disabled(($defenseRooms ?? collect())->isEmpty())
                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-600 shadow-2xs hover:bg-slate-50 transition-colors cursor-pointer"
                            title="Schedule an individual group defense"
                        >
                            <i class="ph ph-user text-base text-slate-500"></i>
                            <span>Individual</span>
                        </button>
                    </div>
                </div>

                <!-- Venue Manager Section -->
                <section x-show="showVenueManager" x-transition class="rounded-3xl border border-emerald-200/80 bg-white p-6 sm:p-7 shadow-sm space-y-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-black font-heading text-slate-900">Defense Venue Catalog</h2>
                            <p class="mt-0.5 text-xs text-slate-500 font-medium">Create and maintain active presentation rooms available for defense bookings.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-[10px] font-black uppercase text-[#0e5c3a]">
                            {{ ($defenseRooms ?? collect())->count() }} Active {{ Str::plural('Venue', ($defenseRooms ?? collect())->count()) }}
                        </span>
                    </div>

                    @if ($errors->has('code') || $errors->has('name') || $errors->has('location_notes'))
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-xs font-semibold text-rose-700">
                            {{ $errors->first('code') ?: ($errors->first('name') ?: $errors->first('location_notes')) }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('facilitator.defense-rooms.store') }}" class="grid gap-3.5 lg:grid-cols-[0.8fr_1.2fr_1.5fr_auto] items-end bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80">
                        @csrf
                        <div class="space-y-1">
                            <label for="venue-code" class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">Room Code</label>
                            <input id="venue-code" name="code" value="{{ old('code') }}" maxlength="50" required placeholder="e.g. CEAC-301" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label for="venue-name" class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">Venue Name</label>
                            <input id="venue-name" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="e.g. Conference Room" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label for="venue-location" class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">Location Notes</label>
                            <input id="venue-location" name="location_notes" value="{{ old('location_notes') }}" maxlength="1000" placeholder="e.g. 3rd Floor, CEAC Building" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                        </div>
                        <button type="submit" class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2 text-xs font-bold text-white shadow-sm transition-colors cursor-pointer">
                            Add Venue
                        </button>
                    </form>

                    <div class="space-y-3">
                        @forelse ($allDefenseRooms ?? [] as $venue)
                            <article class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 xl:flex-row xl:items-end">
                                <form method="POST" action="{{ route('facilitator.defense-rooms.update', $venue) }}" class="grid flex-1 gap-3 md:grid-cols-[0.8fr_1.2fr_1.5fr_auto]">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label for="venue-code-{{ $venue->id }}" class="mb-1 block text-[9px] font-bold uppercase text-slate-400">Code</label>
                                        <input id="venue-code-{{ $venue->id }}" name="code" value="{{ $venue->code }}" maxlength="50" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                                    </div>
                                    <div>
                                        <label for="venue-name-{{ $venue->id }}" class="mb-1 block text-[9px] font-bold uppercase text-slate-400">Name</label>
                                        <input id="venue-name-{{ $venue->id }}" name="name" value="{{ $venue->name }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                                    </div>
                                    <div>
                                        <label for="venue-location-{{ $venue->id }}" class="mb-1 block text-[9px] font-bold uppercase text-slate-400">Location</label>
                                        <input id="venue-location-{{ $venue->id }}" name="location_notes" value="{{ $venue->location_notes }}" maxlength="1000" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0e5c3a] focus:outline-none">
                                    </div>
                                    <button type="submit" class="self-end rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-100 transition-colors cursor-pointer">Save</button>
                                </form>

                                <form method="POST" action="{{ $venue->is_active ? route('facilitator.defense-rooms.deactivate', $venue) : route('facilitator.defense-rooms.activate', $venue) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="w-full rounded-xl px-4 py-2 text-xs font-bold xl:w-auto transition-colors cursor-pointer {{ $venue->is_active ? 'border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border border-emerald-200 bg-emerald-50 text-[#0e5c3a] hover:bg-emerald-100' }}">
                                        {{ $venue->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 py-8 text-center text-xs text-slate-400">
                                No defense venues exist yet. Add your first room above to enable defense bookings.
                            </div>
                        @endforelse
                    </div>
                </section>

                <!-- 4 KPI Cards Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Total Scheduled</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none" x-text="totalScheduledCount">3</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">This Week</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none" x-text="thisWeekCount">2</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Pending Defense</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none" x-text="pendingDefenseCount">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0" :class="pendingDefenseCount > 0 ? 'animate-pulse' : ''">
                            <i class="ph ph-calendar-plus"></i>
                        </span>
                    </div>

                    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Completed</span>
                            <span class="text-2xl sm:text-3xl font-black font-heading text-slate-900 mt-1 block leading-none" x-text="completedDefenseCount">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-calendar-check"></i>
                        </span>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 flex flex-wrap gap-3 items-center">
                    <span class="text-slate-400 pl-1 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider">
                        <i class="ph ph-funnel text-base"></i>
                        <span>Filter:</span>
                    </span>
                    <select 
                        x-model="defenseTypeFilter"
                        class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl outline-none focus:border-[#0e5c3a] cursor-pointer"
                    >
                        <option value="All">All Defense Types</option>
                        <option value="Title Proposal">Title Proposal</option>
                        <option value="Proposal Defense">Proposal Defense</option>
                        <option value="Pre-Final Defense">Pre-Final Defense</option>
                        <option value="Final Defense">Final Defense</option>
                    </select>

                    <select 
                        x-model="defenseStatusFilter"
                        class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl outline-none focus:border-[#0e5c3a] cursor-pointer"
                    >
                        <option value="All">All Statuses</option>
                        <option value="Scheduled">Scheduled</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <!-- Defenses List -->
                <div class="space-y-6">
                    <template x-for="def in filteredDefenseList" :key="def.id">
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-5 transition-all duration-300 hover:shadow-xl border-l-4 animate-fade-in relative overflow-hidden"
                             :class="{
                                 'border-l-emerald-600': def.status === 'Scheduled',
                                 'border-l-amber-500': def.status === 'Pending',
                                 'border-l-slate-400': def.status === 'Completed'
                             }"
                        >
                            <!-- Top Card Header -->
                            <div class="flex justify-between items-start gap-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-black text-[#0e5c3a]" x-text="def.type">Proposal Defense</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase border"
                                              :class="{
                                                  'bg-emerald-50 text-[#0e5c3a] border-emerald-200': def.status === 'Scheduled',
                                                  'bg-amber-50 text-amber-800 border-amber-200': def.status === 'Pending',
                                                  'bg-slate-100 text-slate-600 border-slate-200': def.status === 'Completed'
                                              }"
                                              x-text="def.status"
                                        >
                                            Scheduled
                                        </span>
                                    </div>
                                    <h2 class="text-base sm:text-lg font-black font-heading text-slate-900" x-text="def.title">AI-Powered Traffic Management System</h2>
                                    <p class="text-xs text-slate-500 font-medium">Research Cohort: <strong class="text-slate-800" x-text="def.student">Group Name</strong></p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span x-show="def.presentation_order_label" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-[10px] font-black text-amber-900 shrink-0 flex items-center gap-1.5 shadow-2xs">
                                        <i class="ph ph-list-numbers text-xs text-amber-600"></i>
                                        <span x-text="def.presentation_order_label"></span>
                                    </span>
                                    <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-[10px] font-bold text-slate-500 shrink-0">
                                        Group Schedule
                                    </span>
                                </div>
                            </div>

                            <hr class="border-slate-100">

                            <!-- Date/Time/Venue Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50/70 border border-slate-100">
                                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-lg flex-shrink-0">
                                        <i class="ph ph-calendar"></i>
                                    </span>
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase tracking-wider block text-[9px]">Date</span>
                                        <span class="text-slate-900 font-black block mt-0.5" x-text="def.date">May 25, 2026</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50/70 border border-slate-100">
                                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-lg flex-shrink-0">
                                        <i class="ph ph-clock"></i>
                                    </span>
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase tracking-wider block text-[9px]" x-text="def.presentation_order ? 'Session Window' : 'Time'">Time</span>
                                        <span class="text-slate-900 font-black block mt-0.5" x-text="def.time">9:00 AM - 11:00 AM</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50/70 border border-slate-100">
                                    <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-lg flex-shrink-0">
                                        <i class="ph ph-map-pin"></i>
                                    </span>
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase tracking-wider block text-[9px]">Venue</span>
                                        <span class="text-slate-900 font-black block mt-0.5" x-text="def.venue">Room 405</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Members tags -->
                            <div class="space-y-2 pt-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Panel Members</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="p in def.panel" :key="`${p.position}-${p.name}`">
                                        <span class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-800 rounded-xl text-xs font-semibold" x-text="`${p.position_label}: ${p.name}`">Panelist Name</span>
                                    </template>
                                    <template x-if="def.panel.length === 0">
                                        <span class="text-slate-400 text-xs italic">No panel members assigned yet.</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Action Bar & Evaluation Lifecycle Strip -->
                            <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <!-- Left: Lifecycle Status / Summary Signer Notice -->
                                <div class="flex items-center gap-2">
                                    <template x-if="!def.evaluation_round && def.status === 'Scheduled'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold shadow-2xs">
                                            <i class="ph ph-hourglass text-sm text-amber-600"></i>
                                            <span>Ready for Evaluation Round</span>
                                        </span>
                                    </template>
                                    <template x-if="def.evaluation_round?.status === 'open' || def.evaluation_round?.status === 'in_progress'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold shadow-2xs animate-pulse">
                                            <i class="ph ph-spinner text-sm text-blue-600 animate-spin"></i>
                                            <span>Evaluation In Progress (<strong x-text="def.evaluation_round.submitted_count"></strong>/3 Submitted)</span>
                                        </span>
                                    </template>
                                    <template x-if="def.evaluation_round?.status === 'complete'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold shadow-2xs">
                                            <i class="ph ph-signature text-sm text-purple-600"></i>
                                            <span>All 3 Submitted — Awaiting RES-037 Signature (<span x-text="def.evaluation_round.summary_signer_name || 'Signer'"></span>)</span>
                                        </span>
                                    </template>
                                    <template x-if="def.evaluation_round?.status === 'finalized'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-[#0e5c3a] text-xs font-bold shadow-2xs">
                                            <i class="ph ph-check-circle text-sm text-emerald-600"></i>
                                            <span>RES-037 Signed &amp; Finalized</span>
                                        </span>
                                    </template>
                                    <template x-if="def.evaluation_round?.status === 'released'">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 border border-emerald-200 text-[#0e5c3a] text-xs font-bold shadow-2xs">
                                            <i class="ph ph-broadcast text-sm text-emerald-600"></i>
                                            <span>Results Released to Cohort</span>
                                        </span>
                                    </template>
                                </div>

                                <!-- Right: Facilitator Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <!-- 1. Open Evaluation Round Button -->
                                    <template x-if="def.can_manage && !def.evaluation_round && def.status === 'Scheduled'">
                                        <form method="POST" :action="`/facilitator/defenses/${def.defense_id}/evaluation-round`" onsubmit="return confirm('Open defense evaluation round? This will freeze the 3 assigned panel members and enable panelist scoring in their workspace.')">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="px-4 py-2.5 bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-950/20 transition-all flex items-center gap-2 cursor-pointer"
                                            >
                                                <i class="ph ph-play-circle text-base text-[#eebc3f]"></i>
                                                <span>Open Evaluation Round</span>
                                            </button>
                                        </form>
                                    </template>

                                    <!-- View RES-037 Summary Sheet -->
                                    <template x-if="def.res037_url || def.evaluation_round?.res037_url">
                                        <a
                                            :href="def.res037_url || def.evaluation_round?.res037_url"
                                            class="px-4 py-2.5 bg-gradient-to-r from-purple-700 to-indigo-800 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2"
                                        >
                                            <i class="ph ph-file-text text-base text-purple-200"></i>
                                            <span>View RES-037 Summary</span>
                                        </a>
                                    </template>

                                    <!-- 2. Release Results Button -->
                                    <template x-if="def.can_manage && def.evaluation_round?.status === 'finalized'">
                                        <form method="POST" :action="`/facilitator/evaluation-rounds/${def.evaluation_round.id}/release`" onsubmit="return confirm('Release defense evaluation results to the student researchers and adviser?')">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="px-4 py-2.5 bg-gradient-to-r from-blue-700 to-indigo-800 hover:brightness-110 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer"
                                            >
                                                <i class="ph ph-share-network text-base text-blue-200"></i>
                                                <span>Release Evaluation Results</span>
                                            </button>
                                        </form>
                                    </template>

                                    <!-- 3. Mark Defense Complete Button -->
                                    <template x-if="def.can_manage && def.evaluation_round?.status === 'released' && def.status !== 'Completed'">
                                        <form method="POST" :action="`/facilitator/defenses/${def.defense_id}/complete`" onsubmit="return confirm('Mark this defense as completed?')">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#073823] text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer"
                                            >
                                                <i class="ph ph-check text-base text-[#eebc3f]"></i>
                                                <span>Complete Defense</span>
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- No scheduled defenses -->
                    <template x-if="filteredDefenseList.length === 0">
                        <div class="bg-white rounded-3xl p-12 text-center text-slate-400 font-bold text-xs border border-dashed border-slate-200 w-full space-y-2">
                            <i class="ph ph-calendar-x text-3xl text-slate-300"></i>
                            <p>No defense schedules found matching your current filters.</p>
                        </div>
                    </template>
                </div>
            </div>

            @if ($statistics !== null)
                <!-- TAB: Research Statistics -->
                <div x-show="activeTab === 'statistics'" x-cloak class="animate-fade-in">
                    <x-research-statistics.facilitator-dashboard :statistics="$statistics" />
                </div>
            @endif

            <!-- TAB: Research Reports (Document Screening / Review) -->
            <div x-show="activeTab === 'reports'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Research Reports</span>
                    </div>
                </div>

                <!-- Document Header Card -->
                <div class="bg-white rounded-[2.5rem] border border-gray-150/80 shadow-md p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-start md:items-center gap-5">
                        <div class="w-16 h-16 rounded-[1.5rem] bg-red-50 text-red-500 border border-red-100 flex items-center justify-center text-3xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </div>
                        <div class="space-y-1">
                            <h2 class="text-lg md:text-xl font-bold text-gray-800 leading-snug">Chapter 3 - Research Methodology (Revised)</h2>
                            <p class="text-xs text-gray-455 font-bold block">Machine Learning Applications in Agricultural Pest Detection</p>
                            <div class="flex flex-wrap items-center gap-3 text-[10px] text-gray-400 font-bold uppercase tracking-wider pt-0.5">
                                <span>Uploaded: <span class="text-gray-655">May 15, 2026</span></span>
                                <span>•</span>
                                <span>Version <span class="text-gray-655">2.3</span></span>
                                <span>•</span>
                                <span><span class="text-gray-655">42</span> pages</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Right Action Buttons -->
                    <div class="flex items-center gap-3 flex-wrap flex-shrink-0">
                        <button 
                            type="button"
                            @click="alert('Downloading methodology document Chapter_3_Methodology_v2.3.pdf...')"
                            class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md shadow-[#0e5c3a]/10 hover:shadow-lg flex items-center gap-2 cursor-pointer transition-all duration-200"
                        >
                            <i class="ph ph-download-simple text-base font-bold"></i>
                            <span>Download</span>
                        </button>
                        <button 
                            type="button"
                            @click="alert('Loading full document preview...')"
                            class="px-5 py-3 bg-white border border-gray-255 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl shadow-2xs hover:shadow-xs cursor-pointer transition-all duration-200"
                        >
                            <span>View Full Document</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Widgets Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-2xl p-5 border-l-4 border-l-emerald-600 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block">Approved</span>
                            <span class="text-2xl font-bold text-gray-850 mt-1 block leading-none" x-text="reportsApprovedCount">8</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-2xl p-5 border-l-4 border-l-amber-500 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block">Revisions</span>
                            <span class="text-2xl font-bold text-gray-850 mt-1 block leading-none" x-text="reportsRevisionsCount">5</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-warning-circle"></i>
                        </span>
                    </div>

                    <!-- Comments -->
                    <div class="bg-white rounded-2xl p-5 border-l-4 border-l-blue-600 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block">Comments</span>
                            <span class="text-2xl font-bold text-gray-850 mt-1 block leading-none" x-text="reportsCommentsCount">12</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-chat-circle-dots"></i>
                        </span>
                    </div>

                    <!-- Critical -->
                    <div class="bg-white rounded-2xl p-5 border-l-4 border-l-red-650 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block">Critical</span>
                            <span class="text-2xl font-bold text-gray-850 mt-1 block leading-none" x-text="reportsCriticalCount">2</span>
                        </div>
                        <span class="w-10 h-10 rounded-xl bg-red-50 text-red-650 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Two Column Main Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left: Document Preview (Spans 2 columns) -->
                    <div class="lg:col-span-2 bg-white rounded-[2.5rem] border border-gray-100 shadow-xl shadow-slate-200/20 p-6 md:p-8 space-y-6">
                        <h3 class="font-bold text-gray-850 text-base">Document Preview</h3>
                        
                        <!-- Replica PDF Paper page sheet -->
                        <div class="bg-gray-50/50 border border-gray-150 rounded-[2rem] p-6 md:p-10 space-y-8 font-serif leading-relaxed text-xs text-gray-700 min-h-[500px]">
                            <div class="text-center space-y-2">
                                <h1 class="text-lg md:text-xl font-bold text-gray-900">Chapter 3: Research Methodology</h1>
                            </div>
                            
                            <p class="text-justify indent-8 text-gray-700">
                                This chapter presents the research design, methods, and procedures employed in this study. The methodology encompasses the research approach, data collection instruments, sampling techniques, and data analysis methods.
                            </p>

                            <div class="space-y-3">
                                <h4 class="text-sm font-bold text-gray-850">3.1 Research Design</h4>
                                <p class="text-justify indent-8 text-gray-700">
                                    This study utilizes a quantitative research approach with an experimental design to evaluate the effectiveness of machine learning algorithms in detecting agricultural pests...
                                </p>
                            </div>

                            <div class="space-y-3">
                                <h4 class="text-sm font-bold text-gray-850">3.2 Data Collection</h4>
                                <p class="text-justify indent-8 text-gray-700">
                                    The data collection process involves capturing high-resolution images of crops from various agricultural sites across South Cotabato province...
                                </p>
                            </div>

                            <!-- Warning / Reviewer Note Box -->
                            <div class="p-5 bg-amber-50 border-l-4 border-l-amber-500 border border-amber-100/50 rounded-2xl flex items-start gap-4">
                                <span class="text-lg text-amber-600 flex-shrink-0 pt-0.5">
                                    <i class="ph ph-info font-bold"></i>
                                </span>
                                <div>
                                    <span class="text-[9px] font-extrabold text-amber-700 uppercase tracking-wider block">Reviewer Note</span>
                                    <p class="text-[11px] font-bold text-amber-800 mt-1 leading-snug">
                                        Consider adding more details about the image preprocessing steps used in your methodology.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Comments & Feedback (Spans 1 column) -->
                    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl shadow-slate-200/20 p-6 md:p-8 flex flex-col justify-between gap-6 min-h-[500px]">
                        <div class="space-y-6">
                            <h3 class="font-bold text-gray-850 text-base">Comments & Feedback</h3>
                            
                            <!-- Comments Feed -->
                            <div class="space-y-4 max-h-[350px] overflow-y-auto pr-1">
                                <template x-for="cmt in reportsCommentsList" :key="cmt.id">
                                    <div class="p-4 rounded-2xl border transition-all duration-200 flex flex-col gap-3 animate-fade-in"
                                         :class="{
                                             'bg-orange-50/20 border-orange-100': cmt.role === 'Adviser',
                                             'bg-emerald-50/20 border-emerald-100': cmt.role === 'Panelist',
                                             'bg-purple-50/20 border-purple-100': cmt.role === 'Technical Editor',
                                             'bg-gray-50/40 border-gray-150': cmt.role === 'Research Facilitator'
                                         }"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-extrabold text-[11px] text-gray-800" x-text="cmt.author">Reviewer Name</h4>
                                                <span class="text-[8px] font-extrabold text-gray-400 uppercase tracking-wider block mt-0.5" x-text="cmt.role">Role</span>
                                            </div>
                                            <span class="text-[9px] text-gray-400 font-semibold" x-text="cmt.time">2 hours ago</span>
                                        </div>

                                        <p class="text-xs text-gray-650 leading-relaxed font-semibold" x-text="cmt.content">Comment Content</p>

                                        <div class="flex justify-between items-center pt-1.5 border-t border-gray-100/50">
                                            <span class="text-[9px] font-extrabold text-[#0e5c3a]" x-text="cmt.page">Page 12</span>
                                            <div class="flex items-center gap-3 text-[10px] font-bold">
                                                <button type="button" @click="alert('Replying to comment...')" class="text-emerald-755 hover:text-emerald-955 cursor-pointer">Reply</button>
                                                <button type="button" @click="resolveReportsComment(cmt.id)" class="text-blue-600 hover:text-blue-800 cursor-pointer">Resolve</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="reportsCommentsList.length === 0">
                                    <div class="py-8 text-center text-gray-400 font-bold text-xs">
                                        No active comments. All feedback resolved!
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Add Comment Form (Positioned at bottom of column) -->
                        <form @submit.prevent="postReportsComment()" class="space-y-4 pt-4 border-t border-gray-100/80">
                            <textarea 
                                x-model="reportsNewCommentText"
                                placeholder="Add a comment..."
                                rows="3"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-850 placeholder-gray-400 focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 outline-none resize-none transition-all"
                                required
                            ></textarea>
                            <div class="flex justify-end">
                                <button 
                                    type="submit" 
                                    class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md cursor-pointer transition-colors"
                                >
                                    Post Comment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bottom Review Actions Panel -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-lg p-6 space-y-4">
                    <h3 class="font-bold text-gray-850 text-sm">Review Actions</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <button 
                            type="button" 
                            @click="approveReportsDocument()"
                            class="py-3.5 bg-emerald-650 hover:bg-emerald-755 text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-md shadow-emerald-650/10 cursor-pointer transition-all duration-200"
                        >
                            <i class="ph ph-check-circle text-base"></i>
                            <span>Approve Document</span>
                        </button>
                        <button 
                            type="button" 
                            @click="requestReportsRevisions()"
                            class="py-3.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-md shadow-amber-500/10 cursor-pointer transition-all duration-200"
                        >
                            <i class="ph ph-warning-circle text-base"></i>
                            <span>Request Revisions</span>
                        </button>
                        <button 
                            type="button" 
                            @click="rejectReportsDocument()"
                            class="py-3.5 bg-red-650 hover:bg-red-755 text-white text-xs font-bold rounded-2xl flex items-center justify-center gap-2 shadow-md shadow-red-650/10 cursor-pointer transition-all duration-200"
                        >
                            <i class="ph ph-x-circle text-base"></i>
                            <span>Reject Document</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB: Research Repository -->
            @isset($repositoryDocuments)
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8 animate-fade-in">
                <x-student-section-heading title="Research Repository" description="Browse documents from research groups in the classes you manage." />
                <x-document-repository :documents="$repositoryDocuments" :filters="$repositoryFilters" :stats="$repositoryStats" :stage-options="$repositoryStageOptions" :status-options="$repositoryStatusOptions" />
            </div>
            @else
            <div x-show="activeTab === 'repository'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Research Repository</span>
                    </div>
                </div>

                <!-- Stats Widgets Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Total Files -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-500 border border-gray-100 flex items-center justify-center text-2xl flex-shrink-0">
                                <i class="ph ph-file-text"></i>
                            </span>
                            <div>
                                <span class="text-2xl font-bold text-gray-850 block leading-none" x-text="repositoryStats.total">6</span>
                                <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Total Files</span>
                            </div>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-2xl flex-shrink-0">
                                <i class="ph ph-check-circle"></i>
                            </span>
                            <div>
                                <span class="text-2xl font-bold text-gray-855 block leading-none" x-text="repositoryStats.approved">2</span>
                                <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Approved</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Review -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-2xl flex-shrink-0">
                                <i class="ph ph-clock"></i>
                            </span>
                            <div>
                                <span class="text-2xl font-bold text-gray-855 block leading-none" x-text="repositoryStats.pending">2</span>
                                <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Pending Review</span>
                            </div>
                        </div>
                    </div>

                    <!-- For Evaluation -->
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between animate-fade-in">
                        <div class="flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-2xl flex-shrink-0">
                                <i class="ph ph-textbox"></i>
                            </span>
                            <div>
                                <span class="text-2xl font-bold text-gray-855 block leading-none" x-text="repositoryStats.evaluation">1</span>
                                <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">For Evaluation</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div class="flex flex-col sm:flex-row items-center gap-4 bg-white p-4 rounded-2xl border border-gray-150/80 shadow-2xs">
                    <!-- Search Input -->
                    <div class="relative flex-grow w-full">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400">
                            <i class="ph ph-magnifying-glass"></i>
                        </span>
                        <input 
                            type="text" 
                            x-model="repositorySearchQuery"
                            placeholder="Search documents or researcher name..."
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-xs text-gray-800 placeholder-gray-400 focus:bg-white focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 outline-none transition-all"
                        >
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div class="flex items-center gap-2 w-full sm:w-auto flex-shrink-0">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap"><i class="ph ph-funnel mr-1"></i>Filter</span>
                        <select 
                            x-model="repositoryStatusFilter"
                            class="w-full sm:w-44 px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl text-xs text-gray-700 font-bold outline-none cursor-pointer focus:bg-white focus:border-[#0e5c3a]"
                        >
                            <option value="All">All Status</option>
                            <option value="Approved">Approved</option>
                            <option value="Pending Review">Pending Review</option>
                            <option value="Reviewed">Reviewed</option>
                            <option value="For Evaluation">For Evaluation</option>
                        </select>
                    </div>
                </div>

                <!-- Documents Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <template x-for="file in filteredRepositoryFiles" :key="file.id">
                        <div class="bg-white rounded-[2rem] border transition-all duration-300 hover:shadow-lg flex flex-col justify-between"
                             :class="{
                                 'border-blue-100/80 hover:border-blue-300': file.status === 'Reviewed',
                                 'border-amber-100/80 hover:border-amber-300': file.status === 'Pending Review',
                                 'border-purple-100/80 hover:border-purple-300': file.status === 'For Evaluation',
                                 'border-emerald-100/80 hover:border-emerald-300': file.status === 'Approved'
                             }"
                        >
                            <!-- Top tag row -->
                            <div class="p-6 pb-4 flex justify-between items-center">
                                <span class="px-2.5 py-1 text-[9px] font-extrabold rounded-md uppercase tracking-wider border"
                                      :class="file.typeClass"
                                      x-text="file.type"
                                >PDF</span>
                                <span class="px-3 py-1 text-[9px] font-bold rounded-full flex items-center gap-1.5"
                                      :class="file.statusClass"
                                >
                                    <i :class="file.statusIcon"></i>
                                    <span x-text="file.status">Status</span>
                                </span>
                            </div>

                            <!-- Document Meta Details -->
                            <div class="px-6 space-y-2 flex-grow">
                                <span class="text-[9px] font-extrabold text-[#eebc3f] uppercase tracking-wider block" x-text="file.subtitle">CHAPTER 1</span>
                                <h4 class="text-sm font-bold text-gray-850 leading-snug line-clamp-1" x-text="file.title">Chapter Title</h4>
                                <p class="text-xs text-gray-450 font-semibold leading-relaxed line-clamp-2" x-text="file.description">Description text...</p>
                                
                                <div class="flex items-center gap-2.5 text-[9px] text-gray-400 font-bold uppercase tracking-wider pt-2 border-t border-gray-50">
                                    <span x-text="file.size">Size</span>
                                    <span>•</span>
                                    <span x-text="file.date">Date</span>
                                    <span>•</span>
                                    <span x-text="file.author">Author</span>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div class="p-6 pt-4 grid grid-cols-2 gap-3">
                                <button 
                                    type="button"
                                    @click="selectedRepositoryFile = file"
                                    class="py-2.5 border border-emerald-250 hover:bg-emerald-50/40 text-[#0e5c3a] text-[10px] font-extrabold rounded-xl transition-all cursor-pointer text-center"
                                >
                                    <i class="ph ph-eye mr-1"></i>View
                                </button>
                                <button 
                                    type="button"
                                    @click="alert(`Starting download for ${file.title} (${file.size})...`)"
                                    class="py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-650 text-[10px] font-extrabold rounded-xl transition-all cursor-pointer text-center"
                                >
                                    <i class="ph ph-download-simple mr-1"></i>Download
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="filteredRepositoryFiles.length === 0">
                        <div class="col-span-full py-16 text-center space-y-3 bg-white border border-gray-150 rounded-[2rem]">
                            <div class="text-3xl text-gray-300">
                                <i class="ph ph-folder-open"></i>
                            </div>
                            <p class="text-xs text-gray-455 font-bold">No files match your search query or status filter.</p>
                        </div>
                    </template>
                </div>
            </div>

            @endisset

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings', [
                    'portalType' => 'Facilitator Portal',
                    'accessLevel' => 'Research Facilitator Access'
                ])
            </div>

            <!-- TAB: Official Facilitator Forms -->
            <div x-show="activeTab === 'forms'" x-cloak class="space-y-6 animate-fade-in">
                @include('pages.facilitator.forms.index')
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'classes', 'join-requests', 'settings', 'monitoring', 'advisers', 'screening', 'defenses', 'statistics', 'reports', 'repository', 'forms'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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
        <div @click.away="selectedApproval = null" class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl space-y-4">
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

    <!-- Update Progress Modal Mockup -->
    <div x-show="showMonitoringEditModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showMonitoringEditModal = false" class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-bold text-gray-800 text-sm">Update Research Progress</h3>
                    <p class="text-[10px] text-gray-400 mt-0.5" x-text="activeMonitoringProject.title"></p>
                </div>
                <button @click="showMonitoringEditModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            
            <div class="space-y-4">
                <template x-for="(milestone, index) in activeMonitoringProject.milestones" :key="index">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-3 bg-gray-50/50 hover:bg-gray-50 border border-gray-100 rounded-2xl transition-colors">
                        <div class="flex-grow min-w-0 pr-4">
                            <span class="font-bold text-gray-700 text-xs block" x-text="milestone.title">Milestone Title</span>
                            <!-- Date input field -->
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">Date:</span>
                                <input 
                                    type="text" 
                                    x-model="milestone.date"
                                    class="bg-white border border-gray-250 text-gray-700 text-[10px] px-2 py-1 rounded-md outline-none w-32 focus:border-[#0e5c3a]"
                                >
                            </div>
                        </div>

                        <!-- Status picker and details -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Toggle status selector -->
                            <div class="flex bg-white border border-gray-200 rounded-lg p-0.5">
                                <button 
                                    type="button"
                                    @click="milestone.status = 'Pending'; if(milestone.date === 'In Progress' || milestone.date === 'Completed') milestone.date = 'Not Started'; milestone.details = ''"
                                    :class="milestone.status === 'Pending' ? 'bg-gray-150 text-gray-700 font-bold' : 'text-gray-400 hover:text-gray-600'"
                                    class="px-2 py-1 text-[9px] rounded-md transition-colors cursor-pointer"
                                >
                                    Pending
                                </button>
                                <button 
                                    type="button"
                                    @click="milestone.status = 'In Progress'; milestone.date = 'In Progress'; milestone.details = 'Currently working on this milestone'"
                                    :class="milestone.status === 'In Progress' ? 'bg-amber-500 text-white font-bold' : 'text-gray-400 hover:text-gray-600'"
                                    class="px-2 py-1 text-[9px] rounded-md transition-colors cursor-pointer"
                                >
                                    In Progress
                                </button>
                                <button 
                                    type="button"
                                    @click="milestone.status = 'Completed'; if(milestone.date === 'In Progress' || milestone.date === 'Not Started') milestone.date = new Date().toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}); milestone.details = 'All requirements met and approved'"
                                    :class="milestone.status === 'Completed' ? 'bg-[#0e5c3a] text-white font-bold' : 'text-gray-400 hover:text-gray-600'"
                                    class="px-2 py-1 text-[9px] rounded-md transition-colors cursor-pointer"
                                >
                                    Completed
                                </button>
                            </div>

                            <!-- Details custom input -->
                            <input 
                                type="text" 
                                x-model="milestone.details" 
                                placeholder="Optional details"
                                class="bg-white border border-gray-250 text-gray-700 text-[10px] px-2.5 py-1.5 rounded-lg outline-none w-48 focus:border-[#0e5c3a]"
                            >
                        </div>
                    </div>
                </template>
            </div>
            
            <hr class="border-gray-100">
            <div class="pt-2 flex justify-end gap-3">
                <button @click="showMonitoringEditModal = false" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- Legacy proposal mockup retained only as reference; authoritative document history is rendered in the screening tab. --}}
    @if (false)
    <!-- Proposal Details Modal Mockup -->
    <div x-show="showProposalDetailModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showProposalDetailModal = false" class="bg-white rounded-2xl w-full max-w-2xl p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-emerald-700 font-extrabold text-[10px] uppercase tracking-wider block" x-text="activeProposal.proposal_id">PROP-2026-001</span>
                    <h3 class="font-bold text-gray-800 text-sm mt-1" x-text="activeProposal.title">Proposal Title</h3>
                </div>
                <button @click="showProposalDetailModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <hr class="border-gray-100">
            
            <div class="space-y-4 text-xs leading-relaxed text-gray-650">
                <!-- Metadata details -->
                <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 border border-gray-150 rounded-2xl">
                    <div>
                        <span class="text-gray-400 font-bold block text-[9px] uppercase">College/Department</span>
                        <span class="text-gray-700 font-bold mt-1 block" x-text="activeProposal.department">Department Name</span>
                    </div>
                    <div>
                        <span class="text-gray-400 font-bold block text-[9px] uppercase">Submitted Date</span>
                        <span class="text-gray-700 font-bold mt-1 block" x-text="activeProposal.submitted">Submitted Date</span>
                    </div>
                </div>

                <!-- Abstract section -->
                <div class="space-y-1.5">
                    <span class="text-gray-800 font-extrabold block text-[10px] uppercase tracking-wider">Abstract / Project Summary</span>
                    <p class="text-justify bg-gray-50/50 p-4 border border-gray-100 rounded-2xl font-medium text-gray-600" x-text="activeProposal.abstract">
                        Proposal abstract description content.
                    </p>
                </div>

                <!-- Objectives section -->
                <div class="space-y-1.5">
                    <span class="text-gray-800 font-extrabold block text-[10px] uppercase tracking-wider">Project Objectives</span>
                    <div class="bg-gray-50/50 p-4 border border-gray-100 rounded-2xl font-semibold text-gray-650 whitespace-pre-line" x-text="activeProposal.objectives">
                        Proposal objectives description content.
                    </div>
                </div>
            </div>
            
            <hr class="border-gray-100">
            <div class="pt-2 flex justify-end gap-3">
                <button @click="showProposalDetailModal = false" class="px-5 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    @endif

    <!-- Authoritative Defense Scheduler -->
    <div x-show="showScheduleModal" x-transition x-cloak class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div @click.away="showScheduleModal = false" class="bg-white rounded-3xl w-full max-w-3xl p-6 sm:p-8 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto animate-scale-up border border-slate-200 relative overflow-hidden">
            <!-- Top Accent Stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

            <div class="flex justify-between items-start">
                <div>
                    <h3 class="font-black font-heading text-slate-900 text-lg sm:text-xl tracking-tight">Schedule Group Defense</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Select a research group to automatically populate research title, leader, and faculty adviser.</p>
                </div>
                <button type="button" @click="showScheduleModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors flex items-center justify-center text-base cursor-pointer">
                    <i class="ph ph-x font-bold"></i>
                </button>
            </div>
            <hr class="border-slate-100">

            @if ($errors->has('defense_schedule') || $errors->has('research_class_group_id') || $errors->has('defense_type') || $errors->has('room_id') || $errors->has('starts_at') || $errors->has('ends_at') || $errors->has('chairperson_user_id') || $errors->has('panel_user_ids'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50/90 px-4 py-3 text-xs font-semibold text-rose-700">
                    {{ $errors->first('defense_schedule') ?: $errors->first() }}
                </div>
            @endif

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-xs font-medium text-emerald-900 flex items-center gap-2.5">
                <i class="ph ph-info text-base text-[#0e5c3a] shrink-0"></i>
                <span><strong>Defense Session Booking:</strong> Groups are allocated specific time slots, venue rooms, Chairperson, and panel members. Overlaps are prevented automatically.</span>
            </div>

            <form method="POST" action="{{ route('facilitator.defenses.store') }}" class="space-y-5 text-xs">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="def-group" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Research Group</label>
                        <select id="def-group" name="research_class_group_id" x-model="scheduleForm.groupId" @change="onDefenseGroupChange()" required class="w-full px-4 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="">Select one of your research groups...</option>
                            <template x-for="groupOption in eligibleDefenseSchedulingGroups" :key="groupOption.id">
                                <option :value="groupOption.id" x-text="`${groupOption.name} — ${groupOption.class_name}${groupOption.adviser_name ? ` (Adviser: ${groupOption.adviser_name})` : ' (No adviser)'}`"></option>
                            </template>
                        </select>
                        <p x-show="unavailableDefenseGroupCount > 0" x-cloak class="flex items-start gap-1.5 text-[10px] font-semibold leading-4 text-amber-700">
                            <i class="ph ph-lock-key mt-0.5"></i>
                            <span><span x-text="unavailableDefenseGroupCount"></span> group(s) hidden until the matching RES-033 is fully signed.</span>
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="def-type" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Defense Type</label>
                        <select id="def-type" name="defense_type" x-model="scheduleForm.type" @change="onDefenseTypeChange()" required class="w-full px-4 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="title_presentation">Title Proposal / Title Presentation</option>
                            <option value="proposal_defense">Proposal Defense</option>
                            <option value="pre_final_defense">Pre-Final Defense</option>
                            <option value="final_defense">Final Defense</option>
                        </select>
                        <p class="flex items-start gap-1.5 text-[10px] font-semibold leading-4 text-amber-700">
                            <i class="ph ph-shield-warning mt-0.5"></i>
                            <span>The matching RES-033 must be endorsed by the Adviser and received by the Program Coordinator before scheduling.</span>
                        </p>
                    </div>
                </div>

                <div x-show="selectedDefenseGroup" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 text-xs text-amber-950 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <p class="font-bold text-amber-900"><strong>Research Title:</strong> <span x-text="selectedDefenseGroup?.research_title || 'No canonical research title registered yet'"></span></p>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-[#0e5c3a] border border-emerald-300/60" x-text="selectedDefenseGroup?.department || 'Department Scoped'"></span>
                    </div>
                    <p><strong>Cohort Leader:</strong> <span x-text="selectedDefenseGroup?.leader_name || 'Not assigned'"></span> · <strong>Adviser:</strong> <span x-text="selectedDefenseGroup?.adviser_name || 'Not assigned'"></span></p>
                    <p class="text-[10px] text-emerald-800 font-semibold flex items-center gap-1">
                        <i class="ph ph-buildings"></i>
                        <span>Displaying faculty panelists affiliated with: <strong x-text="selectedDefenseGroup?.department || 'Research Department'"></strong></span>
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label for="def-start" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Starts At</label>
                        <input id="def-start" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label for="def-end" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Ends At</label>
                        <input id="def-end" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label for="def-venue" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Presentation Room</label>
                        <select id="def-venue" name="room_id" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="">Select active room...</option>
                            @foreach ($defenseRooms as $room)
                                <option value="{{ $room->id }}" @selected((string) old('room_id') === (string) $room->id)>{{ $room->code }} — {{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="def-chair" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Chairperson</label>
                            <template x-if="selectedDefenseGroup?.active_chairperson_id && String(scheduleForm.chairpersonId) === String(selectedDefenseGroup?.active_chairperson_id)">
                                <span class="text-[10px] font-black uppercase text-[#0e5c3a] bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="ph ph-link-simple text-xs"></i>
                                    <span>Auto-Linked</span>
                                </span>
                            </template>
                        </div>
                        <select id="def-chair" name="chairperson_user_id" x-model="scheduleForm.chairpersonId" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="">Select Chairperson...</option>
                            <template x-for="candidate in eligibleChairpersons" :key="candidate.id">
                                <option :value="candidate.id" x-text="candidate.name + (candidate.department ? ' (' + candidate.department + ')' : '')"></option>
                            </template>
                        </select>
                        <div x-show="selectedDefenseGroup?.active_chairperson_id && String(scheduleForm.chairpersonId) === String(selectedDefenseGroup?.active_chairperson_id)" x-cloak class="p-2 rounded-xl bg-emerald-50 border border-emerald-200/80 text-[10px] text-[#0e5c3a] font-bold flex items-center gap-1.5">
                            <i class="ph ph-check-circle text-xs text-[#0e5c3a]"></i>
                            <span>
                                <span>Linked from <strong x-text="selectedDefenseGroup?.active_committee_label"></strong>: <span x-text="selectedDefenseGroup?.active_chairperson_name"></span></span>
                            </span>
                        </div>
                        <p x-show="!selectedDefenseGroup?.active_chairperson_id || String(scheduleForm.chairpersonId) !== String(selectedDefenseGroup?.active_chairperson_id)" class="text-[10px] text-emerald-700 font-medium">Faculty advisers are eligible to serve as Chairperson.</p>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="def-member-one" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Panel Member 1</label>
                            <template x-if="selectedDefenseGroup?.active_member_1_id && String(scheduleForm.memberOneId) === String(selectedDefenseGroup?.active_member_1_id)">
                                <span class="text-[10px] font-black uppercase text-[#0e5c3a] bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="ph ph-link-simple text-xs"></i>
                                    <span>Auto-Linked</span>
                                </span>
                            </template>
                        </div>
                        <select id="def-member-one" name="panel_user_ids[]" x-model="scheduleForm.memberOneId" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="">Select Panel Member...</option>
                            <template x-for="candidate in departmentFilteredPanelCandidates" :key="candidate.id">
                                <option :value="candidate.id" :disabled="String(candidate.id) === String(scheduleForm.chairpersonId) || String(candidate.id) === String(scheduleForm.memberTwoId)" x-text="candidate.name + (candidate.department ? ' (' + candidate.department + ')' : '') + (String(candidate.id) === String(selectedDefenseGroup?.adviser_id || '') ? ' (Group Adviser)' : '')"></option>
                            </template>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="def-member-two" class="text-[11px] font-bold text-slate-700 uppercase tracking-wider block">Panel Member 2</label>
                            <template x-if="selectedDefenseGroup?.active_member_2_id && String(scheduleForm.memberTwoId) === String(selectedDefenseGroup?.active_member_2_id)">
                                <span class="text-[10px] font-black uppercase text-[#0e5c3a] bg-emerald-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="ph ph-link-simple text-xs"></i>
                                    <span>Auto-Linked</span>
                                </span>
                            </template>
                        </div>
                        <select id="def-member-two" name="panel_user_ids[]" x-model="scheduleForm.memberTwoId" required class="w-full px-3.5 py-2.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 rounded-2xl text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 outline-none cursor-pointer transition-all">
                            <option value="">Select Panel Member...</option>
                            <template x-for="candidate in departmentFilteredPanelCandidates" :key="candidate.id">
                                <option :value="candidate.id" :disabled="String(candidate.id) === String(scheduleForm.chairpersonId) || String(candidate.id) === String(scheduleForm.memberOneId)" x-text="candidate.name + (candidate.department ? ' (' + candidate.department + ')' : '') + (String(candidate.id) === String(selectedDefenseGroup?.adviser_id || '') ? ' (Group Adviser)' : '')"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <hr class="border-slate-100 mt-6">
                <div class="flex justify-end gap-2.5 pt-2">
                    <button 
                        type="button" 
                        @click="showScheduleModal = false" 
                        class="px-4.5 py-2.5 border border-slate-200 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-6 py-2.5 bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 text-white text-xs font-black rounded-xl shadow-md shadow-emerald-950/20 cursor-pointer transition-all"
                    >
                        Confirm &amp; Schedule Defense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Research Repository File Preview Modal Mockup -->
    <div x-show="selectedRepositoryFile" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="selectedRepositoryFile = null" class="bg-white rounded-[2.5rem] w-full max-w-lg p-6 md:p-8 shadow-xl space-y-5">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 text-[8px] font-extrabold rounded-md uppercase border"
                          :class="selectedRepositoryFile?.typeClass"
                          x-text="selectedRepositoryFile?.type"
                    >PDF</span>
                    <span class="text-[9px] font-extrabold text-[#eebc3f] uppercase tracking-wider" x-text="selectedRepositoryFile?.subtitle">CHAPTER 1</span>
                </div>
                <button @click="selectedRepositoryFile = null" class="text-gray-400 hover:text-gray-650 text-xl cursor-pointer">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <div class="space-y-2">
                <h3 class="font-bold text-gray-850 text-base" x-text="selectedRepositoryFile?.title">Document Title</h3>
                <p class="text-xs text-gray-455 font-semibold" x-text="selectedRepositoryFile?.description">Description...</p>
            </div>

            <hr class="border-gray-100">

            <div class="space-y-4">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Document Abstract / Snippet</span>
                    <div class="mt-2 p-4 bg-gray-50 border border-gray-150 rounded-2xl text-xs text-gray-650 leading-relaxed font-serif text-justify"
                         x-text="selectedRepositoryFile?.abstract"
                    >
                        Abstract content snippet...
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                    <div>
                        <span class="block">Author</span>
                        <span class="text-gray-800 mt-1 block normal-case font-bold" x-text="selectedRepositoryFile?.author">Author Name</span>
                    </div>
                    <div>
                        <span class="block">File Size</span>
                        <span class="text-gray-800 mt-1 block normal-case font-bold" x-text="selectedRepositoryFile?.size">File Size</span>
                    </div>
                    <div>
                        <span class="block">Uploaded</span>
                        <span class="text-gray-800 mt-1 block normal-case font-bold" x-text="selectedRepositoryFile?.date">Upload Date</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button 
                    type="button" 
                    @click="selectedRepositoryFile = null" 
                    class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl cursor-pointer"
                >
                    Close Preview
                </button>
                <button 
                    type="button" 
                    @click="alert(`Downloading ${selectedRepositoryFile?.title}...`); selectedRepositoryFile = null" 
                    class="px-6 py-2.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md cursor-pointer transition-colors"
                >
                    Download File
                </button>
            </div>
        </div>
    </div>

    @include('pages.facilitator.defense-class-committees-modal')
    @include('pages.facilitator.defense-bulk-schedule-modal')

</div>
@endsection
