<?php

return [
    'college' => [
        'code' => 'CEAC',
        'name' => 'College of Engineering, Architecture, and Computing (CEAC)',
    ],

    'departments' => [
        [
            'code' => 'CSD',
            'name' => 'Computer Studies Department',
            'label' => 'Computer Studies Department (CSD)',
            'student_organizations' => 'Course-specific computing/library organizations',
            'programs' => [
                [
                    'code' => 'BSCS',
                    'name' => 'BS Computer Science',
                    'label' => 'BS Computer Science (BSCS)',
                ],
                [
                    'code' => 'BSIT',
                    'name' => 'BS Information Technology',
                    'label' => 'BS Information Technology (BSIT)',
                ],
                [
                    'code' => 'BLIS',
                    'name' => 'Bachelor of Library and Information Science',
                    'label' => 'Bachelor of Library and Information Science (BLIS)',
                ],
            ],
        ],
        [
            'code' => 'EECE',
            'name' => 'Electrical, Electronics, and Computer Engineering Department',
            'label' => 'Electrical, Electronics, and Computer Engineering Department (EECE)',
            'student_organizations' => 'IIEE, JIECEP and ICpEP.SE',
            'programs' => [
                [
                    'code' => 'BSEE',
                    'name' => 'BS Electrical Engineering',
                    'label' => 'BS Electrical Engineering (BSEE)',
                ],
                [
                    'code' => 'BSECE',
                    'name' => 'BS Electronics Engineering',
                    'label' => 'BS Electronics Engineering (BSECE)',
                ],
                [
                    'code' => 'BSCPE',
                    'name' => 'BS Computer Engineering',
                    'label' => 'BS Computer Engineering (BSCpE)',
                ],
            ],
        ],
        [
            'code' => 'CED',
            'name' => 'Civil Engineering Department',
            'label' => 'Civil Engineering Department',
            'student_organizations' => 'PICE–NDMU Student Chapter',
            'programs' => [
                [
                    'code' => 'BSCE',
                    'name' => 'BS Civil Engineering',
                    'label' => 'BS Civil Engineering (BSCE)',
                ],
            ],
        ],
        [
            'code' => 'AD',
            'name' => 'Architecture Department',
            'label' => 'Architecture Department',
            'student_organizations' => 'UAPSA–NDMU Chapter',
            'programs' => [
                [
                    'code' => 'BSARCH',
                    'name' => 'BS Architecture',
                    'label' => 'BS Architecture (BSArch)',
                ],
            ],
        ],
    ],

    // Flattened helper list for backward compatibility
    'programs' => [
        [
            'code' => 'BSARCH',
            'name' => 'BS Architecture',
            'label' => 'BS Architecture (BSArch)',
            'department_code' => 'AD',
            'student_organizations' => 'UAPSA–NDMU Chapter',
        ],
        [
            'code' => 'BSCE',
            'name' => 'BS Civil Engineering',
            'label' => 'BS Civil Engineering (BSCE)',
            'department_code' => 'CED',
            'student_organizations' => 'PICE–NDMU Student Chapter',
        ],
        [
            'code' => 'BSCPE',
            'name' => 'BS Computer Engineering',
            'label' => 'BS Computer Engineering (BSCpE)',
            'department_code' => 'EECE',
            'student_organizations' => 'IIEE, JIECEP and ICpEP.SE',
        ],
        [
            'code' => 'BSCS',
            'name' => 'BS Computer Science',
            'label' => 'BS Computer Science (BSCS)',
            'department_code' => 'CSD',
            'student_organizations' => 'Course-specific computing/library organizations',
        ],
        [
            'code' => 'BSEE',
            'name' => 'BS Electrical Engineering',
            'label' => 'BS Electrical Engineering (BSEE)',
            'department_code' => 'EECE',
            'student_organizations' => 'IIEE, JIECEP and ICpEP.SE',
        ],
        [
            'code' => 'BSECE',
            'name' => 'BS Electronics Engineering',
            'label' => 'BS Electronics Engineering (BSECE)',
            'department_code' => 'EECE',
            'student_organizations' => 'IIEE, JIECEP and ICpEP.SE',
        ],
        [
            'code' => 'BSIT',
            'name' => 'BS Information Technology',
            'label' => 'BS Information Technology (BSIT)',
            'department_code' => 'CSD',
            'student_organizations' => 'Course-specific computing/library organizations',
        ],
        [
            'code' => 'BLIS',
            'name' => 'Bachelor of Library and Information Science',
            'label' => 'Bachelor of Library and Information Science (BLIS)',
            'department_code' => 'CSD',
            'student_organizations' => 'Course-specific computing/library organizations',
        ],
    ],
];
