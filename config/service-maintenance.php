<?php

return [
    'services' => [
        'student-registration' => [
            'label' => 'Student Registration',
            'description' => 'New student account registration and submissions.',
            'icon' => 'ph-user-plus',
            'routes' => ['register', 'register.store'],
            'tabs' => [],
        ],
        'research-classes' => [
            'label' => 'Research Classes & Groups',
            'description' => 'Class membership, research groups, advisers, and progress management.',
            'icon' => 'ph-users-three',
            'routes' => ['student.classes.*', 'student.progress.*', 'facilitator.classes.*', 'facilitator.progress.*', 'adviser.classes.*', 'adviser.progress.*'],
            'tabs' => ['classes', 'research', 'researchers', 'progress', 'monitoring'],
        ],
        'documents-revisions' => [
            'label' => 'Documents & Revisions',
            'description' => 'Manuscript uploads, reviews, revision cycles, and document access.',
            'icon' => 'ph-file-text',
            'routes' => ['documents.*', 'student.documents.*', 'student.revisions.*', 'adviser.documents.*', 'adviser.revisions.*', 'panelist.documents.*', 'facilitator.title-proposals.*'],
            'tabs' => ['proposal', 'docreview', 'revisions', 'repository', 'endorsement'],
        ],
        'consultations' => [
            'label' => 'Consultations',
            'description' => 'Consultation requests, scheduling, responses, and records.',
            'icon' => 'ph-chats-circle',
            'routes' => ['student.consultations.*', 'adviser.consultations.*'],
            'tabs' => ['consultation'],
        ],
        'official-forms' => [
            'label' => 'Official Forms',
            'description' => 'Form creation, signing, approval, and actor assignment.',
            'icon' => 'ph-file-pdf',
            'routes' => ['official-forms.workspace.*', 'official-form-class-actors.*', 'student.official-forms.*', 'facilitator.classes.form-actors.*'],
            'tabs' => ['forms'],
        ],
        'defenses-evaluations' => [
            'label' => 'Defenses & Evaluations',
            'description' => 'Defense scheduling, panel assignments, evaluations, and result release.',
            'icon' => 'ph-presentation-chart',
            'routes' => ['facilitator.defenses.*', 'facilitator.title-presentations.*', 'facilitator.evaluation-rounds.*', 'panelist.evaluations.*'],
            'tabs' => ['defense', 'defenses', 'evaluations', 'schedule'],
        ],
        'reports' => [
            'label' => 'Reports & Analytics',
            'description' => 'Operational reports, analytics views, and exports.',
            'icon' => 'ph-chart-line-up',
            'routes' => ['admin.reports.*', 'facilitator.reports.*', 'dean.reports.*'],
            'tabs' => ['reports', 'statistics'],
        ],
    ],
];
