<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Kinh nghiệm bay lần đầu',
            'excerpt' => 'Tóm tắt ngắn của bài viết.',
            'content' => "## Mở đầu\n\nNội dung **markdown**.",
            'category' => 'travel-guide',
            'status' => 'published',
        ], $override);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->create('thumb.jpg', 100, 'image/jpeg');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('admin.articles.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('admin.articles.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.articles.store'), $this->payload())->assertForbidden();
    }

    public function test_index_search_and_filters(): void
    {
        $admin = $this->admin();
        $a = Article::factory()->create(['title' => 'Bangkok cuối tuần', 'category' => 'travel-guide']);
        $b = Article::factory()->create(['title' => 'Ưu đãi mùa hè', 'category' => 'promotion']);
        $c = Article::factory()->draft()->create(['title' => 'Bản nháp riêng', 'category' => 'promotion']);

        $ids = fn (array $q) => $this->actingAs($admin)
            ->get(route('admin.articles.index', $q))->assertOk()
            ->viewData('articles')->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $ids([]));
        $this->assertSame([$a->id], $ids(['search' => 'Bangkok']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['category' => 'promotion']));
        $this->assertSame([$c->id], $ids(['status' => 'draft']));
    }

    public function test_store_creates_article_with_slug_author_and_thumbnail(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), $this->payload(['thumbnail' => $this->image(), 'is_featured' => 1]))
            ->assertRedirect(route('admin.articles.index'));

        $article = Article::firstOrFail();
        $this->assertSame('kinh-nghiem-bay-lan-dau', $article->slug);
        $this->assertSame($admin->id, $article->user_id);
        $this->assertTrue($article->is_featured);
        $this->assertNotNull($article->published_at);
        Storage::disk('public')->assertExists($article->thumbnail_path);
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload());
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload());

        $this->assertEqualsCanonicalizing(
            ['kinh-nghiem-bay-lan-dau', 'kinh-nghiem-bay-lan-dau-2'],
            Article::pluck('slug')->all()
        );
    }

    public function test_draft_has_no_published_at_and_unchecked_featured_is_false(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.articles.store'), $this->payload(['status' => 'draft', 'published_at' => now()->toDateTimeString()]));

        $article = Article::firstOrFail();
        $this->assertNull($article->published_at);
        $this->assertFalse($article->is_featured);
    }

    public function test_store_validation(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload([
            'title' => '', 'category' => 'khong-co', 'status' => 'archived',
            'thumbnail' => UploadedFile::fake()->create('big.jpg', 5000, 'image/jpeg'),
        ]))->assertSessionHasErrors(['title', 'category', 'status', 'thumbnail']);

        $this->assertSame(0, Article::count());
    }

    public function test_update_keeps_slug_and_replaces_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/old.jpg', 'x');
        $article = Article::factory()->create(['slug' => 'giu-nguyen', 'thumbnail_path' => 'articles/old.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.articles.update', $article), $this->payload(['title' => 'Tiêu đề mới', 'thumbnail' => $this->image()]))
            ->assertRedirect(route('admin.articles.index'));

        $article->refresh();
        $this->assertSame('giu-nguyen', $article->slug);
        $this->assertSame('Tiêu đề mới', $article->title);
        Storage::disk('public')->assertMissing('articles/old.jpg');
        Storage::disk('public')->assertExists($article->thumbnail_path);
    }

    public function test_update_can_remove_thumbnail(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/old.jpg', 'x');
        $article = Article::factory()->create(['thumbnail_path' => 'articles/old.jpg']);

        $this->actingAs($this->admin())
            ->put(route('admin.articles.update', $article), $this->payload(['remove_thumbnail' => 1]));

        $this->assertNull($article->fresh()->thumbnail_path);
        Storage::disk('public')->assertMissing('articles/old.jpg');
    }

    public function test_destroy_deletes_row_and_uploaded_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/old.jpg', 'x');
        $article = Article::factory()->create(['thumbnail_path' => 'articles/old.jpg']);

        $this->actingAs($this->admin())
            ->delete(route('admin.articles.destroy', $article))
            ->assertRedirect(route('admin.articles.index'));

        $this->assertModelMissing($article);
        Storage::disk('public')->assertMissing('articles/old.jpg');
    }
}
