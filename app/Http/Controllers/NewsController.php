<?php
namespace App\Http\Controllers;

use App\Models\News;
use DB;

class NewsController extends Controller
{
    public function index() {
        $news_list = News::query()
            ->select([
                'id',
                'title',
                'partners_news',
                DB::raw('SUBSTR(`text`, 1, 200) as text'),
                'news_time',
                'original_link'
            ])
            ->orderBy('news_time', 'desc')
            ->limit(15)
            ->get();

        return view('index', compact('news_list'));
    }

    public function show($id) {
        $news = News::query()
            ->select([
                'id',
                'title',
                'text',
                'news_time',
                'original_link',
                'img'
            ])
            ->where('id', $id)
            ->first();

        if(!$news)
            return abort(404);

        return view('news', compact('news'));
    }
}
