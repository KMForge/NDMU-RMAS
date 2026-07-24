<?php

namespace App\Modules\Documents\Exceptions;

use RuntimeException;

class DuplicateDocumentSubmission extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This document submission has already been received.');
    }
}
