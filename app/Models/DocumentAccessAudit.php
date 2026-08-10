<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['document_id', 'user_id', 'action', 'ip_address', 'user_agent', 'accessed_at'])]
class DocumentAccessAudit extends Model
{
    protected function casts(): array
    {
        return ['accessed_at' => 'immutable_datetime'];
    }
}
