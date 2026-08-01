<template x-if="activeOfficialForm === 'RES-049'"><div><x-student-official-form code="RES-Form-049" title="Certificate of Authentic Authorship" guidebook-page="140">
    <p class="text-justify leading-6">I hereby declare that this submission is my/our own work and, to the best of my/our knowledge, contains no material previously published or written by another person except where due acknowledgment is made in the text. I/We accept responsibility for the authenticity and integrity of this research work.</p>
    <label class="block">Research Title:<input name="research_title" class="w-full"></label>
    <label class="block">Name of Researcher/s:<textarea name="researchers" class="mt-2 min-h-24 w-full"></textarea></label>
    <label class="flex items-start gap-3 rounded border border-[#173c30] p-4"><input type="checkbox" name="authorship_confirmed" required class="mt-1"><span>I certify that I have read and understood this declaration and affirm that the information supplied is true and correct.</span></label>
    <div class="space-y-8 pt-8">@for ($i=1;$i<=4;$i++)<div class="grid grid-cols-2 gap-8 text-center"><div class="border-t border-[#173c30] pt-1">Printed Name of Researcher {{ $i }}</div><div class="border-t border-[#173c30] pt-1">Signature / Date</div></div>@endfor</div>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><div class="border-t border-[#173c30] pt-1">Research Adviser</div><div class="border-t border-[#173c30] pt-1">Program Coordinator</div></div>
</x-student-official-form></div></template>
