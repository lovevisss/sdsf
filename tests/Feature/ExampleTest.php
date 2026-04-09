<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response()
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    }

    public function test_post()
    {
        $first = Post::factory()->create();
        $second = Post::factory()->create([
            'created_at' => now()->submonth()
        ]);

        $posts = Post::archives();

        $this->assertCount(2, $posts);

        $this->assertEquals(
            [
                [
                    "year" => $first->created_at->format('Y'),
                    "month" => $first->created_at->format('F'),
                    "published" => 1
                ],
                [
                    "year" => $second->created_at->format('Y'),
                    "month" => $second->created_at->format('F'),
                    "published" => 1
                ]
            ], $posts
        );
    }
}
