<template x-if="activeOfficialForm === 'RES-030'"><div><x-student-official-form code="RES-Form-030" title="Request for Change of Research Adviser / Panelist / Language Editor" guidebook-page="105">
    <div class="grid gap-4 md:grid-cols-2"><label>Date: <input type="date" name="date" class="w-full"></label><label>Degree Program: <input name="degree_program" class="w-full"></label></div>
    <label class="block">Name of Student/s:<textarea name="students" class="mt-1 min-h-16 w-full"></textarea></label>
    <label class="block">Research Title:<input name="research_title" class="w-full"></label>
    <fieldset><legend class="font-bold">Requested personnel change:</legend><div class="mt-2 flex flex-wrap gap-5">@foreach (['Research Adviser','Research Panelist','Language Editor'] as $role)<label><input type="checkbox" name="personnel_type[]" value="{{ $role }}"> {{ $role }}</label>@endforeach</div></fieldset>
    <div class="grid gap-4 md:grid-cols-2"><label>Current personnel:<input name="current_personnel" class="w-full"></label><label>Proposed replacement:<input name="proposed_replacement" class="w-full"></label></div>
    <label class="block font-bold">Reason/s for replacement:<textarea name="reasons" class="mt-2 min-h-32 w-full"></textarea></label>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><div class="border-t border-[#173c30] pt-1">Student Researcher/s</div><div class="border-t border-[#173c30] pt-1">Research Adviser</div><div class="border-t border-[#173c30] pt-1">Program Coordinator</div><div class="border-t border-[#173c30] pt-1">College Dean</div></div>
</x-student-official-form></div></template>
