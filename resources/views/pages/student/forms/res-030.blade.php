<div x-show="activeOfficialForm === 'RES-030'" x-cloak><x-student-official-form code="RES-Form-030" title="Request for Change of Research Adviser / Panelist / Language Editor" guidebook-page="105">
    <div class="grid gap-4 md:grid-cols-2"><label>Date: <input type="date" name="date" class="w-full"></label><label>Degree Program: <input name="degree_program" class="w-full"></label></div>
    <label class="block">Name of Student/s:<textarea name="students" class="mt-1 min-h-16 w-full"></textarea></label>
    <label class="block">Research Title:<input name="research_title" class="w-full"></label>
    <fieldset><legend class="font-bold">Specific Request (Please check):</legend><div class="mt-2 grid gap-3 md:grid-cols-2">@foreach (['Change of Research Adviser','Change of Panelist','Change of Language Editor'] as $role)<label><input type="checkbox" name="personnel_type[]" value="{{ $role }}"> {{ $role }}</label><label>NAME: <input name="current_names[]" class="w-full"></label>@endforeach</div></fieldset>
    <label class="block">Proposed Adviser / Panelist / Language Editor:<input name="proposed_replacement" class="w-full"></label>
    <label class="block font-bold">Reason/s for replacement:<textarea name="reasons" class="mt-2 min-h-32 w-full"></textarea></label>
    <div class="pt-6"><p class="mb-3 font-bold">Requested by:</p><div class="grid gap-4 md:grid-cols-2">@for ($i=1;$i<=4;$i++)<label class="block"><input type="text" name="student_signatures[]" placeholder="Type your full name as your electronic signature" class="w-full"><span class="mt-1 block text-center text-xs">Student {{ $i }} — Name & Signature</span></label>@endfor</div></div>
    <div class="grid grid-cols-2 gap-8 pt-10 text-center"><div class="border-t border-[#173c30] pt-1">Approved by: College Dean (Name & Signature)</div><div class="border-t border-[#173c30] pt-1">Noted by: Program Coordinator (Name & Signature)</div></div>
</x-student-official-form></div>
