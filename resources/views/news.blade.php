@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li><a href="/">Главная</a> <span>></span> </li>
        <li class="active">{{$news->title}}</li>
    </ol>
    <h1>{{$news->title}}</h1>
    <div class="d-flex justify-content-between">
        <div class="pull-left">
            {{$news->news_time}}
        </div>
        <div class="pull-right">
            <a href="{{$news->original_link}}" target="_blank">
                ссылка на оригинал
            </a>
        </div>
    </div>
    @if($news->title)
        <div class="text-center d-flex justify-content-center image">
            <img src="{{$news->img}}" alt="" width="80%">
        </div>
    @endif
    <p class="text-justify">
        {{$news->text}}
    </p>

@endsection
