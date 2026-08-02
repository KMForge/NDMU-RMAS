@if ($officialForms === [])
    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">
        No official adviser forms have been published yet.
    </div>
@else
    {{-- Shared forms are linked to their existing Blade views; they are not duplicated. --}}
    @include('pages.student.forms.res-026')
    @include('pages.adviser.forms.res-027')
    @include('pages.student.forms.res-031')
    @include('pages.student.forms.res-032')
    @include('pages.adviser.forms.res-033')
    @include('pages.student.forms.res-034')
    @include('pages.adviser.forms.res-035')
    @include('pages.adviser.forms.res-038')
    @include('pages.adviser.forms.res-040')
    @include('pages.student.forms.res-042')
    @include('pages.adviser.forms.res-044')
    @include('pages.student.forms.res-048')
    @include('pages.student.forms.res-049')
@endif
