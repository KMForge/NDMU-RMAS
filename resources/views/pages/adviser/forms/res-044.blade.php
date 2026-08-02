<div x-show="activeOfficialForm === 'RES-044'" x-cloak>
    <x-student-official-form code="RES-Form-044" title="Endorsement of Student Researcher for Data Gathering" guidebook-page="130">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_044_date"></label>
        <p><input name="res_044_college_dean" class="w-72" placeholder="College Dean"><br>College of <input name="res_044_college" value="Engineering, Architecture, and Computing" class="w-80"></p>
        <p>Dear <input name="res_044_salutation" class="w-56">,</p>
        <div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input name="res_044_students[]" class="flex-1"></label>@endfor</div>
        <label class="block">whose research title is:<input name="res_044_research_title" class="mt-1 w-full"></label>
        <p>has/have fully accomplished the following:</p>
        <ol class="list-[lower-alpha] space-y-2 pl-5 text-xs leading-5"><li>Full revision of the research proposal paper, duly approved by the members of the panel.</li><li>Acquired a Validation Rating of not less than 4.00 (Very Good), with 1.00 as the lowest and 5.00 as the highest rating.</li></ol>
        <p>Therefore, the above-mentioned student researcher/s is/are highly endorsed to proceed to the data-gathering phase.</p>
        <div><strong>Endorsed by:</strong><div class="mt-5 grid gap-6 text-center md:grid-cols-3">@for ($i=1; $i<=3; $i++)@if (($formActor ?? 'adviser') === 'panelist')<label><input name="res_044_panelist_signatures[]" class="w-full text-center"><span class="block">Panelist {{ $i }}<br><small>(Name & Electronic Signature)</small></span></label>@else<div class="border-t border-[#173c30] pt-1">Panelist {{ $i }}<br><small>(Name & Signature)</small></div>@endif @endfor</div></div>
        <label class="mx-auto block max-w-sm pt-8 text-center"><input name="res_044_adviser_signature" class="w-full text-center"><span class="block font-bold">Research Adviser (Name & Electronic Signature)</span></label>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><div class="border-t border-[#173c30] pt-1">Program Coordinator<br><small>(Name & Signature)</small></div><div class="border-t border-[#173c30] pt-1">College Dean<br><small>(Name & Signature)</small></div></div>
    </x-student-official-form>
</div>
