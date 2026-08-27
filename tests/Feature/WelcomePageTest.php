<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_welcome_page_contains_research_system_sections_without_marketing_navigation(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('NDMU Research Management System')
            ->assertSee('Manage research from', false)
            ->assertSee('How research moves')
            ->assertSee('Everything your research needs')
            ->assertSee('Log in to Continue');

        foreach (['home', 'about', 'achievements', 'process', 'events', 'contact'] as $section) {
            $response->assertDontSee("data-section-link=\"{$section}\"", false);
        }
    }
}
