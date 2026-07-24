<?php

namespace App\Modules\Consultations\Exceptions;

use RuntimeException;

class DuplicateConsultationRequest extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This consultation request has already been submitted.');
    }
}
