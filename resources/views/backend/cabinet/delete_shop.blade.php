{{--удаления магазина--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">
    <div class="container-fluid mt-25">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card card-default card-md mb-4">
                    <div class="card-header">
                        <h6>Удаление магазина</h6>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        
                        <div class="alert alert-warning">
                            <strong>Внимание!</strong>&nbsp; Это действие необратимо. При удалении магазина все данные магазина будут удалены. Все созданные товары исчезнут без возможности восстановления. Купленный тариф будет аннулирован, и средства не вернутся. Пожалуйста, убедитесь, что вы действительно хотите удалить свой магазин. 
                        </div>
                        
                        <p><strong>Магазин:</strong> {{ $user->seller->name }}</p>
                        <p><strong>Товаров:</strong> {{ $user->seller->products()->count() }}</p>
                        
                        <form method="POST" action="{{ route('account.store.destroy') }}">
                            @csrf
                            @method('DELETE')
                            
                            <div class="form-group mb-20">
                                <label for="password">Введите ваш пароль для подтверждения</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="button-group d-flex pt-25">
                                <button type="submit" class="btn btn-danger btn-default btn-squared mr-15">
                                    Удалить магазин
                                </button>
                                <a href="{{ route('account.index') }}" class="btn btn-light btn-default btn-squared">
                                    Отмена
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

