<div x-show="activeOfficialForm === 'RES-033'" x-cloak>
    <x-student-official-form code="RES-Form-033" title="Endorsement for Research Proposal / Final Oral Defense" guidebook-page="113">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_033_date"></label>
        <p>Dear <input type="text" name="res_033_program_coordinator" class="w-72" placeholder="Name of Program Coordinator">,</p>
        <p>This is to endorse the research paper of the following student researchers:</p>
        <div class="grid gap-3 md:grid-cols-2">
            @for ($i = 1; $i <= 4; $i++)
                <label class="flex gap-2"><span>{{ $i }}.</span><input type="text" name="res_033_students[]" class="flex-1"></label>
            @endfor
        </div>
        <label class="block font-bold">Research Title:<input type="text" name="res_033_research_title" class="mt-1 w-full font-normal"></label>
        <fieldset class="space-y-3">
            <legend class="mb-2 font-bold">For Research (please check):</legend>
            <label class="flex flex-wrap items-center gap-2"><input type="radio" name="res_033_defense_type" value="proposal"> Proposal Defense on <input type="date" name="res_033_proposal_date"><input type="time" name="res_033_proposal_time"></label>
            <label class="flex flex-wrap items-center gap-2"><input type="radio" name="res_033_defense_type" value="final"> Final Defense on <input type="date" name="res_033_final_date"><input type="time" name="res_033_final_time"></label>
        </fieldset>
        <p class="text-xs leading-5">Attached are four (4) copies of the research paper: three copies for the panelists and one for the Research Adviser.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <label><input type="text" name="res_033_adviser_signature" class="w-full text-center"><span class="block font-bold">Instructor / Research Adviser Electronic Signature</span></label>
            <label><input type="text" name="res_033_received_by" class="w-full text-center"><span class="block">Received by</span><input type="date" name="res_033_received_date" class="mt-2"></label>
        </div>
    </x-student-official-form>
</div>
