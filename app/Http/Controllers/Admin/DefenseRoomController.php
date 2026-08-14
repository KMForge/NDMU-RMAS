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
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || $user->user_type !== UserType::Admin || $user->status !== AccountStatus::Active || ! $user->can('settings.manage')) {
            throw new AuthorizationException('Unauthorized: Room catalog management requires Admin role with settings.manage capability.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:defense_rooms,code'],
            'name' => ['required', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = DefenseRoom::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'location_notes' => isset($validated['location_notes']) ? trim($validated['location_notes']) : null,
            'is_active' => true,
        ]);

        AuditLog::record($request->user(), 'defense_room.created', $room, $validated);

        return back()->with('status', 'Defense room created successfully.');
    }

    public function update(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('defense_rooms', 'code')->ignore($room->id)],
            'name' => ['required', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'location_notes' => isset($validated['location_notes']) ? trim($validated['location_notes']) : null,
        ]);

        AuditLog::record($request->user(), 'defense_room.updated', $room, $validated);

        return back()->with('status', 'Defense room updated successfully.');
    }

    public function activate(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $room->update(['is_active' => true]);

        AuditLog::record($request->user(), 'defense_room.activated', $room);

        return back()->with('status', 'Defense room activated successfully.');
    }

    public function deactivate(Request $request, DefenseRoom $room): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $room->update(['is_active' => false]);

        AuditLog::record($request->user(), 'defense_room.deactivated', $room);

        return back()->with('status', 'Defense room deactivated successfully.');
    }
}
