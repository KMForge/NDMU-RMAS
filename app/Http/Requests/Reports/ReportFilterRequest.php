<?php

namespace App\Http\Requests\Reports;

use App\Modules\ReportsAnalytics\ValueObjects\ReportFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'research_class_id' => ['nullable', 'integer', 'exists:research_classes,id'],
            'adviser_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage' => ['nullable', 'string', 'max:100', 'exists:milestone_definitions,code'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'overdue', 'delayed'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): ReportFilters
    {
        return ReportFilters::from($this->validated());
    }
}
