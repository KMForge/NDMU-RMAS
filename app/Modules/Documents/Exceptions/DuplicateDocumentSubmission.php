<?php

namespace App\Modules\Documents\Exceptions;

use RuntimeException;

class DuplicateDocumentSubmission extends RuntimeException
{
    public function __construct(string $message = 'This document submission has already been received.')
    {
        parent::__construct($message);
    }
}
