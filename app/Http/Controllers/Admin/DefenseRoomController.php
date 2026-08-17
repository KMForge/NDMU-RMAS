<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DefenseRoom;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DefenseRoomController extends Controller
{
    private function authorizeRoomManager(Request $request): void
    {
        $user = $request->user();
        if (! $user || $user->status !== AccountStatus::Active) {
            throw new AuthorizationException('Unauthorized access.');
        }

        $isAdmin = $user->user_type === UserType::Admin && $user->can('settings.manage');
        $isFacilitator = $user->user_type === UserType::Faculty && $user->can('defenses.manage');

        if (! $isAdmin && ! $isFacilitator) {
            throw new AuthorizationException('Unauthorized: Defense room catalog management requires System Admin or Research Facilitator permissions.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRoomManager($request);

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:defense_rooms,code'],
            'name' => ['required', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = DefenseRoom::create([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'location_notes' => isset($validated['location_notes']) ? trim($validated['location_notes']) : null,
            'is_active' => true,
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name ?? 'System',
            'actor_email' => $request->user()?->email ?? '',
            'event' => 'defense_room.created',
            'auditable_type' => DefenseRoom::class,
            'auditable_id' => $room->id,
            'description' => "Created defense room {$room->code}.",
            'new_values' => $validated,
        ]);

        return back()->with('status', 'Defense room created successfully.');
    }

    public function update(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeRoomManager($request);

        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('defense_rooms', 'code')->ignore($room->id)],
            'name' => ['required', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room->update([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'location_notes' => isset($validated['location_notes']) ? trim($validated['location_notes']) : null,
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name ?? 'System',
            'actor_email' => $request->user()?->email ?? '',
            'event' => 'defense_room.updated',
            'auditable_type' => DefenseRoom::class,
            'auditable_id' => $room->id,
            'description' => "Updated defense room {$room->code}.",
            'new_values' => $validated,
        ]);

        return back()->with('status', 'Defense room updated successfully.');
    }

    public function activate(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeRoomManager($request);

        $room->update(['is_active' => true]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name ?? 'System',
            'actor_email' => $request->user()?->email ?? '',
            'event' => 'defense_room.activated',
            'auditable_type' => DefenseRoom::class,
            'auditable_id' => $room->id,
            'description' => "Activated defense room {$room->code}.",
        ]);

        return back()->with('status', 'Defense room activated successfully.');
    }

    public function deactivate(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeRoomManager($request);

        $room->update(['is_active' => false]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'actor_name' => $request->user()?->name ?? 'System',
            'actor_email' => $request->user()?->email ?? '',
            'event' => 'defense_room.deactivated',
            'auditable_type' => DefenseRoom::class,
            'auditable_id' => $room->id,
            'description' => "Deactivated defense room {$room->code}.",
        ]);

        return back()->with('status', 'Defense room deactivated successfully.');
    }
}
