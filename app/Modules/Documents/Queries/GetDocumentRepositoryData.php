<?php

namespace App\Modules\Documents\Queries;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentRepositoryAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GetDocumentRepositoryData
{
    public function __construct(private readonly DocumentRepositoryAccess $access) {}

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function for(User $user, array $input): array
    {
        $filters = $this->filters($input);
        $scope = $this->access->scopeFor(Document::query(), $user);
        $filtered = (clone $scope)
            ->when($filters['search'] !== '', fn (Builder $query) => $query
                ->whereRaw('LOWER(original_filename) LIKE ?', ['%'.Str::lower($filters['search']).'%']))
            ->when($filters['stage'] !== 'all', fn (Builder $query) => $query
                ->where('document_stage', $filters['stage']))
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query
                ->where('status', $filters['status']))
            ->when($filters['file_type'] !== 'all', fn (Builder $query) => $query
                ->where('file_type', $filters['file_type']))
            ->when($filters['version'] === 'current', fn (Builder $query) => $query
                ->where('is_current', true));

        $documents = $filtered
            ->with([
                'user:id,name,email',
                'researchClassGroup:id,research_class_id,name,status,disbanded_at',
                'researchClassGroup.researchClass:id,name,facilitator_id',
            ])
            ->orderBy('submitted_at', $filters['sort'] === 'oldest' ? 'asc' : 'desc')
            ->paginate(10, ['*'], 'repository_page')
            ->withQueryString();

        $stats = (clone $scope)->where('is_current', true)
            ->selectRaw('status, COUNT(*) aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'repositoryDocuments' => $documents,
            'repositoryFilters' => $filters,
            'repositoryStats' => [
                'total' => (int) $stats->sum(),
                'accepted' => (int) $stats->get(DocumentStatus::Accepted->value, 0),
                'pending' => (int) $stats->get(DocumentStatus::Pending->value, 0)
                    + (int) $stats->get(DocumentStatus::Submitted->value, 0),
                'under_review' => (int) $stats->get(DocumentStatus::UnderReview->value, 0),
            ],
            'repositoryStageOptions' => collect(DocumentStage::cases())
                ->mapWithKeys(fn (DocumentStage $stage) => [$stage->value => $stage->label()])
                ->all(),
            'repositoryStatusOptions' => collect(DocumentStatus::cases())
                ->mapWithKeys(fn (DocumentStatus $status) => [$status->value => Str::headline($status->value)])
                ->all(),
        ];
    }

    /** @param array<string, mixed> $input
     * @return array{search:string,stage:string,status:string,file_type:string,version:string,sort:string}
     */
    private function filters(array $input): array
    {
        $allowedStages = array_column(DocumentStage::cases(), 'value');
        $allowedStatuses = array_column(DocumentStatus::cases(), 'value');

        return [
            'search' => Str::limit(trim(strip_tags((string) ($input['repository_q'] ?? ''))), 100, ''),
            'stage' => in_array($input['repository_stage'] ?? 'all', [...$allowedStages, 'all'], true)
                ? (string) ($input['repository_stage'] ?? 'all') : 'all',
            'status' => in_array($input['repository_status'] ?? 'all', [...$allowedStatuses, 'all'], true)
                ? (string) ($input['repository_status'] ?? 'all') : 'all',
            'file_type' => in_array($input['repository_type'] ?? 'all', ['all', 'pdf', 'docx'], true)
                ? (string) ($input['repository_type'] ?? 'all') : 'all',
            'version' => in_array($input['repository_version'] ?? 'current', ['current', 'all'], true)
                ? (string) ($input['repository_version'] ?? 'current') : 'current',
            'sort' => in_array($input['repository_sort'] ?? 'newest', ['newest', 'oldest'], true)
                ? (string) ($input['repository_sort'] ?? 'newest') : 'newest',
        ];
    }
}
