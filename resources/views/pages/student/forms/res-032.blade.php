<template x-if="activeOfficialForm === 'RES-032'"><div><x-student-official-form code="RES-Form-032" title="Consultation Sheet (With Other Consultants)" guidebook-page="116">
    <div class="grid gap-4 md:grid-cols-2"><label>Name of Student/s:<textarea name="students" class="mt-1 min-h-20 w-full"></textarea></label><div class="space-y-4"><label class="block">Degree Program:<input name="degree_program" class="w-full"></label><label class="block">Date:<input type="date" name="date" class="w-full"></label></div></div>
    <label class="block">Research Title:<input name="research_title" class="w-full"></label>
    <div class="grid gap-4 md:grid-cols-2"><label>Name of Consultant:<input name="consultant_name" class="w-full"></label><label>Field/Specialization:<input name="specialization" class="w-full"></label></div>
    <label class="block font-bold">Purpose / Topics Discussed:<textarea name="topics" class="mt-2 min-h-28 w-full"></textarea></label>
    <label class="block font-bold">Consultant's Recommendations:<textarea name="recommendations" class="mt-2 min-h-36 w-full"></textarea></label>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><div class="border-t border-[#173c30] pt-1">Student Researcher/s</div><div class="border-t border-[#173c30] pt-1">Consultant</div></div>
</x-student-official-form></div></template>
