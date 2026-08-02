<div x-show="activeOfficialForm === 'RES-038'" x-cloak>
    <x-student-official-form code="RES-Form-038" title="Endorsement of Student Researcher/s to Research Adviser" guidebook-page="122">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_038_date"></label>
        <p>Dear <input name="res_038_adviser_name" class="w-72" placeholder="Name of Research Adviser">,</p>
        <p>This is to officially endorse the following student researchers as your Research Advisees:</p>
        <div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input name="res_038_students[]" class="flex-1"></label>@endfor</div>
        <label class="block">whose research paper is entitled:<input name="res_038_research_title" class="mt-1 w-full"></label>
        <label class="flex flex-wrap items-center gap-2">Endorsed this <input type="number" min="1" max="31" name="res_038_day" class="w-20"> day of <input type="text" name="res_038_month_year" class="w-48"></label>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><div class="border-t border-[#173c30] pt-1">Dean / Program Head<br><small>(Name & Signature)</small></div><label><input name="res_038_adviser_acceptance" class="w-full text-center"><span class="block font-bold">Research Adviser Acceptance / Electronic Signature</span></label></div>
    </x-student-official-form>
</div>
