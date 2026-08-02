<div x-show="activeOfficialForm === 'RES-028'" x-cloak>
    <x-student-official-form code="RES-Form-028" title="Invitation to Research Examination Panelist" guidebook-page="103">
        <label class="ml-auto flex w-fit items-center gap-2">Date: <input type="date" name="res_028_date"></label>
        <p>To: <input type="text" name="res_028_panelist_name" class="w-72" placeholder="Name of Research Panelist"></p>
        <p class="leading-7">May I invite you to serve as
            <label><input type="radio" name="res_028_panel_role" value="chairman"> Chairman</label> /
            <label><input type="radio" name="res_028_panel_role" value="member"> Member</label>
            of the Panel of Examiners for the
            <input type="text" name="res_028_defense" class="w-48" placeholder="type of defense">
            of the <input type="text" name="res_028_course" class="w-40" placeholder="course"> students.
        </p>
        <div class="grid gap-3 md:grid-cols-3">
            <label>Date:<input type="date" name="res_028_defense_date" class="w-full"></label>
            <label>Time:<input type="time" name="res_028_defense_time" class="w-full"></label>
            <label>Venue:<input type="text" name="res_028_venue" class="w-full"></label>
        </div>
        <fieldset><legend class="mb-2 font-bold">Name of Students:</legend><div class="grid gap-3 md:grid-cols-2">@for ($i=1; $i<=4; $i++)<label class="flex gap-2"><span>{{ $i }}.</span><input type="text" name="res_028_students[]" class="flex-1"></label>@endfor</div></fieldset>
        <label class="block font-bold">Research Title:<input type="text" name="res_028_research_title" class="mt-1 w-full font-normal"></label>
        <div class="space-y-2 text-xs leading-5"><p>As Research Panelist, please be guided by the following:</p><ol class="list-decimal space-y-1 pl-5"><li>Review the research manuscript before the scheduled defense.</li><li>Coordinate any necessary rescheduling with the program coordinator, adviser, and other panel members.</li><li>Maintain the assigned panel composition through the required oral-defense stages whenever possible.</li><li>Discuss substantial recommendations with the adviser and other panelists for approval.</li></ol></div>
        <p>Thank you very much.</p>
        <div class="grid gap-8 pt-8 text-center md:grid-cols-3"><div class="border-t border-[#173c30] pt-1">Program Coordinator<br><small>(Name & Signature)</small></div><label><input name="res_028_panelist_signature" class="w-full text-center"><span class="block font-bold">Research Panelist Conforme<br><small>(Electronic Signature & Date)</small></span></label><div class="border-t border-[#173c30] pt-1">College Dean<br><small>(Name & Signature)</small></div></div>
    </x-student-official-form>
</div>
