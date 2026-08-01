<template x-if="activeOfficialForm === 'RES-026'">
    <div><x-student-official-form code="RES-Form-026" title="Research Title Approval" guidebook-page="101">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="border border-[#173c30] p-3 text-xs leading-5">
                <strong>Requirements for Research Title Proposal:</strong><br>
                • Three (3) proposed research titles<br>
                • Each title must contain the Background/Rationale, Objectives/Statement of the Problem, and Brief Methodology Plan.
            </div>
            <label class="flex items-center gap-2 whitespace-nowrap">Date: <input type="date" name="date"></label>
        </div>
        <fieldset><legend class="mb-2 font-bold">Name of Student/s:</legend><div class="grid grid-cols-1 gap-3 md:grid-cols-2">@for ($i = 1; $i <= 4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input class="w-full" name="students[]"></label>@endfor</div></fieldset>
        <fieldset><legend class="mb-2 font-bold">Proposed Research Topics:</legend><div class="space-y-3">@for ($i = 1; $i <= 3; $i++)<label class="flex items-start gap-2"><span>{{ $i }}.</span><textarea class="min-h-14 w-full" name="topics[]"></textarea></label>@endfor</div></fieldset>
        <label class="flex items-center gap-2 font-bold">Approved Research Title No. <input class="w-32" name="approved_title_number" readonly></label>
        <label class="block font-bold">Remarks:<textarea class="mt-2 min-h-24 w-full" name="remarks" readonly></textarea></label>
        <div class="grid grid-cols-2 gap-x-6 gap-y-4 pt-3 text-center"><div class="border-t border-[#173c30] pt-1">Name</div><div class="border-t border-[#173c30] pt-1">Signature</div><div class="col-span-2 text-left font-bold">Panel of Examiners:</div>@foreach (['Chairman','Member 1','Member 2'] as $role)<div class="text-left italic">{{ $role }}:</div><div class="border-b border-[#173c30]"></div>@endforeach</div>
        <div class="grid grid-cols-2 gap-8 pt-8 text-center"><div class="border-t border-[#173c30] pt-1">Program Coordinator<br><small>(Name & Signature)</small></div><div class="border-t border-[#173c30] pt-1">College Dean<br><small>(Name & Signature)</small></div></div>
    </x-student-official-form></div>
</template>
