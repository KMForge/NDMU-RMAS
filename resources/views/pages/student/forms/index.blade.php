@if ($officialForms === [])
    <x-student-section-heading
        title="Official Forms"
        description="Official research forms made available by the university."
    />
    <x-student-empty-state message="No official research forms have been published yet." />
@else
    @include('pages.student.forms.res-026')
    @include('pages.student.forms.res-030')
    @include('pages.student.forms.res-031')
    @include('pages.student.forms.res-032')
    @include('pages.student.forms.res-034')
    @include('pages.student.forms.res-039')
    @include('pages.student.forms.res-042')
    @include('pages.student.forms.res-048')
    @include('pages.student.forms.res-049')
@endif
