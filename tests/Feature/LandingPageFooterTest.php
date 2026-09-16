<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageFooterTest extends TestCase
{
    public function test_landing_page_footer_is_a_single_copyright_strip(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('text-center text-xs text-white', false)
            ->assertSee('Notre Dame of Marbel University. All rights reserved.')
            ->assertDontSee('Footer navigation', false)
            ->assertDontSee('Research journey', false);
    }
}
