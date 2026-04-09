<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for Post model, factory, and controller functionality.
 */
class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a post with factory.
     */
    public function test_create_post_with_factory(): void
    {
        $post = Post::factory()->create();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'user_id' => $post->user_id,
        ]);
        $this->assertNotEmpty($post->title);
        $this->assertNotEmpty($post->body);
    }

    /**
     * Test creating multiple posts.
     */
    public function test_create_multiple_posts(): void
    {
        $posts = Post::factory()->count(5)->create();

        $this->assertCount(5, $posts);
        $this->assertCount(5, Post::all());
    }

    /**
     * Test creating a post with specific user.
     */
    public function test_create_post_with_specific_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $post->user_id);
        $this->assertTrue($post->user->is($user));
    }

    /**
     * Test post belongs to user relationship.
     */
    public function test_post_belongs_to_user(): void
    {
        $post = Post::factory()->create();

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($post->user_id, $post->user->id);
    }

    /**
     * Test user has many posts relationship.
     */
    public function test_user_has_many_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->posts);
        $this->assertTrue($user->posts->every(fn ($post) => $post->user_id === $user->id));
    }

    /**
     * Test store post via controller.
     */
    public function test_store_post(): void
    {
        $user = $this->signIn();

        $response = $this->post('/posts', [
            'title' => 'Test Post Title',
            'body' => 'This is the test post body content.',
        ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'Test Post Title',
            'body' => 'This is the test post body content.',
        ]);
    }

    /**
     * Test store post validation fails without title.
     */
    public function test_store_post_fails_without_title(): void
    {
        $user = $this->signIn();

        $this->post('/posts', [
            'body' => 'This is the test post body content.',
        ])->assertInvalid(['title']);
    }

    /**
     * Test store post validation fails without body.
     */
    public function test_store_post_fails_without_body(): void
    {
        $user = $this->signIn();

        $this->post('/posts', [
            'title' => 'Test Post Title',
        ])->assertInvalid(['body']);
    }

    /**
     * Test update post.
     */
    public function test_update_post(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->patch("/posts/{$post->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated body content.',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'body' => 'Updated body content.',
        ]);
    }

    /**
     * Test delete post.
     */
    public function test_delete_post(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->delete("/posts/{$post->id}");

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * Test cascade delete removes posts when user is deleted.
     */
    public function test_cascade_delete_posts_with_user(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $user->delete();

        foreach ($posts as $post) {
            $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        }
    }

    /**
     * Test post has title and body attributes.
     */
    public function test_post_has_required_attributes(): void
    {
        $post = Post::factory()->create([
            'title' => 'Sample Title',
            'body' => 'Sample body content.',
        ]);

        $this->assertEquals('Sample Title', $post->title);
        $this->assertEquals('Sample body content.', $post->body);
        $this->assertIsInt($post->user_id);
    }

    public function test_a_post_may_receive_comments()
    {
        $user = $this->signIn();
        $post = Post::factory()->create();

        $this->post("/posts/{$post->id}/comments", [
            'body' => 'A comment body'
        ]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'body' => 'A comment body',
        ]);
    }

    public function test_a_comment_may_be_replied_to()
    {
        $user = $this->signIn();
        $post = Post::factory()->create();

        $comment = $post->addComment('A comment body');

        $this->post('/comments/' . $comment->id . '/replies', [
            'body' => 'A reply body',
        ]);

        $this->assertCount(2, $post->comments);
        $this->assertEquals('A reply body', $post->comments->last()->body);
    }
}

