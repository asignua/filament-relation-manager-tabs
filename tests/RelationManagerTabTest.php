<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs\Tests;

use Asignua\FilamentRelationManagerTabs\RelationManagerSection;
use Asignua\FilamentRelationManagerTabs\RelationManagerTab;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Enums\IconPosition;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Workbench\App\Filament\Resources\Teams\Pages\CreateTeam;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
use Workbench\App\Filament\Resources\Teams\Pages\TranslatableEditTeam;
use Workbench\App\Filament\Resources\Teams\Pages\ViewTeam;
use Workbench\App\Filament\Resources\Teams\RelationManagers\DeferredBadgePostsRelationManager;
use Workbench\App\Filament\Resources\Teams\RelationManagers\HiddenPostsRelationManager;
use Workbench\App\Filament\Resources\Teams\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\Team;

/**
 * The tab of a relation manager must actually OPEN: Filament pastes the tab key into Alpine
 * as a bare string (`tab = '<key>'`) in the button and double-escaped in the panel, so a key
 * with backslashes (an FQCN) opens an empty tab with no console error.
 */
class RelationManagerTabTest extends TestCase
{
    private function team(): Team
    {
        $team = Team::create(['name' => 'Core team']);
        Post::create(['team_id' => $team->id, 'title' => 'First visible post']);
        Post::create(['team_id' => $team->id, 'title' => 'Second visible post']);

        return $team;
    }

    private function editHtml(): string
    {
        return Livewire::test(EditTeam::class, ['record' => $this->team()->getRouteKey()])->html();
    }

    /**
     * @return array<int, string>
     */
    private function buttonKeys(string $html): array
    {
        preg_match_all('/x-on:click="tab = \'([^\']*)\'"/', $html, $matches);

        return $matches[1];
    }

    public function test_tab_button_keys_contain_no_backslash(): void
    {
        $keys = $this->buttonKeys($this->editHtml());

        $this->assertNotEmpty($keys);

        foreach ($keys as $key) {
            $this->assertStringNotContainsString('\\', $key);
        }
    }

    public function test_button_keys_and_panel_keys_are_the_same_set(): void
    {
        $html = $this->editHtml();

        preg_match_all('/\'fi-active\': tab === \'([^\']*)\'/', $html, $panels);

        $panelKeys = array_values(array_unique($panels[1]));
        $buttonKeys = array_values(array_unique($this->buttonKeys($html)));

        sort($panelKeys);
        sort($buttonKeys);

        $this->assertNotEmpty($buttonKeys);
        $this->assertSame($buttonKeys, $panelKeys);
    }

    public function test_tab_is_absent_on_the_create_page(): void
    {
        $html = Livewire::test(CreateTeam::class)->html();

        $this->assertStringNotContainsString('relation-manager-postsrelationmanager', $html);
        $this->assertNotEmpty($this->buttonKeys($html));

        foreach ($this->buttonKeys($html) as $key) {
            $this->assertStringNotContainsString('relation-manager', $key);
        }
    }

    public function test_tab_is_present_on_the_edit_page(): void
    {
        $this->assertContains('relation-manager-postsrelationmanager', $this->buttonKeys($this->editHtml()));
    }

    public function test_tab_is_hidden_when_the_manager_cannot_be_viewed(): void
    {
        $html = $this->editHtml();

        $this->assertStringNotContainsString('relation-manager-hiddenpostsrelationmanager', $html);
        $this->assertStringNotContainsString('Secret posts', $html);
    }

    public function test_default_label_is_the_manager_title(): void
    {
        $team = $this->team();
        $title = PostsRelationManager::getTitle($team, EditTeam::class);

        $this->assertSame('Posts', $title);
        $this->assertStringContainsString($title, Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->html());
    }

