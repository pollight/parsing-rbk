@extends('layouts.app')

@section('content')

    <div class="jumbotron">
        <h1 class="mb-4">Список новостей</h1>
        <ul class="media-list pl-0">
            @foreach($news_list as $news)
                <li class="media">
                    <div class="media-body">
                        <h4 class="media-heading">
                            <a href="/news/{{$news->id}}">
                                {{$news->title}}
                            </a>
                        </h4>

                        @if($news->text)
                            {{$news->text}}...
                        @endif

                        <p>
                            <small>
                                {{$news->news_time}}
                            </small>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>

    </div>

@endsection
