<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_comment_on_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_create_reply_to_comment(): void
    {
        $this->signIn();
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = $post->addComment('foo comment');
        $comment->reply('reply to foo comment', $user->id);
        $this->assertCount(1, $comment->replies);
    }

    public function test_post_has_many_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertCount(3, $post->comments);
    }

    public function test_comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    public function test_comment_belongs_to_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertInstanceOf(Post::class, $comment->post);
        $this->assertEquals($post->id, $comment->post->id);
    }

    public function test_comment_has_replies(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reply_to_id' => $comment->id,
        ]);

        $this->assertCount(2, $comment->replies);
    }

    public function test_reply_belongs_to_parent_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        $reply = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reply_to_id' => $comment->id,
        ]);
        $this->assertNull($comment->parent);

        $this->assertTrue( $reply->parent->is($comment));
    }

    public function test_store_comment_on_post(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create();

        $this->post("/posts/{$post->id}/comments", [
            'body' => 'This is a great post!',
        ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body' => 'This is a great post!',
        ]);
    }

    public function test_store_reply_to_comment(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->post("/comments/{$comment->id}/replies", [
            'body' => 'Great response!',
        ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reply_to_id' => $comment->id,
            'body' => 'Great response!',
        ]);
    }

    public function test_store_comment_fails_without_body(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create();

        $this->post("/posts/{$post->id}/comments", [])
            ->assertInvalid(['body']);
    }

    public function test_update_comment(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->patch("/comments/{$comment->id}", [
            'body' => 'Updated comment text',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Updated comment text',
        ]);
    }

    public function test_delete_comment(): void
    {
        $user = $this->signIn();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->delete("/comments/{$comment->id}");

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_cascade_delete_comments_with_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comments = Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $post->delete();

        foreach ($comments as $comment) {
            $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        }
    }

    public function test_cascade_delete_replies_with_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
        $replies = Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'reply_to_id' => $comment->id,
        ]);

        $comment->delete();

        foreach ($replies as $reply) {
            $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
        }
    }

    public function test_comment_has_required_attributes(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'body' => 'Test comment',
        ]);

        $this->assertEquals($user->id, $comment->user_id);
        $this->assertEquals($post->id, $comment->post_id);
        $this->assertNull($comment->reply_to_id);
        $this->assertEquals('Test comment', $comment->body);
    }
}

