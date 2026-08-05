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
        <div><strong>Endorsed by:</strong><div class="mt-5 grid gap-6 text-center md:grid-cols-3">@for ($i=1; $i<=3; $i++)<x-official-signature-field name-field="res_044_panelist_names[]" :label="'Panelist '.$i" />@endfor</div></div>
        <x-official-signature-field name-field="res_044_adviser_printed_name" label="Research Adviser" class="mx-auto max-w-sm pt-8" />
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field name-field="res_044_program_coordinator_name" label="Program Coordinator" /><x-official-signature-field name-field="res_044_college_dean_name" label="College Dean" /></div>
    </x-student-official-form>
</div>
