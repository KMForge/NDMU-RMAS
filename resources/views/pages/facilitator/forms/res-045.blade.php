<div x-show="activeOfficialForm === 'RES-045'" x-cloak>
    <x-student-official-form code="RES-Form-045" title="Certificate of Language Editing" guidebook-page="131">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_045_date"></label><p><input name="res_045_college_dean" class="w-72" placeholder="College Dean"><br>College of <input name="res_045_college" value="Engineering, Architecture, and Computing" class="w-80"></p><p>Dear <input name="res_045_salutation" class="w-56">,</p>
        <p>This is to certify that the research paper entitled:</p><input name="res_045_research_title" class="w-full text-center" aria-label="Research title"><p>to be submitted by the student researcher/s whose name/s appear below:</p><div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input name="res_045_students[]" class="flex-1"></label>@endfor</div>
        <p class="leading-7">has undergone language editing. The content and the authors' intention were not altered in any way during the editing process.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><label><input name="res_045_editor_signature" class="w-full text-center"><span class="block font-bold">Language Editor (Name & Electronic Signature)</span></label><label>Date Edited:<input type="date" name="res_045_edited_at" class="w-full"></label></div>
    </x-student-official-form>
</div>
