<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for this post.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all posts grouped by year and month with count.
     * Returns array of archives with year, month, and published count.
     */
    public static function archives()
    {
        return static::selectRaw("strftime('%Y', created_at) year, strftime('%m', created_at) month_num, COUNT(*) published")
            ->groupByRaw("strftime('%Y', created_at), strftime('%m', created_at)")
            ->orderByRaw("MIN(created_at) DESC")
            ->get()
            ->map(function ($archive) {
                $monthNum = (int) $archive->month_num;
                $monthName = date('F', mktime(0, 0, 0, $monthNum, 1));

                return [
                    'year' => $archive->year,
                    'month' => $monthName,
                    'published' => (int) $archive->published,
                ];
            })
            ->toArray();
    }

    public function addComment(mixed $body) : Comment
    {
        return $this->comments()->create([
            'user_id' => auth()->id(),
            'body' => $body,
        ]);
    }


}
