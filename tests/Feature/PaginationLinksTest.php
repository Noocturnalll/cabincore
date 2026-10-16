<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginationLinksTest extends TestCase
{
    public function test_page_links_are_absolute_even_when_the_paginator_path_has_no_leading_slash(): void
    {
        // what Livewire gives a paginator inside a component: the request path, no slash in front
        $paginator = (new LengthAwarePaginator(range(1, 30), 120, 30, 2, ['path' => 'modules/aircraft-cleaning']));

        $html = $paginator->links('pagination::tailwind')->toHtml();

        $this->assertStringContainsString('href="/modules/aircraft-cleaning?page=3"', $html);
        $this->assertStringContainsString('href="/modules/aircraft-cleaning?page=1"', $html);
        $this->assertStringNotContainsString('href="modules/', $html);
    }

    public function test_links_that_are_already_absolute_are_left_alone(): void
    {
        $paginator = (new LengthAwarePaginator(range(1, 30), 120, 30, 1, ['path' => 'https://example.test/modules/wo']));

        $this->assertStringContainsString('href="https://example.test/modules/wo?page=2"', $paginator->links('pagination::tailwind')->toHtml());
    }
}
