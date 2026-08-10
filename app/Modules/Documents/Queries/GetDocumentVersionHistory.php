<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use Illuminate\Support\Collection;

class GetDocumentVersionHistory
{
    /** @return Collection<int, Document> */
    public function for(Document $document): Collection
    {
        if ($document->research_class_group_id === null) {
            return collect([$document->loadMissing('user:id,name,email')]);
        }

        return Document::query()
            ->where('research_class_group_id', $document->research_class_group_id)
            ->where('document_stage', $document->document_stage?->value)
            ->with('user:id,name,email')
            ->orderByDesc('version_number')
            ->get();
    }
}
