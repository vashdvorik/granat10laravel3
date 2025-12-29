{{--404 в кабинете продавца--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Start: error page -->
            <div class="min-vh-100 content-center">
                <div class="error-page text-center">
                    <img src="{{ asset('cabinet/img/svg/404.svg') }}" alt="404" class="svg">
                    <div class="error-page__title">404</div>
                    <h5 class="fw-500">Извините! Страница, которую вы ищете, не существует.</h5>
                    <div class="content-center mt-30">
                        <a href="{{ route('account.index') }}" class="btn btn-primary btn-default btn-squared px-30">Вернуться в кабинет</a>
                    </div>
                </div>
            </div>
            <!-- End: error page -->
        </div>
    </div>
</div>
@endsection