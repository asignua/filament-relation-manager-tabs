<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs\Tests;

use Asignua\FilamentRelationManagerTabs\RelationManagerSection;
use Asignua\FilamentRelationManagerTabs\RelationManagerTab;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\Teams\Pages\CreateTeam;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
use Workbench\App\Filament\Resources\Teams\RelationManagers\HiddenPostsRelationManager;
use Workbench\App\Filament\Resources\Teams\RelationManagers\LazyPostsRelationManager;
use Workbench\App\Filament\Resources\Teams\RelationManagers\SectionPostsRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\Team;

class LazyAndSectionTest extends TestCase
{
    private function team(): Team
    {
        $team = Team::create(['name' => 'Core team']);
        Post::create(['team_id' => $team->id, 'title' => 'Lazy visible post']);

        return $team;
    }

    /**
     * Queries that LIST posts (the table body) — the badge's `count(*)` is not one of them.
     *
     * @return array<int, string>
     */
    private function listingQueries(callable $render): array
    {
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            if (preg_match('/^select\s+(?!count)/i', $query->sql) && str_contains($query->sql, '"posts"')) {
                $queries[] = $query->sql;
            }
        });

        $render();

        return $queries;
    }

    /**
     * The first `<tag …>` carrying the manager's snapshot in the page HTML.
     */
    private function segment(string $html, string $manager): string
    {
        $position = strpos($html, 'RelationManagers\\'.$manager.'" wire:snapshot');
        $this->assertNotFalse($position, $manager.' is not in the page');

        $end = strpos($html, 'wire:name', $position);

        return substr($html, $position, ($end === false ? 3000 : $end - $position + 20000));
    }

    private function isPlaceholder(string $html, string $manager): bool
    {
        return str_contains($this->segment($html, $manager), '__lazyLoad');
    }

    public function test_lazy_tab_section_and_eager_tab_render_as_requested(): void
    {
        $team = $this->team();
        $html = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->html();

        // default tab: follows the manager (Filament's default is lazy)
        $this->assertTrue($this->isPlaceholder($html, 'PostsRelationManager'));
        // manager says $isLazy = false, tab says lazy: true
        $this->assertTrue($this->isPlaceholder($html, 'LazyPostsRelationManager'));
        // manager says lazy, tab says lazy: false
        $this->assertFalse($this->isPlaceholder($html, 'ForcedEagerPostsRelationManager'));
        // manager says $isLazy = false, the section is lazy by default
        $this->assertTrue($this->isPlaceholder($html, 'SectionPostsRelationManager'));
    }

    public function test_lazy_placeholders_run_no_listing_query_and_activation_does(): void
    {
        $team = $this->team();
        $page = null;

        $initial = $this->listingQueries(function () use ($team, &$page): void {
            $page = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()]);
        });

        // Only the eagerly mounted manager may list posts on the initial render.
        $this->assertLessThanOrEqual(1, count($initial));

        $html = $page->html();
        // exactly one table (the eager one) has rendered its rows
        $this->assertSame(1, substr_count($html, 'Lazy visible post'));

        preg_match("/__lazyLoad\\('([^']*)'\\)/", html_entity_decode($this->segment($html, 'SectionPostsRelationManager'), ENT_QUOTES), $matches);
        $this->assertNotEmpty($matches[1]);

        // The placeholder carries everything needed to mount the real component on activation.
        $payload = json_decode((string) base64_decode($matches[1], true), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('ownerRecord', $payload['data']['forMount'][0]);
        $this->assertSame(EditTeam::class, $payload['data']['forMount'][0]['pageClass']);
    }

    public function test_activated_manager_lists_the_records(): void
    {
        $team = $this->team();

        $queries = $this->listingQueries(function () use ($team): void {
            Livewire::test(SectionPostsRelationManager::class, [
                'ownerRecord' => $team,
                'pageClass' => EditTeam::class,
            ])->assertSee('Lazy visible post');
        });

        $this->assertNotEmpty($queries);
    }

    public function test_section_is_collapsible_and_collapsed_by_default(): void
    {
        $team = $this->team();
        $html = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->html();

        $this->assertStringContainsString('id="relation-manager-sectionpostsrelationmanager"', $html);
        $this->assertStringContainsString('Section posts', $html);
        $this->assertMatchesRegularExpression('/isCollapsed:\s*true/', $html);
        $this->assertStringContainsString('fi-collapsible', $html);
    }

    public function test_section_options_are_applied(): void
    {
        $section = RelationManagerSection::make(SectionPostsRelationManager::class, collapsed: false, key: 'my-section');

        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame('my-section', $section->getKey(isAbsolute: false));
        $this->assertTrue($section->isCollapsible());
        $this->assertFalse($section->isCollapsed());
        $this->assertTrue(RelationManagerSection::make(SectionPostsRelationManager::class)->isCollapsed());
    }

    public function test_section_key_is_a_slug(): void
    {
        $key = RelationManagerSection::make(SectionPostsRelationManager::class)->getKey(isAbsolute: false);

        $this->assertSame('relation-manager-sectionpostsrelationmanager', $key);
        $this->assertStringNotContainsString('\\', (string) $key);
    }

    public function test_section_is_absent_on_the_create_page(): void
    {
        $html = Livewire::test(CreateTeam::class)->html();

        $this->assertStringNotContainsString('SectionPostsRelationManager', $html);
        $this->assertStringNotContainsString('Section posts', $html);
    }

    public function test_section_respects_can_view_for_record(): void
    {
        $team = $this->team();
        $page = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->instance();

        $section = RelationManagerSection::make(HiddenPostsRelationManager::class);
        $section->container($page->form);

        $this->assertFalse($section->isVisible());
    }

    public function test_section_shows_the_manager_badge(): void
    {
        $team = $this->team();
        $html = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->html();

        $this->assertStringContainsString('fi-badge', $html);
    }

    public function test_tab_livewire_component_is_built(): void
    {
        $this->assertInstanceOf(LivewireComponent::class, RelationManagerTab::livewire(LazyPostsRelationManager::class, true));
    }
}
