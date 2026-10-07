@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@include('backend.cabinet.partials.flash')

<div class="cabinet-error-page">
    <div class="cabinet-error-page__card text-center">
        <img src="{{ asset('cabinet/img/svg/404.svg') }}" alt="404" class="cabinet-error-page__icon mb-4">
        <h1 class="cabinet-error-page__title">404</h1>
        <h5 class="fw-600 mt-3 mb-3">Страница не найдена</h5>
        <p class="cabinet-error-page__text">
            Возможно, ссылка устарела или страница была удалена. Вернитесь в кабинет и продолжайте работу оттуда.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <a href="{{ route('account.index') }}" class="btn btn-primary btn-default btn-squared px-30">
                Вернуться в кабинет
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-light btn-default btn-squared px-30">
                Назад
            </a>
        </div>
    </div>
</div>
@endsection
