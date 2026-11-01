<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_homepage_displays_okay(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('Okay');
    }
}
