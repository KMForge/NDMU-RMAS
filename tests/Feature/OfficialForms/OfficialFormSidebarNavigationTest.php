<?php

namespace Tests\Feature\OfficialForms;

use Tests\TestCase;

class OfficialFormSidebarNavigationTest extends TestCase
{
    public function test_role_dashboards_switch_official_forms_with_anchored_client_side_navigation(): void
    {
        $dashboards = [
            'student-dashboard.blade.php',
            'adviser-dashboard.blade.php',
            'panelist-dashboard.blade.php',
            'facilitator-dashboard.blade.php',
        ];

        foreach ($dashboards as $dashboard) {
            $source = file_get_contents(resource_path("views/pages/{$dashboard}"));

            $this->assertIsString($source);
            $this->assertStringContainsString('selectOfficialForm(form)', $source);
            $this->assertStringContainsString('data-sidebar-anchor', $source);
            $this->assertStringContainsString('@click.prevent="selectOfficialForm(', $source);
            $this->assertStringContainsString('#official-form-{{ strtolower($code) }}', $source);
            $this->assertStringNotContainsString("\$watch('activeOfficialForm'", $source);
        }
    }

    public function test_shared_frontend_restores_the_sidebar_anchor_after_navigation(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString('window.NDMUSidebarAnchors', $source);
        $this->assertStringContainsString("document.addEventListener('livewire:navigated', () => {", $source);
        $this->assertStringContainsString('restoreSidebarAnchor();', $source);
        $this->assertStringContainsString("window.addEventListener('hashchange', restoreSidebarAnchor)", $source);
        $this->assertStringContainsString("window.Livewire?.hook('morphed'", $source);
    }

    public function test_admin_dashboard_tabs_use_restorable_sidebar_anchors(): void
    {
        $source = file_get_contents(resource_path('views/livewire/admin-dashboard-content.blade.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('id="admin-primary-navigation" wire:ignore data-portal-sidebar', $source);
        $this->assertStringNotContainsString('@click.prevent="selectAdminTab(', $source);

        foreach (['dashboard', 'users', 'permissions', 'research', 'defenses', 'repository', 'forms', 'configuration', 'audit', 'backups', 'notifications', 'settings'] as $tab) {
            $this->assertStringContainsString("id=\"admin-nav-{$tab}\"", $source);
            $this->assertStringContainsString("#admin-nav-{$tab}", $source);
        }
    }
}
