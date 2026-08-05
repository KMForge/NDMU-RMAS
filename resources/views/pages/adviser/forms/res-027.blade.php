<div x-show="activeOfficialForm === 'RES-027'" x-cloak>
    <x-student-official-form code="RES-Form-027" title="Invitation to Research Adviser" guidebook-page="102">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_027_date"></label>
        <p>Dear <input type="text" name="res_027_adviser_name" class="w-72" placeholder="Name of Research Adviser">,</p>
        <p class="leading-7">May I invite you to be the <strong>RESEARCH ADVISER</strong> of the following
            <input type="text" name="res_027_course" class="w-56" placeholder="course"> student/s:</p>
        <fieldset>
            <legend class="mb-2 font-bold">Name/s:</legend>
            <div class="grid gap-3 md:grid-cols-2">
                @for ($i = 1; $i <= 4; $i++)
                    <label class="flex gap-2"><span>{{ $i }}.</span><input type="text" name="res_027_students[]" class="flex-1"></label>
                @endfor
            </div>
        </fieldset>
        <label class="block font-bold">Research Title:<input type="text" name="res_027_research_title" class="mt-1 w-full font-normal"></label>
        <div class="space-y-2 text-xs leading-5">
            <p>As Research Adviser, please be guided by the following:</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>Guide the student-advisees in all stages of the approved research study and ensure proper research-writing protocol.</li>
                <li>Regularly monitor their progress through face-to-face or online consultation schedules.</li>
                <li>Endorse the research for final oral defense.</li>
                <li>Guide preparation of the research presentation for the final oral defense.</li>
                <li>Document the panel members' comments, suggestions, and recommendations during the defense.</li>
            </ol>
        </div>
        <p>Thank you very much.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2">
            <label><input type="text" name="res_027_issued_by" class="w-full text-center"><span class="block">Program Coordinator / College Dean</span></label>
            <x-official-signature-field name-field="res_027_adviser_printed_name" label="Research Adviser" />
        </div>
    </x-student-official-form>
</div>
