<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_welcome_page_contains_scrollspy_navigation_and_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('data-welcome-page', false)
            ->assertSee('data-site-header', false)
            ->assertSee('data-scroll-progress', false);

        foreach (['home', 'about', 'achievements', 'process', 'events', 'contact'] as $section) {
            $response
                ->assertSee("data-section-link=\"{$section}\"", false)
                ->assertSee("id=\"{$section}\"", false)
                ->assertSee('data-scroll-section', false);
        }
    }
}
