<?php

return [
    'college' => [
        'code' => 'CEAC',
        'name' => 'College of Engineering, Architecture, and Computing (CEAC)',
    ],

    /*
     * The current database schema relates programs through a department.
     * Because NDMU-RMAS is scoped to one college, this single organizational
     * unit groups every supported CEAC program.
     */
    'department' => [
        'code' => 'CEAC-PROGRAMS',
        'name' => 'CEAC Academic Programs',
    ],

    'programs' => [
        [
            'code' => 'BSARCH',
            'name' => 'Bachelor of Science in Architecture',
            'label' => 'Bachelor of Science in Architecture (BS Architecture)',
        ],
        [
            'code' => 'BSCE',
            'name' => 'Bachelor of Science in Civil Engineering',
            'label' => 'Bachelor of Science in Civil Engineering (BS Civil Engineering)',
        ],
        [
            'code' => 'BSCPE',
            'name' => 'Bachelor of Science in Computer Engineering',
            'label' => 'Bachelor of Science in Computer Engineering (BS Computer Engineering)',
        ],
        [
            'code' => 'BSCS',
            'name' => 'Bachelor of Science in Computer Science',
            'label' => 'Bachelor of Science in Computer Science (BS Computer Science)',
        ],
        [
            'code' => 'BSEE',
            'name' => 'Bachelor of Science in Electrical Engineering',
            'label' => 'Bachelor of Science in Electrical Engineering (BS Electrical Engineering)',
        ],
        [
            'code' => 'BSECE',
            'name' => 'Bachelor of Science in Electronics Engineering',
            'label' => 'Bachelor of Science in Electronics Engineering (BS Electronics Engineering)',
        ],
        [
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'label' => 'Bachelor of Science in Information Technology (BS Information Technology)',
        ],
        [
            'code' => 'BLIS',
            'name' => 'Bachelor of Library and Information Science',
            'label' => 'Bachelor of Library and Information Science (BLIS)',
        ],
    ],
];
