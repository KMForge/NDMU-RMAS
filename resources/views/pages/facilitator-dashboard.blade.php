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
    ],

    selectedMonitoringId: 1,
    showMonitoringEditModal: false,
    monitoringProjects: [
        {
            id: 1,
            code: 'RES-2026-001',
            title: 'Machine Learning Applications in Agricultural Pest Detection',
            students: 'Maria Santos, Juan Dela Cruz',
            adviser: 'Dr. Roberto Garcia',
            milestones: [
                { title: 'Research Title Presentation', status: 'Completed', date: 'Feb 15, 2026', details: 'All requirements met and approved' },
                { title: 'Proposal Approval', status: 'Completed', date: 'Mar 10, 2026', details: 'All requirements met and approved' },
                { title: 'Adviser Endorsement', status: 'Completed', date: 'Mar 20, 2026', details: 'All requirements met and approved' },
                { title: 'Instrument Validation', status: 'Completed', date: 'Apr 5, 2026', details: 'All requirements met and approved' },
                { title: 'Data Gathering', status: 'In Progress', date: 'In Progress', details: 'Currently working on this milestone' },
                { title: 'Proposal Defense', status: 'Completed', date: 'May 10, 2026', details: 'All requirements met and approved' },
                { title: 'Revisions', status: 'In Progress', date: 'May 18, 2026', details: 'Currently working on this milestone' },
                { title: 'Final Defense', status: 'Pending', date: 'Jul 15, 2026', details: 'Not Started' },
                { title: 'Technical Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Language Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Final Manuscript Approval', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Certificate of Authentic Authorship', status: 'Pending', date: 'Not Started', details: '' }
            ]
        },
        {
            id: 2,
            code: 'RES-2026-002',
            title: 'IoT-Based Smart Classroom Management',
            students: 'Anna Reyes, Carlos Mendoza',
            adviser: 'Dr. Patricia Cruz',
            milestones: [
                { title: 'Research Title Presentation', status: 'Completed', date: 'Jan 10, 2026', details: 'All requirements met and approved' },
                { title: 'Proposal Approval', status: 'Completed', date: 'Jan 28, 2026', details: 'All requirements met and approved' },
                { title: 'Adviser Endorsement', status: 'Completed', date: 'Feb 05, 2026', details: 'All requirements met and approved' },
                { title: 'Instrument Validation', status: 'In Progress', date: 'In Progress', details: 'Currently working on this milestone' },
                { title: 'Data Gathering', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Proposal Defense', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Revisions', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Final Defense', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Technical Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Language Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Final Manuscript Approval', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Certificate of Authentic Authorship', status: 'Pending', date: 'Not Started', details: '' }
            ]
        },
        {
            id: 3,
            code: 'RES-2026-003',
            title: 'Community Health Information System',
            students: 'Luis Fernandez, Sarah Gonzales',
            adviser: 'Dr. Michael Tan',
            milestones: [
                { title: 'Research Title Presentation', status: 'Completed', date: 'Dec 12, 2025', details: 'All requirements met and approved' },
                { title: 'Proposal Approval', status: 'Completed', date: 'Dec 22, 2025', details: 'All requirements met and approved' },
                { title: 'Adviser Endorsement', status: 'Completed', date: 'Jan 08, 2026', details: 'All requirements met and approved' },
                { title: 'Instrument Validation', status: 'Completed', date: 'Jan 20, 2026', details: 'All requirements met and approved' },
                { title: 'Data Gathering', status: 'Completed', date: 'Feb 15, 2026', details: 'All requirements met and approved' },
                { title: 'Proposal Defense', status: 'Completed', date: 'Feb 28, 2026', details: 'All requirements met and approved' },
                { title: 'Revisions', status: 'Completed', date: 'Mar 15, 2026', details: 'All requirements met and approved' },
                { title: 'Final Defense', status: 'In Progress', date: 'In Progress', details: 'Currently working on this milestone' },
                { title: 'Technical Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Language Editing', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Final Manuscript Approval', status: 'Pending', date: 'Not Started', details: '' },
                { title: 'Certificate of Authentic Authorship', status: 'Pending', date: 'Not Started', details: '' }
            ]
        }
    ],
    get activeMonitoringProject() {
        return this.monitoringProjects.find(p => p.id === this.selectedMonitoringId) || this.monitoringProjects[0];
    },
    get activeMonitoringStats() {
        let project = this.activeMonitoringProject;
        let completed = project.milestones.filter(m => m.status === 'Completed').length;
        let inProgress = project.milestones.filter(m => m.status === 'In Progress').length;
        let pending = project.milestones.filter(m => m.status === 'Pending').length;
        let total = project.milestones.length;
        let percent = Math.round((completed / total) * 100);
        return { completed, inProgress, pending, total, percent };
    },

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
        
        // Switch back to "All Users" tab
        this.userManagementSubTab = 'all';
    },

    selectedProposalId: 1,
    showProposalDetailModal: false,
    proposalList: [
        { 
            id: 1, 
            proposal_id: 'PROP-2026-001', 
            title: 'Machine Learning Applications in Agricultural Pest Detection', 
            submitted: 'March 5, 2026', 
            status: 'Approved', 
            reviewer: 'Dr. Maria Santos', 
            approval_date: 'March 10, 2026',
            department: 'College of Information Technology',
            abstract: 'This research proposes an AI-based system that utilizes deep learning methods, specifically convolutional neural networks (CNNs), to identify and classify crop pests in local agricultural sectors. By detecting pests at an early stage, farmers can perform targeted interventions, reducing chemical pesticide use.',
            objectives: '1. To design and implement a mobile crop scanner using lightweight neural networks.\n2. To classify the top 5 agricultural pests found in South Cotabato crops.\n3. To offer pest density maps and action recommendations.'
        },
        { 
            id: 2, 
            proposal_id: 'PROP-2026-002', 
            title: 'IoT-Based Smart Classroom Management', 
            submitted: 'May 12, 2026', 
            status: 'Pending', 
            reviewer: 'Pending Assignment', 
            approval_date: 'N/A',
            department: 'College of Engineering',
            abstract: 'This project focuses on the automation of academic spaces utilizing smart sensors. By tracking temperature, humidity, lighting, and occupancy, the classroom management system dynamically adjusts climate controls and scheduling, minimizing energy consumption and maximizing student comfort.',
            objectives: '1. To design real-time occupancy trackers.\n2. To interface HVAC controls with custom scheduling microcontrollers.\n3. To reduce electrical energy consumption by 20% in testing halls.'
        },
        { 
            id: 3, 
            proposal_id: 'PROP-2026-003', 
            title: 'Community Health Information System', 
            submitted: 'May 15, 2026', 
            status: 'Revisions', 
            reviewer: 'Dr. Michael Tan', 
            approval_date: 'N/A',
            department: 'College of Information Technology',
            abstract: 'A decentralized information portal built for rural health centers. The system secures patient records offline and synchronizes securely with central municipal databases whenever connectivity becomes available, improving resource allocation during epidemics.',
            objectives: '1. To construct an offline-first storage engine.\n2. To ensure strict role-based access controls for health data privacy.\n3. To deliver dashboard tools for local health facilitators.'
        }
    ],

    get activeProposal() {
        return this.proposalList.find(p => p.id === this.selectedProposalId) || this.proposalList[0];
    },
    get approvedProposalsCount() {
        return this.proposalList.filter(p => p.status === 'Approved').length;
    },
    get pendingProposalsCount() {
        return this.proposalList.filter(p => p.status === 'Pending').length;
    },
    get revisionsProposalsCount() {
        return this.proposalList.filter(p => p.status === 'Revisions').length;
    },
    get totalProposalsCount() {
        return this.proposalList.length;
    },

    approveProposal(id) {
        let prop = this.proposalList.find(p => p.id === id);
        if (prop) {
            prop.status = 'Approved';
            prop.reviewer = 'Dr. Rosario Dela Paz';
            prop.approval_date = new Date().toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
            alert(`Proposal approved successfully: ${prop.title}`);
        }
    },

    requestRevisionsProposal(id) {
        let prop = this.proposalList.find(p => p.id === id);
        if (prop) {
            prop.status = 'Revisions';
            prop.reviewer = 'Dr. Rosario Dela Paz';
            prop.approval_date = 'N/A';
            alert(`Requested revisions for proposal: ${prop.title}`);
        }
    }
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

            <!-- TAB: Research Monitoring -->
            <div x-show="activeTab === 'monitoring'" x-cloak class="space-y-8 animate-fade-in">
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
            </div>

            <!-- TAB: Adviser Assignments (User Management) -->
            <div x-show="activeTab === 'advisers'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">User Management</span>
                    </div>
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">User Management</h1>
                            <p class="text-xs text-gray-455 mt-1">Manage accounts, approve registrations, and create staff users</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Widgets Cards Row (4 Columns matching screenshots) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total Users -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-users"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block leading-none animate-fade-in" x-text="totalUsersCount">26</span>
                            <span class="text-[10px] text-gray-450 font-bold uppercase tracking-wider block mt-1">Total Users</span>
                        </div>
                    </div>

                    <!-- Pending Approval -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl flex-shrink-0" :class="pendingUsersCount > 0 ? 'animate-pulse' : ''">
                            <i class="ph ph-clock"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block leading-none animate-fade-in" x-text="pendingUsersCount">5</span>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Pending Approval</span>
                        </div>
                    </div>

                    <!-- Active Accounts -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-shield-check"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block leading-none animate-fade-in" x-text="activeUsersCount">21</span>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Active Accounts</span>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                        <div>
                            <span class="text-2xl font-bold text-gray-800 block leading-none animate-fade-in" x-text="rejectedUsersCount">0</span>
                            <span class="text-[10px] text-gray-455 font-bold uppercase tracking-wider block mt-1">Rejected</span>
                        </div>
                    </div>
                </div>

                <!-- Main Card with Tabbed Navigation -->
                <div class="bg-white rounded-[2.5rem] border border-gray-100/85 shadow-xl shadow-slate-200/30 p-6 md:p-8 space-y-6">
                    <!-- Inner Tabs (All Users, Pending Students, Create User) -->
                    <div class="flex border-b border-gray-100 pb-px overflow-x-auto gap-8">
                        <button 
                            type="button"
                            @click="userManagementSubTab = 'all'"
                            :class="userManagementSubTab === 'all' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-semibold'"
                            class="pb-4 border-b-2 text-xs flex items-center gap-2 cursor-pointer transition-all duration-200"
                        >
                            <span>All Users</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold" :class="userManagementSubTab === 'all' ? 'bg-[#0e5c3a] text-white' : 'bg-gray-100 text-gray-500'" x-text="totalUsersCount">26</span>
                        </button>
                        <button 
                            type="button"
                            @click="userManagementSubTab = 'pending'"
                            :class="userManagementSubTab === 'pending' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-semibold'"
                            class="pb-4 border-b-2 text-xs flex items-center gap-2 cursor-pointer transition-all duration-200"
                        >
                            <span>Pending Students</span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold" :class="userManagementSubTab === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-500'" x-text="pendingUsersCount">5</span>
                        </button>
                        <button 
                            type="button"
                            @click="userManagementSubTab = 'create'"
                            :class="userManagementSubTab === 'create' ? 'border-[#0e5c3a] text-[#0e5c3a] font-bold' : 'border-transparent text-gray-400 hover:text-gray-600 font-semibold'"
                            class="pb-4 border-b-2 text-xs flex items-center gap-2 cursor-pointer transition-all duration-200"
                        >
                            <i class="ph ph-user-plus text-sm"></i>
                            <span>Create User</span>
                        </button>
                    </div>

                    <!-- Inner Tab content: Tables vs Form -->
                    <div class="space-y-6">
                        
                        <!-- Filter Bar (Only visible for 'all' and 'pending' sub-tabs) -->
                        <div x-show="userManagementSubTab !== 'create'" class="flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
                            <!-- Search box -->
                            <div class="relative flex-grow max-w-md">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 pointer-events-none">
                                    <i class="ph ph-magnifying-glass text-sm"></i>
                                </span>
                                <input 
                                    type="text" 
                                    x-model="userSearchQuery"
                                    placeholder="Search by name or email..."
                                    class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-150 rounded-full text-xs text-gray-800 placeholder-gray-400 outline-none focus:bg-white focus:border-gray-300 transition-all"
                                >
                            </div>

                            <!-- Role Filter dropdown -->
                            <div class="flex items-center gap-3 flex-wrap md:flex-nowrap">
                                <select 
                                    x-model="userRoleFilter"
                                    class="bg-white border border-gray-250 text-gray-700 text-xs px-3.5 py-2.5 rounded-xl outline-none focus:border-[#0e5c3a] cursor-pointer"
                                >
                                    <option value="All">All Roles</option>
                                    <option value="system-administrator">Administrator</option>
                                    <option value="college-dean">College Dean</option>
                                    <option value="research-facilitator">Research Facilitator</option>
                                    <option value="research-adviser">Research Adviser</option>
                                    <option value="panelist">Panelist</option>
                                    <option value="student-researcher">Student Researcher</option>
                                </select>

                                <!-- Refresh/Reset button -->
                                <button 
                                    type="button"
                                    @click="userSearchQuery = ''; userRoleFilter = 'All'; alert('Refreshed user list.')"
                                    class="px-4 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl flex items-center gap-2 shadow-2xs hover:shadow-xs transition-all cursor-pointer"
                                >
                                    <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                                    <span>Refresh</span>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-tab view: All Users & Pending Students Table -->
                        <div x-show="userManagementSubTab !== 'create'" class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 font-extrabold uppercase tracking-wider text-[9px]">
                                        <th class="py-4 px-4">Name</th>
                                        <th class="py-4 px-4">Email</th>
                                        <th class="py-4 px-4">Role</th>
                                        <th class="py-4 px-4">Status</th>
                                        <th class="py-4 px-4">Department</th>
                                        <th class="py-4 px-4">Created</th>
                                        <th class="py-4 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="user in filteredUserList" :key="user.email">
                                        <tr class="border-b border-gray-50/50 hover:bg-gray-50/30 transition-colors animate-fade-in">
                                            <!-- Name avatar cell -->
                                            <td class="py-4 px-4 font-bold text-gray-800 flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                                     :class="{
                                                         'bg-emerald-600': user.role === 'system-administrator' || user.role === 'college-dean',
                                                         'bg-[#0e5c3a]': user.role === 'research-facilitator' || user.role === 'research-adviser',
                                                         'bg-teal-600': user.role === 'panelist',
                                                         'bg-blue-600': user.role === 'student-researcher' && user.status === 'Active',
                                                         'bg-amber-500': user.status === 'Pending'
                                                     }"
                                                     x-text="user.name.charAt(0)"
                                                ></div>
                                                <div class="flex flex-col">
                                                    <span class="text-gray-800" x-text="user.name">User Name</span>
                                                    <!-- Temp password badge -->
                                                    <template x-if="user.hasTempPassword">
                                                        <span class="mt-0.5 px-1.5 py-0.5 rounded bg-amber-50 border border-amber-100 text-amber-700 text-[8px] font-extrabold tracking-wide uppercase w-fit leading-none">Temp password</span>
                                                    </template>
                                                </div>
                                            </td>
                                            
                                            <!-- Email -->
                                            <td class="py-4 px-4 text-gray-550 font-medium" x-text="user.email">user@email.com</td>
                                            
                                            <!-- Role badge -->
                                            <td class="py-4 px-4">
                                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-extrabold tracking-wide uppercase border shadow-3xs"
                                                      :class="{
                                                          'bg-slate-50 border-slate-150 text-slate-700': user.role === 'system-administrator',
                                                          'bg-blue-50 border-blue-150 text-blue-700': user.role === 'college-dean',
                                                          'bg-teal-50 border-teal-150 text-teal-700': user.role === 'research-facilitator',
                                                          'bg-emerald-50 border-emerald-150 text-emerald-700': user.role === 'research-adviser',
                                                          'bg-purple-50 border-purple-150 text-purple-700': user.role === 'panelist',
                                                          'bg-green-50 border-green-150 text-green-700': user.role === 'student-researcher'
                                                      }"
                                                      x-text="user.role === 'system-administrator' ? 'Administrator' : (user.role === 'college-dean' ? 'College Dean' : (user.role === 'research-facilitator' ? 'Research Facilitator' : (user.role === 'research-adviser' ? 'Research Adviser' : (user.role === 'panelist' ? 'Panelist' : 'Student Researcher'))))"
                                                >
                                                    Role
                                                </span>
                                            </td>
                                            
                                            <!-- Status badge -->
                                            <td class="py-4 px-4">
                                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold tracking-wide uppercase"
                                                      :class="{
                                                          'bg-emerald-50 border border-emerald-100 text-emerald-700': user.status === 'Active',
                                                          'bg-amber-50 border border-amber-100 text-amber-700': user.status === 'Pending',
                                                          'bg-red-50 border border-red-100 text-red-700': user.status === 'Rejected'
                                                      }"
                                                      x-text="user.status"
                                                >
                                                    Active
                                                </span>
                                            </td>
                                            
                                            <!-- Department -->
                                            <td class="py-4 px-4 text-gray-500 font-semibold" x-text="user.department">Department Name</td>
                                            
                                            <!-- Created -->
                                            <td class="py-4 px-4 text-gray-400 font-bold" x-text="user.created">2024-01-01</td>
                                            
                                            <!-- Actions -->
                                            <td class="py-4 px-4 text-right">
                                                <!-- If System Account -->
                                                <template x-if="user.isSystem">
                                                    <span class="text-gray-400 italic text-[11px] font-semibold">System</span>
                                                </template>
                                                <!-- If non-system account -->
                                                <template x-if="!user.isSystem">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <!-- If student pending approval, show Approve/Reject -->
                                                        <template x-if="user.status === 'Pending'">
                                                            <div class="flex items-center gap-2">
                                                                <button 
                                                                    type="button"
                                                                    @click="approveUser(user.email)"
                                                                    class="px-2.5 py-1.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-[10px] font-bold rounded-lg shadow-sm cursor-pointer transition-colors"
                                                                >
                                                                    Approve
                                                                </button>
                                                                <button 
                                                                    type="button"
                                                                    @click="rejectUser(user.email)"
                                                                    class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold rounded-lg shadow-sm cursor-pointer transition-colors"
                                                                >
                                                                    Reject
                                                                </button>
                                                            </div>
                                                        </template>
                                                        
                                                        <!-- Always show delete trash button for non-system accounts -->
                                                        <button 
                                                            type="button"
                                                            @click="deleteUser(user.email)"
                                                            class="p-1.5 hover:bg-red-50 text-red-500 hover:text-red-700 rounded-lg cursor-pointer transition-colors text-sm"
                                                            title="Delete User"
                                                        >
                                                            <i class="ph ph-trash"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                    
                                    <!-- No users found row -->
                                    <template x-if="filteredUserList.length === 0">
                                        <tr>
                                            <td colspan="7" class="py-12 text-center text-gray-400 font-bold text-xs">
                                                No users found matching the filter criteria.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Sub-tab view: Create User Form -->
                        <div x-show="userManagementSubTab === 'create'" class="max-w-xl mx-auto py-4 animate-fade-in">
                            <form @submit.prevent="submitCreateUser()" class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Name -->
                                    <div class="space-y-2">
                                        <label for="create-name" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Full Name</label>
                                        <input 
                                            id="create-name"
                                            type="text" 
                                            x-model="newUserForm.name"
                                            placeholder="Enter full name"
                                            class="w-full px-4 py-3 bg-white border border-gray-250 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 outline-none transition-all"
                                            required
                                        >
                                    </div>

                                    <!-- Email -->
                                    <div class="space-y-2">
                                        <label for="create-email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                                        <input 
                                            id="create-email"
                                            type="email" 
                                            x-model="newUserForm.email"
                                            placeholder="user@ndmu.edu.ph"
                                            class="w-full px-4 py-3 bg-white border border-gray-250 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 outline-none transition-all"
                                            required
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Role Select -->
                                    <div class="space-y-2">
                                        <label for="create-role" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Assign Role</label>
                                        <select 
                                            id="create-role"
                                            x-model="newUserForm.role"
                                            class="w-full px-4 py-3 bg-white border border-gray-250 rounded-xl text-xs text-gray-800 focus:border-[#0e5c3a] outline-none cursor-pointer"
                                        >
                                            <option value="system-administrator">Administrator</option>
                                            <option value="college-dean">College Dean</option>
                                            <option value="research-facilitator">Research Facilitator</option>
                                            <option value="research-adviser">Research Adviser</option>
                                            <option value="panelist">Panelist</option>
                                            <option value="student-researcher">Student Researcher</option>
                                        </select>
                                    </div>

                                    <!-- Department -->
                                    <div class="space-y-2">
                                        <label for="create-dept" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Department / College</label>
                                        <select 
                                            id="create-dept"
                                            x-model="newUserForm.department"
                                            class="w-full px-4 py-3 bg-white border border-gray-250 rounded-xl text-xs text-gray-800 focus:border-[#0e5c3a] outline-none cursor-pointer"
                                        >
                                            <option value="College of Information Technology">College of Information Technology</option>
                                            <option value="College of Engineering">College of Engineering</option>
                                            <option value="Office of the College Dean">Office of the College Dean</option>
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="College of Business Administration">College of Business Administration</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Password options -->
                                <div class="flex items-center gap-3 bg-gray-50 border border-gray-150 rounded-2xl p-4">
                                    <input 
                                        id="create-temp-password"
                                        type="checkbox" 
                                        x-model="newUserForm.tempPassword"
                                        class="rounded border-gray-350 text-[#0e5c3a] focus:ring-[#0e5c3a] w-4 h-4 cursor-pointer"
                                    >
                                    <label for="create-temp-password" class="text-xs text-gray-600 font-bold cursor-pointer select-none">
                                        Generate temporary password and notify user via email
                                    </label>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end gap-3 pt-2">
                                    <button 
                                        type="button" 
                                        @click="userManagementSubTab = 'all'" 
                                        class="px-5 py-3 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl cursor-pointer transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button 
                                        type="submit" 
                                        class="px-6 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md shadow-[#0e5c3a]/10 hover:shadow-lg cursor-pointer transition-all duration-200 flex items-center gap-2"
                                    >
                                        <i class="ph ph-user-plus text-base font-bold"></i>
                                        <span>Create Account</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Research Screening (Proposal Management) -->
            <div x-show="activeTab === 'screening'" x-cloak class="space-y-8 animate-fade-in">
                <!-- Breadcrumbs & Header -->
                <div class="flex flex-col gap-2">
                    <div class="flex items-center gap-1 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                        <span>Dashboard</span>
                        <span>/</span>
                        <span class="text-[#0e5c3a]">Research Screening</span>
                    </div>
                    
                    <div class="flex justify-between items-center flex-wrap gap-4">
                        <div>
                            <h1 class="text-2xl font-bold font-heading text-gray-800">Proposal Management</h1>
                            <p class="text-xs text-gray-455 mt-1">Manage research proposals and approvals</p>
                        </div>
                        
                        <!-- Proposal Selection Dropdown -->
                        <div class="flex items-center gap-3">
                            <label for="proposal-select" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Select Proposal:</label>
                            <select 
                                id="proposal-select"
                                x-model.number="selectedProposalId" 
                                class="bg-white border border-gray-250 text-gray-700 text-xs px-3.5 py-2 rounded-xl outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all cursor-pointer"
                            >
                                <template x-for="p in proposalList" :key="p.id">
                                    <option :value="p.id" x-text="`[${p.proposal_id}] ${p.title}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Stats Widgets Cards Row (4 Columns matching screenshots) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Approved -->
                    <div class="bg-white rounded-3xl p-5 border border-emerald-100/50 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-xs text-gray-455 font-bold uppercase tracking-wider block">Approved</span>
                            <span class="text-2xl font-bold text-gray-850 mt-2 block leading-none" x-text="approvedProposalsCount">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-check-circle"></i>
                        </span>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white rounded-3xl p-5 border border-amber-100/50 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-xs text-gray-455 font-bold uppercase tracking-wider block">Pending</span>
                            <span class="text-2xl font-bold text-gray-850 mt-2 block leading-none" x-text="pendingProposalsCount">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl flex-shrink-0" :class="pendingProposalsCount > 0 ? 'animate-pulse' : ''">
                            <i class="ph ph-clock"></i>
                        </span>
                    </div>

                    <!-- Revisions -->
                    <div class="bg-white rounded-3xl p-5 border border-red-100/50 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-xs text-gray-455 font-bold uppercase tracking-wider block">Revisions</span>
                            <span class="text-2xl font-bold text-gray-850 mt-2 block leading-none" x-text="revisionsProposalsCount">0</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-x-circle"></i>
                        </span>
                    </div>

                    <!-- Total Proposals -->
                    <div class="bg-white rounded-3xl p-5 border border-blue-100/50 shadow-sm flex items-center justify-between animate-fade-in">
                        <div>
                            <span class="text-xs text-gray-455 font-bold uppercase tracking-wider block">Total Proposals</span>
                            <span class="text-2xl font-bold text-gray-850 mt-2 block leading-none" x-text="totalProposalsCount">1</span>
                        </div>
                        <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="ph ph-file-text"></i>
                        </span>
                    </div>
                </div>

                <!-- Research Proposal Container Card -->
                <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl shadow-slate-200/30 p-6 md:p-8 space-y-6">
                    <h3 class="font-bold text-gray-850 text-base">Research Proposal</h3>
                    
                    <!-- Proposal Display Card -->
                    <div class="border border-gray-150 rounded-3xl p-6 md:p-8 space-y-6 transition-all duration-300 animate-fade-in"
                         :class="{
                             'bg-[#f2fcf7]/30 border-emerald-100': activeProposal.status === 'Approved',
                             'bg-[#fffbf0]/40 border-amber-100 ring-4 ring-amber-500/5': activeProposal.status === 'Pending',
                             'bg-red-50/5 border-red-100': activeProposal.status === 'Revisions'
                         }"
                    >
                        <div class="flex justify-between items-start gap-4 flex-wrap">
                            <div class="space-y-2">
                                <h2 class="text-lg md:text-xl font-bold text-gray-800 leading-snug" x-text="activeProposal.title">Machine Learning Applications in Agricultural Pest Detection</h2>
                                <div class="flex flex-wrap items-center gap-4 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                    <span>Proposal ID: <span class="text-gray-650" x-text="activeProposal.proposal_id">PROP-2026-001</span></span>
                                    <span class="hidden md:inline">•</span>
                                    <span>Submitted: <span class="text-gray-650" x-text="activeProposal.submitted">March 5, 2026</span></span>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <span class="px-4 py-1.5 rounded-xl text-[10px] font-extrabold tracking-wide uppercase border shadow-2xs transition-all duration-300"
                                  :class="{
                                      'bg-emerald-600 border-emerald-700 text-white': activeProposal.status === 'Approved',
                                      'bg-amber-500 border-amber-600 text-white': activeProposal.status === 'Pending',
                                      'bg-red-650 border-red-750 text-white': activeProposal.status === 'Revisions'
                                  }"
                                  x-text="activeProposal.status"
                            >
                                Approved
                            </span>
                        </div>

                        <hr class="border-gray-100/80">

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                            <div>
                                <span class="text-gray-400 font-extrabold uppercase tracking-wider block text-[9px]">Reviewed by</span>
                                <span class="text-gray-800 font-bold block mt-1.5" x-text="activeProposal.reviewer">Dr. Maria Santos</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-extrabold uppercase tracking-wider block text-[9px]">Approval Date</span>
                                <span class="text-gray-800 font-bold block mt-1.5" x-text="activeProposal.approval_date">March 10, 2026</span>
                            </div>
                        </div>

                        <!-- Pending Actions Bar -->
                        <template x-if="activeProposal.status === 'Pending'">
                            <div class="mt-6 pt-6 border-t border-amber-100/50 flex flex-wrap gap-3 items-center">
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mr-2 block">Action Required:</span>
                                <button 
                                    type="button" 
                                    @click="approveProposal(activeProposal.id)"
                                    class="px-4 py-2.5 bg-emerald-650 hover:bg-emerald-755 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-650/10 cursor-pointer transition-colors"
                                >
                                    Approve Proposal
                                </button>
                                <button 
                                    type="button" 
                                    @click="requestRevisionsProposal(activeProposal.id)"
                                    class="px-4 py-2.5 bg-white border border-gray-250 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl cursor-pointer transition-colors"
                                >
                                    Request Revisions
                                </button>
                            </div>
                        </template>

                        <!-- Revisions Notice -->
                        <template x-if="activeProposal.status === 'Revisions'">
                            <div class="mt-6 pt-6 border-t border-red-100/50 flex items-center gap-2">
                                <span class="text-[10px] font-bold text-red-700 block">
                                    ⚠ This proposal is currently in revision. Awaiting student resubmission.
                                </span>
                            </div>
                        </template>

                        <!-- Bottom Card Buttons -->
                        <div class="pt-6 border-t border-gray-100/80 flex gap-4 flex-wrap">
                            <button 
                                type="button"
                                @click="showProposalDetailModal = true"
                                class="px-5 py-3 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-xs font-bold rounded-xl shadow-md shadow-[#0e5c3a]/10 hover:shadow-lg cursor-pointer transition-all duration-200"
                            >
                                View Proposal
                            </button>
                            <button 
                                type="button"
                                @click="alert(`Downloading PDF for proposal ID: ${activeProposal.proposal_id}...`)"
                                class="px-5 py-3 bg-white border border-gray-250 hover:bg-gray-50 text-gray-700 text-xs font-bold rounded-xl shadow-2xs hover:shadow-xs cursor-pointer transition-all duration-200"
                            >
                                Download PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Settings -->
            <div x-show="activeTab === 'settings'" x-cloak class="space-y-8 animate-fade-in">
                @include('partials.settings', [
                    'avatarInitials' => 'D',
                    'userName' => 'Dr. Rosario Dela Paz',
                    'emailAddress' => 'r.dela-paz@ndmu.edu.ph',
                    'userRole' => 'Research Facilitator',
                    'userRoleBadge' => 'RESEARCH FACILITATOR',
                    'department' => 'College of Information Technology',
                    'userId' => 'FAC-2015-0001',
                    'portalType' => 'Faculty Portal',
                    'accessLevel' => 'Faculty & Guidance Access'
                ])
            </div>

            <!-- Placeholder Fallback View for Other Tabs -->
            <div x-show="!['notifications', 'dashboard', 'settings', 'monitoring', 'advisers', 'screening'].includes(activeTab)" x-cloak class="min-h-[50vh] flex flex-col items-center justify-center text-center space-y-4">
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

    <!-- Update Progress Modal Mockup -->
    <div x-show="showMonitoringEditModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showMonitoringEditModal = false" class="bg-white rounded-3xl w-full max-w-2xl p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
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

    <!-- Proposal Details Modal Mockup -->
    <div x-show="showProposalDetailModal" x-transition x-cloak class="fixed inset-0 bg-black/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
        <div @click.away="showProposalDetailModal = false" class="bg-white rounded-3xl w-full max-w-2xl p-6 shadow-xl space-y-4 max-h-[85vh] overflow-y-auto">
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

</div>
@endsection