    public function test_custom_key_is_used_verbatim(): void
    {
        $tab = RelationManagerTab::make(PostsRelationManager::class, key: 'my-posts');

        $this->assertInstanceOf(Tabs\Tab::class, $tab);
        $this->assertSame('my-posts', $tab->getKey(isAbsolute: false));
    }

    public function test_default_key_is_a_slug_of_the_class_name(): void
    {
        $key = RelationManagerTab::make(PostsRelationManager::class)->getKey(isAbsolute: false);

        $this->assertSame('relation-manager-postsrelationmanager', $key);
        $this->assertStringNotContainsString('\\', (string) $key);
    }

    public function test_relation_manager_component_is_mounted_inside_the_edit_page(): void
    {
        $html = $this->editHtml();

        $this->assertStringContainsString('RelationManagers\\PostsRelationManager', $html);
        $this->assertStringNotContainsString('HiddenPostsRelationManager', $html);
    }

    public function test_relation_manager_lists_the_related_records(): void
    {
        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $this->team(),
            'pageClass' => EditTeam::class,
        ])
            ->assertSee('First visible post')
            ->assertSee('Second visible post');
    }

    public function test_many_returns_one_tab_per_manager(): void
    {
        $tabs = RelationManagerTab::many([PostsRelationManager::class, HiddenPostsRelationManager::class]);

        $this->assertCount(2, $tabs);
        $this->assertContainsOnlyInstancesOf(Tabs\Tab::class, $tabs);
        $this->assertSame('relation-manager-postsrelationmanager', $tabs[0]->getKey(isAbsolute: false));
        $this->assertSame('relation-manager-hiddenpostsrelationmanager', $tabs[1]->getKey(isAbsolute: false));
    }

    public function test_tab_shows_the_manager_badge_color_and_tooltip(): void
    {
        $tab = $this->editTab(PostsRelationManager::class);

        $this->assertSame('2', $tab->getBadge());
        $this->assertSame('success', $tab->getBadgeColor());
        $this->assertSame('Published posts', $tab->getBadgeTooltip());
    }

    public function test_tab_works_on_the_view_page(): void
    {
        $html = Livewire::test(ViewTeam::class, ['record' => $this->team()->getRouteKey()])->html();

        $this->assertContains('relation-manager-postsrelationmanager', $this->buttonKeys($html));
        $this->assertContains('overview::tab', $this->buttonKeys($html)); // the infolist's own tab
        $this->assertStringContainsString('RelationManagers\\PostsRelationManager', $html);
    }

    public function test_deferred_badge_is_not_computed_with_the_page(): void
    {
        $team = $this->team();
        DeferredBadgePostsRelationManager::$badgeCalls = 0;

        $component = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()]);

        $this->assertStringNotContainsString('late#', $component->html());
        $this->assertSame(0, DeferredBadgePostsRelationManager::$badgeCalls);

        /** @var Tabs $tabs */
        $tabs = $component->instance()->form->getComponents()[0];
        $badges = $tabs->getDeferredTabBadges();

        // only the deferred tab is fetched later, and it gets its real badge then
        $this->assertCount(1, $badges, implode(', ', array_keys($badges)));
        $this->assertSame('late#2', array_values($badges)[0]['badge']);
    }

    public function test_tab_takes_the_icon_position_from_the_manager(): void
    {
        $this->assertSame(IconPosition::After, $this->editTab(DeferredBadgePostsRelationManager::class)->getIconPosition());
        $this->assertSame(IconPosition::Before, $this->editTab(PostsRelationManager::class)->getIconPosition());
    }

    public function test_active_locale_of_the_page_reaches_the_manager(): void
    {
        $team = $this->team();

        $translatable = Livewire::test(TranslatableEditTeam::class, ['record' => $team->getRouteKey()])->instance();
        $component = RelationManagerTab::livewire(PostsRelationManager::class);
        $component->container($translatable->form);
        $this->assertSame('uk', $component->getData()['activeLocale'] ?? null);

        $plain = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->instance();
        $component = RelationManagerTab::livewire(PostsRelationManager::class);
        $component->container($plain->form);
        $this->assertArrayNotHasKey('activeLocale', $component->getData());
    }

    public function test_configured_manager_passes_its_properties(): void
    {
        $team = $this->team();
        $page = Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->instance();

        $tab = RelationManagerTab::make(PostsRelationManager::make(['tableSearch' => 'First']));
        $this->assertSame('relation-manager-postsrelationmanager', $tab->getKey(isAbsolute: false));

        $component = RelationManagerTab::livewire(PostsRelationManager::make(['tableSearch' => 'First']), lazy: false);
        $component->container($page->form);
        $data = $component->getData();

        $this->assertSame('First', $data['tableSearch']);
        $this->assertSame(EditTeam::class, $data['pageClass']);
        $this->assertArrayNotHasKey('lazy', $data);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidKeys(): array
    {
        return [
            'backslash' => ['App\\Posts'],
            'quote' => ["posts' + alert(1) + '"],
            'double quote' => ['my "posts"'],
            'backtick' => ['posts`'],
            'angle bracket' => ['<posts>'],
            'line break' => ["posts\nmore"],
            'empty' => [''],
        ];
    }

    /**
     * Keys v1.0.0 accepted and Filament renders unchanged — a minor release must keep them.
     *
     * @return array<string, array{0: string}>
     */
    public static function validKeys(): array
    {
        return [
            'slug' => ['posts_v2.tab'],
            'space' => ['my posts'],
            'colon' => ['posts::tab'],
            'non-ascii' => ['пости'],
            'ampersand' => ['posts&comments'],
        ];
    }

    #[DataProvider('validKeys')]
    public function test_a_key_that_worked_in_v1_is_still_accepted(string $key): void
    {
        $this->assertSame($key, RelationManagerTab::make(PostsRelationManager::class, key: $key)->getKey(isAbsolute: false));
    }

    #[DataProvider('validKeys')]
    public function test_a_section_key_that_worked_in_v1_is_still_accepted(string $key): void
    {
        $this->assertSame($key, RelationManagerSection::make(PostsRelationManager::class, key: $key)->getKey(isAbsolute: false));
    }

    #[DataProvider('invalidKeys')]
    public function test_a_key_alpine_cannot_take_is_rejected(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        RelationManagerTab::make(PostsRelationManager::class, key: $key);
    }

    #[DataProvider('invalidKeys')]
    public function test_a_section_key_alpine_cannot_take_is_rejected(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);

        RelationManagerSection::make(PostsRelationManager::class, key: $key);
    }

    /**
     * The Boost guideline is read by coding agents: it must state the key rule the code
     * enforces, not the stricter slug-only rule of v1.0.0.
     */
    public function test_the_boost_guideline_states_the_enforced_key_rule(): void
    {
        $guideline = (string) file_get_contents(dirname(__DIR__).'/resources/boost/guidelines/core.blade.php');

        $this->assertStringNotContainsString('[A-Za-z0-9_.-]', $guideline);

        foreach (['empty', 'backslash', 'quote', 'backtick', 'angle bracket', 'control character'] as $rule) {
            $this->assertStringContainsString($rule, $guideline);
        }
    }

    /**
     * The evaluated tab as the Edit page sees it — badge closures need the record and the page.
     */
    private function editTab(string $manager): Tabs\Tab
    {
        $component = Livewire::test(EditTeam::class, ['record' => $this->team()->getRouteKey()]);

        /** @var Tabs $tabs */
        $tabs = $component->instance()->form->getComponents()[0];

        foreach ($tabs->getChildSchema()->getComponents() as $tab) {
            if ($tab instanceof Tabs\Tab && $tab->getKey(isAbsolute: false) === 'relation-manager-'.strtolower(class_basename($manager))) {
                return $tab;
            }
        }

        $this->fail('Tab for '.$manager.' not found');
    }
}
