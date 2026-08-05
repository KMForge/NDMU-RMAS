<div x-show="activeOfficialForm === 'RES-046'" x-cloak>
    <x-student-official-form code="RES-Form-046" title="Certificate of Technical Editing" guidebook-page="132">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_046_date"></label><p><input name="res_046_college_dean" class="w-72" placeholder="College Dean"><br>College of <input name="res_046_college" value="Engineering, Architecture, and Computing" class="w-80"></p><p>Dear <input name="res_046_salutation" class="w-56">,</p>
        <p>This is to certify that the research paper entitled:</p><input name="res_046_research_title" class="w-full text-center" aria-label="Research title"><p>of the student researcher/s whose name/s appear below:</p><div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input name="res_046_students[]" class="flex-1"></label>@endfor</div>
        <p class="leading-7">has been edited technically. The content and the authors' intention were not altered in any way during the editing process. The paper follows the prescribed research format stipulated in the NDMU Undergraduate Research Manual.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><x-official-signature-field name-field="res_046_editor_printed_name" label="Technical Editor" /><label>Date Edited:<input type="date" name="res_046_edited_at" class="w-full"></label></div>
    </x-student-official-form>
</div>
