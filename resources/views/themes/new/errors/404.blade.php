@extends('main_layots')

@section('title', 'Страница не найдена — BIMM')

@section('content')
<div class="bimm-empty" style="max-width:640px;margin:60px auto"><span class="bimm-kicker">404</span><h1 style="margin:0 0 12px;font-size:42px">Кажется, страница потерялась.</h1><p>Попробуйте вернуться в каталог или начать поиск заново.</p><a class="bimm-button bimm-button--dark" href="{{ $regionalUrl->route('home') }}">На главную</a></div>
@endsection
