<template x-if="activeOfficialForm === 'RES-042'"><div><x-student-official-form code="RES-Form-042" title="Request for Research Instrument Validation" guidebook-page="133">
    <div class="flex justify-end"><label>Date: <input type="date" name="date"></label></div>
    <label class="block">To (Validator):<input name="validator" class="w-full"></label><label class="block">Field of Expertise:<input name="expertise" class="w-full"></label>
    <p>Greetings!</p><p>We respectfully request your professional assistance in validating the research instrument described below.</p>
    <label class="block">Research Title:<input name="research_title" class="w-full"></label><label class="block">Type of Instrument:<input name="instrument_type" class="w-full"></label>
    <label class="block">Name of Student Researcher/s:<textarea name="students" class="mt-1 min-h-20 w-full"></textarea></label>
    <label class="block">Purpose / Description of Instrument:<textarea name="description" class="mt-2 min-h-32 w-full"></textarea></label>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><div class="border-t border-[#173c30] pt-1">Student Researcher/s</div><div class="border-t border-[#173c30] pt-1">Research Adviser</div><div class="border-t border-[#173c30] pt-1">Program Coordinator</div><div class="border-t border-[#173c30] pt-1">College Dean</div></div>
</x-student-official-form></div></template>
