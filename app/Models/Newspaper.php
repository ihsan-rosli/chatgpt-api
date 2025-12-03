<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Newspaper extends Model
{
    protected $fillable = [
        'published_date',
        'file_path',
        'file_name',
        'extracted_content',
        'content_embeddings',
        'file_size',
        'mime_type'
    ];

    protected $casts = [
        'published_date' => 'date',
        'content_embeddings' => 'array'
    ];

    public function scopeByDate($query, $day, $month, $year = null)
    {
        $query->whereDay('published_date', $day)
              ->whereMonth('published_date', $month);
        
        if ($year !== null) {
            $query->whereYear('published_date', $year);
        }
        
        return $query;
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('published_date', [$startDate, $endDate]);
    }

    public function getPublicUrlAttribute()
    {
        return asset('newspapers/sinar-harian/' . $this->file_name);
    }
}
