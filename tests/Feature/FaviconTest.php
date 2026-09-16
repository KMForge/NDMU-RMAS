<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaviconTest extends TestCase
{
    public function test_login_page_declares_the_n_logo_as_its_tab_icon(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="icon" type="image/svg+xml"', false)
            ->assertSee(asset('favicon.svg'), false);
    }

    public function test_the_logo_icon_file_exists_and_is_not_empty(): void
    {
        $path = public_path('favicon.svg');

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function test_landing_page_uses_the_n_logo_and_favicon(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(asset('favicon.svg'), false)
            ->assertSee('ndmu-n-gold-grad', false)
            ->assertDontSee(asset('images/ndmu_logo.png'), false);
    }
}
