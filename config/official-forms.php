<?php

return [
    'phases' => [
        'phase-1' => 'Phase 1: Title Approval',
        'phase-2' => 'Phase 2: Adviser and Panelist Assignment',
        'phase-3' => 'Phase 3: Consultation and Endorsement',
        'phase-4' => 'Phase 4: Proposal / Pre-Final / Final Defense',
        'phase-5' => 'Phase 5: Revisions',
        'phase-6' => 'Phase 6: Instrument Validation and Data Gathering',
        'phase-7' => 'Phase 7: Editing, Reproduction, and Completion',
    ],

    'student' => [
        'RES-026' => [
            'title' => 'Research Title Approval',
            'phase' => 'phase-1',
            'file' => 'res-026.pdf',
            'purpose' => 'Student submits their research title for approval.',
        ],
        'RES-030' => [
            'title' => 'Request for Change of Adviser/Panelist/Language Editor',
            'phase' => 'phase-2',
            'file' => 'res-030.pdf',
            'purpose' => 'Student initiates the request for personnel change.',
        ],
        'RES-031' => [
            'title' => 'Consultation Record with Research Adviser',
            'phase' => 'phase-3',
            'file' => 'res-031.pdf',
            'purpose' => 'Student fills out their consultation records with the research adviser.',
        ],
        'RES-032' => [
            'title' => 'Consultation Sheet (With Other Consultants)',
            'phase' => 'phase-3',
            'file' => 'res-032.pdf',
            'purpose' => 'Student records consultations with other consultants.',
        ],
        'RES-034' => [
            'title' => 'Research Proposal/Pre-Final/Final Oral Defense Pre-Conference',
            'phase' => 'phase-4',
            'file' => 'res-034.pdf',
            'purpose' => 'Student attends their own proposal, pre-final, or final oral defense pre-conference.',
        ],
        'RES-039' => [
            'title' => 'Research Revision Chart',
            'phase' => 'phase-5',
            'file' => 'res-039.pdf',
            'purpose' => 'Student tracks their required research revisions.',
        ],
        'RES-042' => [
            'title' => 'Request for Research Instrument Validation',
            'phase' => 'phase-6',
            'file' => 'res-042.pdf',
            'purpose' => 'Student requests validation of their research instrument.',
        ],
        'RES-048' => [
            'title' => 'Self and Peer Evaluation',
            'phase' => 'phase-7',
            'file' => 'res-048.pdf',
            'purpose' => 'Student completes their self and peer evaluation.',
        ],
        'RES-049' => [
            'title' => 'Certificate of Authentic Authorship',
            'phase' => 'phase-7',
            'file' => 'res-049.pdf',
            'purpose' => 'Student signs their certificate of authentic authorship.',
        ],
    ],

    'adviser' => [
        'RES-026' => [
            'title' => 'Research Title Approval',
            'phase' => 'phase-1',
            'shared_with' => 'student',
            'purpose' => 'Adviser signs the title approval for their student.',
        ],
        'RES-027' => [
            'title' => 'Invitation to Research Adviser',
            'phase' => 'phase-2',
            'purpose' => 'Adviser receives the formal invitation to serve.',
        ],
        'RES-038' => [
            'title' => 'Endorsement of Student Researchers to Research Adviser',
            'phase' => 'phase-2',
            'purpose' => 'Adviser receives the endorsement of their students.',
        ],
        'RES-031' => [
            'title' => 'Consultation Record with Research Adviser',
            'phase' => 'phase-3',
            'shared_with' => 'student',
            'purpose' => 'Adviser fills and signs the consultation record.',
        ],
        'RES-032' => [
            'title' => 'Consultation Sheet (With Other Consultants)',
            'phase' => 'phase-3',
            'shared_with' => 'student',
            'purpose' => 'Adviser co-signs consultations with other consultants.',
        ],
        'RES-033' => [
            'title' => 'Endorsement for Research Proposal/Pre-Final/Final Oral Defense',
            'phase' => 'phase-3',
            'purpose' => 'Adviser issues the endorsement for defense.',
        ],
        'RES-034' => [
            'title' => 'Research Proposal/Pre-Final/Final Oral Defense Pre-Conference',
            'phase' => 'phase-4',
            'shared_with' => 'student',
            'purpose' => 'Adviser chairs the pre-conference session.',
        ],
        'RES-035' => [
            'title' => 'Defense Proceedings',
            'phase' => 'phase-4',
            'purpose' => 'Adviser documents and signs the defense proceedings.',
        ],
        'RES-040' => [
            'title' => 'Endorsement to Research Instructor',
            'phase' => 'phase-5',
            'purpose' => 'Adviser issues endorsement to the research instructor.',
        ],
        'RES-042' => [
            'title' => 'Request for Research Instrument Validation',
            'phase' => 'phase-6',
            'shared_with' => 'student',
            'purpose' => 'Adviser co-signs the instrument validation request.',
        ],
        'RES-044' => [
            'title' => 'Endorsement of Student Researcher for Data Gathering',
            'phase' => 'phase-6',
            'purpose' => 'Adviser issues endorsement for data gathering.',
        ],
        'RES-048' => [
            'title' => 'Self and Peer Evaluation',
            'phase' => 'phase-7',
            'shared_with' => 'student',
            'purpose' => 'Adviser signs off on the peer evaluation.',
        ],
        'RES-049' => [
            'title' => 'Certificate of Authentic Authorship',
            'phase' => 'phase-7',
            'shared_with' => 'student',
            'purpose' => 'Adviser co-signs the authorship certificate.',
        ],
    ],

    'panelist' => [
        'RES-028' => [
            'title' => 'Invitation to Research Examination Panelist',
            'phase' => 'phase-2',
            'purpose' => 'Panelist receives the formal invitation to serve.',
        ],
        'RES-034' => [
            'title' => 'Research Proposal/Pre-Final/Final Oral Defense Pre-Conference',
            'phase' => 'phase-4',
            'shared_with' => 'student',
            'purpose' => 'Panelist attends the pre-conference as a panel member.',
        ],
        'RES-035' => [
            'title' => 'Defense Proceedings',
            'phase' => 'phase-4',
            'shared_with' => 'adviser',
            'purpose' => 'Panelist participates in and signs the defense proceedings.',
        ],
        'RES-036' => [
            'title' => 'Evaluation of Research Defense',
            'phase' => 'phase-4',
            'purpose' => 'Panelist individually evaluates and grades the defense.',
        ],
        'RES-037' => [
            'title' => 'Evaluation Summary of Research Defense',
            'phase' => 'phase-4',
            'purpose' => 'Panelist signs the evaluation summary.',
        ],
        'RES-039' => [
            'title' => 'Research Revision Chart',
            'phase' => 'phase-5',
            'shared_with' => 'student',
            'purpose' => 'Panelist records the required revisions after defense.',
        ],
        'RES-044' => [
            'title' => 'Endorsement of Student Researcher for Data Gathering',
            'phase' => 'phase-6',
            'shared_with' => 'adviser',
            'purpose' => 'Panelist co-signs the data gathering endorsement.',
        ],
    ],

    'facilitator' => [
        'RES-026' => ['title' => 'Research Title Approval', 'phase' => 'phase-1', 'shared_with' => 'student', 'purpose' => 'Facilitator approves the title at the facilitator level.'],
        'RES-027' => ['title' => 'Invitation to Research Adviser', 'phase' => 'phase-2', 'shared_with' => 'adviser', 'purpose' => 'Facilitator issues the invitation to adviser.'],
        'RES-028' => ['title' => 'Invitation to Research Examination Panelist', 'phase' => 'phase-2', 'shared_with' => 'panelist', 'purpose' => 'Facilitator issues the invitation to panelist.'],
        'RES-030' => ['title' => 'Request for Change of Adviser/Panelist/Language Editor', 'phase' => 'phase-2', 'shared_with' => 'student', 'purpose' => 'Facilitator approves personnel change requests.'],
        'RES-038' => ['title' => 'Endorsement of Student Researchers to Research Adviser', 'phase' => 'phase-2', 'shared_with' => 'adviser', 'purpose' => 'Facilitator issues the endorsement to adviser.'],
        'RES-031' => ['title' => 'Consultation Record with Research Adviser', 'phase' => 'phase-3', 'shared_with' => 'student', 'purpose' => 'Facilitator has oversight of consultation records.'],
        'RES-032' => ['title' => 'Consultation Sheet (With Other Consultants)', 'phase' => 'phase-3', 'shared_with' => 'student', 'purpose' => 'Facilitator has oversight of other consultant sessions.'],
        'RES-033' => ['title' => 'Endorsement for Research Proposal/Final Oral Defense', 'phase' => 'phase-3', 'shared_with' => 'adviser', 'purpose' => 'Facilitator co-signs the defense endorsement.'],
        'RES-034' => ['title' => 'Research Proposal/Final Oral Defense Pre-Conference', 'phase' => 'phase-4', 'shared_with' => 'student', 'purpose' => 'Facilitator oversees the pre-conference.'],
        'RES-035' => ['title' => 'Defense Proceedings', 'phase' => 'phase-4', 'shared_with' => 'adviser', 'purpose' => 'Facilitator receives defense proceedings for record.'],
        'RES-036' => ['title' => 'Evaluation of Research Defense', 'phase' => 'phase-4', 'shared_with' => 'panelist', 'purpose' => 'Facilitator receives and validates the evaluation.'],
        'RES-037' => ['title' => 'Evaluation Summary of Research Defense', 'phase' => 'phase-4', 'shared_with' => 'panelist', 'purpose' => 'Facilitator receives the evaluation summary.'],
        'RES-039' => ['title' => 'Research Revision Chart', 'phase' => 'phase-5', 'shared_with' => 'student', 'purpose' => 'Facilitator oversees revision tracking.'],
        'RES-040' => ['title' => 'Endorsement to Research Instructor', 'phase' => 'phase-5', 'shared_with' => 'adviser', 'purpose' => 'Facilitator receives endorsement to instructor.'],
        'RES-041' => ['title' => 'Endorsement to Program Coordinator', 'phase' => 'phase-5', 'purpose' => 'Facilitator issues endorsement to program coordinator.'],
        'RES-042' => ['title' => 'Request for Research Instrument Validation', 'phase' => 'phase-6', 'shared_with' => 'student', 'purpose' => 'Facilitator oversees instrument validation requests.'],
        'RES-043A' => ['title' => 'Research Instrument Item Validation', 'phase' => 'phase-6', 'purpose' => 'Facilitator fills out item-level validation as validator.'],
        'RES-043B' => ['title' => 'Research Instrument Validation Rating', 'phase' => 'phase-6', 'purpose' => 'Facilitator fills out validation rating as validator.'],
        'RES-044' => ['title' => 'Endorsement of Student Researcher for Data Gathering', 'phase' => 'phase-6', 'shared_with' => 'adviser', 'purpose' => 'Facilitator co-signs data gathering endorsement.'],
        'RES-045' => ['title' => 'Certificate of Language Editing', 'phase' => 'phase-7', 'purpose' => 'Facilitator issues certificate of language editing.'],
        'RES-046' => ['title' => 'Certificate of Technical Editing', 'phase' => 'phase-7', 'purpose' => 'Facilitator issues certificate of technical editing.'],
        'RES-047' => ['title' => 'Endorsement for Reproduction of the Research Paper', 'phase' => 'phase-7', 'purpose' => 'Facilitator issues endorsement for reproduction.'],
        'RES-048' => ['title' => 'Self and Peer Evaluation', 'phase' => 'phase-7', 'shared_with' => 'student', 'purpose' => 'Facilitator receives self/peer evaluation records.'],
        'RES-049' => ['title' => 'Certificate of Authentic Authorship', 'phase' => 'phase-7', 'shared_with' => 'student', 'purpose' => 'Facilitator receives authentic authorship certificate.'],
    ],
];
