<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of posts.
     */
    public function index(): array
    {
        return [
            'posts' => Post::query()
                ->with('user')
                ->latest()
                ->get(),
        ];
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request): Post
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        return auth()->user()->posts()->create($validated);
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post): array
    {
        return [
            'post' => $post->load('user'),
        ];
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified post in storage.
     */
    public function update(Request $request, Post $post): Post
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $post->update($validated);
        return $post;
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(Post $post): void
    {
        $post->delete();
    }
}
