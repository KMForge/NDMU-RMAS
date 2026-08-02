@if ($officialForms === [])
    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">
        No official panelist forms have been published yet.
    </div>
@else
    {{-- Shared forms link directly to their existing role templates. --}}
    @include('pages.panelist.forms.res-028')
    @include('pages.student.forms.res-034', ['formActor' => 'panelist'])
    @include('pages.adviser.forms.res-035', ['formActor' => 'panelist'])
    @include('pages.panelist.forms.res-036')
    @include('pages.panelist.forms.res-037')
    @include('pages.student.forms.res-039', ['formActor' => 'panelist'])
    @include('pages.adviser.forms.res-044', ['formActor' => 'panelist'])
@endif
