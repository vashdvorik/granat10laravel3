@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents store-edit-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Удаление магазина</h4>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xxl-7 col-lg-8">
                <div class="card mb-25 store-edit-card">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">Перед удалением проверьте последствия</h6>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="alert alert-warning product-form-callout product-form-callout--warning mb-4">
                            <div class="d-flex align-items-start gap-2">
                                <i class="uil uil-exclamation-triangle product-form-callout__icon text-warning mt-1"></i>
                                <div>
                                    <strong>Внимание.</strong>
                                    <div>Это действие необратимо. Магазин, товары, тариф и связанные данные будут удалены.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 bg-light-subtle h-100">
                                    <small class="text-muted d-block mb-1">Магазин</small>
                                    <strong class="d-block">{{ $user->seller->name }}</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-4 bg-light-subtle h-100">
                                    <small class="text-muted d-block mb-1">Товаров</small>
                                    <strong class="d-block">{{ $user->seller->products()->count() }}</strong>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('account.store.destroy') }}">
                            @csrf
                            @method('DELETE')

                            <div class="form-group mb-20">
                                <label for="password" class="form-label">Введите пароль для подтверждения</label>
                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    required
                                >
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="store-edit-submit-bar">
                                <a href="{{ route('account.index') }}" class="btn btn-light btn-default btn-squared">
                                    Отмена
                                </a>
                                <button type="submit" class="btn btn-danger btn-default btn-squared">
                                    Удалить магазин
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
