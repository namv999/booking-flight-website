<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    private function ids($response, string $key = 'articles'): array
    {
        return $response->viewData($key)->pluck('id')->all();
    }

    public function test_index_lists_only_published_articles(): void
    {
        $live = Article::factory()->create();
        Article::factory()->draft()->create();
        Article::factory()->create(['published_at' => now()->addDay()]); // hẹn giờ chưa tới

        $this->assertSame([$live->id], $this->ids($this->get(route('news.index'))->assertOk()));
    }

    public function test_search_matches_title_excerpt_and_content(): void
    {
        $byTitle = Article::factory()->create(['title' => 'Bangkok cuối tuần']);
        $byContent = Article::factory()->create(['content' => 'Chợ nổi rất đáng ghé']);
        Article::factory()->create(['title' => 'Chủ đề khác', 'excerpt' => 'Không liên quan', 'content' => 'Không liên quan']);

        $this->assertSame([$byTitle->id], $this->ids($this->get(route('news.index', ['search' => 'Bangkok']))));
        $this->assertSame([$byContent->id], $this->ids($this->get(route('news.index', ['search' => 'chợ nổi']))));
    }

    public function test_category_filter_and_unknown_category_is_ignored(): void
    {
        $promo = Article::factory()->create(['category' => 'promotion']);
        $tips = Article::factory()->create(['category' => 'booking-tips']);

        $this->assertSame([$promo->id], $this->ids($this->get(route('news.index', ['category' => 'promotion']))));
        $this->assertEqualsCanonicalizing(
            [$promo->id, $tips->id],
            $this->ids($this->get(route('news.index', ['category' => 'hack'])))
        );
    }

    public function test_pagination_keeps_query_string(): void
    {
        Article::factory()->count(10)->create(['category' => 'promotion']);

        $response = $this->get(route('news.index', ['category' => 'promotion']));
        $articles = $response->viewData('articles');

        $this->assertCount(9, $articles);
        $this->assertStringContainsString('category=promotion', $articles->nextPageUrl());
    }

    public function test_category_counts_only_count_published(): void
    {
        Article::factory()->count(2)->create(['category' => 'promotion']);
        Article::factory()->draft()->create(['category' => 'promotion']);

        $counts = $this->get(route('news.index'))->viewData('categoryCounts');

        $this->assertSame(2, (int) $counts['promotion']);
    }

    public function test_show_renders_markdown_and_strips_raw_html(): void
    {
        $article = Article::factory()->create([
            'content' => "**đậm** <script>alert(1)</script>\n\n[link](javascript:alert(1))",
        ]);

        $response = $this->get(route('news.show', $article->slug))->assertOk();

        $response->assertSee('<strong>đậm</strong>', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('href="javascript:', false);
    }

    public function test_show_hides_draft_and_scheduled_articles(): void
    {
        $draft = Article::factory()->draft()->create();
        $future = Article::factory()->create(['published_at' => now()->addDay()]);

        $this->get(route('news.show', $draft->slug))->assertNotFound();
        $this->get(route('news.show', $future->slug))->assertNotFound();
        $this->get('/news/khong-ton-tai')->assertNotFound();
    }

    public function test_show_increments_view_count_without_touching_updated_at(): void
    {
        $article = Article::factory()->create(['updated_at' => now()->subDays(5)]);
        $before = $article->fresh()->updated_at->toDateTimeString();

        $this->get(route('news.show', $article->slug))->assertOk();
        $this->get(route('news.show', $article->slug))->assertOk();

        $fresh = $article->fresh();
        $this->assertSame(2, $fresh->view_count);
        $this->assertSame($before, $fresh->updated_at->toDateTimeString());
    }

    public function test_related_articles_share_category_and_exclude_self(): void
    {
        $main = Article::factory()->create(['category' => 'promotion']);
        $same = Article::factory()->create(['category' => 'promotion']);
        Article::factory()->create(['category' => 'booking-tips']);
        Article::factory()->draft()->create(['category' => 'promotion']);

        $related = $this->get(route('news.show', $main->slug))->viewData('related');

        $this->assertSame([$same->id], $related->pluck('id')->all());
    }

    public function test_home_shows_published_articles_featured_first(): void
    {
        $normal = Article::factory()->create(['published_at' => now()->subHour()]);
        $featured = Article::factory()->featured()->create(['published_at' => now()->subDays(3)]);
        Article::factory()->draft()->create();

        $response = $this->get(route('home'))->assertOk();

        $this->assertSame([$featured->id, $normal->id], $this->ids($response, 'homeArticles'));
        $this->assertEqualsCanonicalizing(
            $response->viewData('homeArticles')->pluck('category')->unique()->all(),
            $response->viewData('homeCategories')->keys()->all()
        );
    }
}
