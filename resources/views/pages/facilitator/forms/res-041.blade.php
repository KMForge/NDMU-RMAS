<div x-show="activeOfficialForm === 'RES-041'" x-cloak>
    <x-student-official-form code="RES-Form-041" title="Endorsement of the Revised Research Proposals to the Program Coordinator" guidebook-page="126">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_041_date"></label>
        <p>Dear Sir/Madam:</p>
        <p class="leading-7">This is to officially endorse the hard copies of the Revised Research Proposal papers of the students in <input name="res_041_subject_number" class="w-40" placeholder="Subject No."> <input name="res_041_descriptive_title" class="w-64" placeholder="Descriptive Title">.</p>
        <p>Below is the list of Research Titles and the corresponding student-researchers:</p>
        <table class="official-form-table text-xs"><thead><tr><th>Research Titles</th><th>Student-Researchers</th></tr></thead><tbody>@for ($i=1; $i<=8; $i++)<tr><td><input name="res_041_entries[{{ $i }}][title]" class="w-full"></td><td><textarea name="res_041_entries[{{ $i }}][researchers]" class="min-h-12 w-full border-0"></textarea></td></tr>@endfor</tbody></table>
        <p>Sincerely yours,</p>
        <label class="ml-auto block max-w-sm pt-8 text-center"><input name="res_041_instructor_signature" class="w-full text-center"><span class="block font-bold">Research Instructor (Name & Electronic Signature)</span></label>
    </x-student-official-form>
</div>
