<?php

namespace App\Modules\Documents\Exceptions;

use RuntimeException;

class DocumentUploadFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The document could not be stored securely. Please try again.');
    }
}
