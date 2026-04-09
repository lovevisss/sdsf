<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    /**
     * Store a newly created comment on a post.
     */
    public function store(Post $post, Request $request): \Illuminate\Database\Eloquent\Model
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        return $post->comments()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);
    }

    /**
     * Store a reply to a comment.
     */
    public function storeReply(Comment $comment, Request $request): Comment
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        return $comment->post->comments()->create([
            'user_id' => auth()->id(),
            'reply_to_id' => $comment->id,
            'body' => $validated['body'],
        ]);
    }

    /**
     * Update a comment.
     */
    public function update(Comment $comment, Request $request): Comment
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment->update($validated);
        return $comment;
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): void
    {
        $comment->delete();
    }
}

