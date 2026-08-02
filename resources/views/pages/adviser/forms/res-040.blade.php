<div x-show="activeOfficialForm === 'RES-040'" x-cloak>
    <x-student-official-form code="RES-Form-040" title="Endorsement of the Revised Research Proposal to the Research Instructor" guidebook-page="125">
        <h3 class="text-center font-bold uppercase">To the Research Instructor</h3>
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_040_date"></label>
        <p>Dear Sir/Madam:</p>
        <p class="leading-7">This is to officially endorse the hard copy of the Revised Research Proposal Paper entitled:</p>
        <input name="res_040_research_title" class="w-full text-center" aria-label="Research title">
        <p>of the following student researchers:</p>
        <div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input name="res_040_students[]" class="flex-1"></label>@endfor</div>
        <p>approved by the panel of reviewers.</p><p>Thank you very much.</p><p>Sincerely yours,</p>
        <label class="ml-auto block max-w-sm pt-8 text-center"><input name="res_040_adviser_signature" class="w-full text-center"><span class="block font-bold">Research Adviser (Name & Electronic Signature)</span></label>
    </x-student-official-form>
</div>
