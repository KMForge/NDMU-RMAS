<div x-show="activeOfficialForm === 'RES-035'" x-cloak>
    <x-student-official-form code="RES-Form-035" title="Research Proposal / Final Oral Defense Proceedings" guidebook-page="117">
        <div class="grid gap-4 md:grid-cols-2">
            <fieldset><legend class="mb-2 font-bold">Name & Course of Student/s:</legend>@for ($i=1; $i<=4; $i++)<label class="mb-2 flex gap-2"><span>{{ $i }}.</span><input name="res_035_students[]" class="flex-1"></label>@endfor</fieldset>
            <div class="space-y-4"><label class="block">Date:<input type="date" name="res_035_date" class="w-full"></label><label class="block">Research Adviser:<input name="res_035_adviser" class="w-full"></label></div>
        </div>
        <label class="block font-bold">Research Title:<input name="res_035_research_title" class="mt-1 w-full font-normal"></label>
        <fieldset class="flex flex-wrap gap-6"><legend class="mb-2 font-bold">Type of Defense:</legend><label><input type="radio" name="res_035_defense_type" value="proposal"> Research Proposal Defense</label><label><input type="radio" name="res_035_defense_type" value="final"> Research Final Oral Defense</label></fieldset>
        <table class="official-form-table text-xs">
            <thead><tr><th>Area</th><th>Comments / Corrections / Suggestions</th></tr></thead>
            <tbody>@foreach (['Title','Introduction','Method','Results','Discussion','References','Others'] as $area)<tr><th class="w-1/4 text-left">{{ $area }}</th><td><textarea name="res_035_comments[{{ strtolower($area) }}]" class="min-h-16 w-full border-0"></textarea></td></tr>@endforeach</tbody>
        </table>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-2"><label><input name="res_035_adviser_signature" class="w-full text-center"><span class="block font-bold">Research Adviser Electronic Signature</span></label><label><input name="res_035_panel_chair_signature" class="w-full text-center"><span class="block">Panel Chair Electronic Signature</span></label></div>
    </x-student-official-form>
</div>
