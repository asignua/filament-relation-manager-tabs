<?php

declare(strict_types=1);

namespace Asignua\FilamentRelationManagerTabs\Tests;

use Asignua\FilamentRelationManagerTabs\RelationManagerTab;
use Filament\Schemas\Components\Tabs;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\Teams\Pages\CreateTeam;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
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
}
