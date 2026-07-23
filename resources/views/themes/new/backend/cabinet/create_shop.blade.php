@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents store-edit-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="breadcrumb-main">
                    <h4 class="breadcrumb-title">Создание магазина</h4>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('account.store.store') }}" method="POST" enctype="multipart/form-data" class="row store-edit-layout store-edit-form" id="create-shop-form">
            <div class="col-lg-8 store-edit-main">
                <div class="card mb-25 store-edit-card store-edit-main-card">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">Основная информация</h6>
                    </div>
                    <div class="card-body">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Название магазина <span class="text-danger">*</span></label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" id="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">Описание</label>
                                    <input id="description" type="text" class="form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description') }}">
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="settlement_id" class="form-label">Населённый пункт</label>
                                    <select class="form-control @error('settlement_id') is-invalid @enderror" id="settlement_id" name="settlement_id" required>
                                        <option value="">Выберите населённый пункт</option>
                                        @foreach($regions as $region)
                                            <optgroup label="{{ $region->name }}">
                                                @foreach($region->settlements as $settlement)
                                                    <option value="{{ $settlement->id }}" @selected((string) old('settlement_id') === (string) $settlement->id)>
                                                        {{ $settlement->name }} ({{ $settlement->type }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('settlement_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="sellerplace" class="form-label">Район / ориентир</label>
                                    <input
                                        type="text"
                                        class="form-control @error('sellerplace') is-invalid @enderror"
                                        id="sellerplace"
                                        name="sellerplace"
                                        value="{{ old('sellerplace') }}"
                                        maxlength="255"
                                        placeholder="Например: центр, рынок, микрорайон"
                                    >
                                    @error('sellerplace')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">Адрес</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4 store-edit-aside">
                <div class="card mb-25 store-edit-card store-edit-logo-card">
                    <div class="card-header">
                        <h6 class="fw-500 mb-0">Логотип</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="account-profile store-create-logo-upload justify-content-center d-flex mb-3">
                            <div class="ap-img pro_img_wrapper">
                                <input id="shop-logo-upload" type="file" name="logo" accept="image/*" class="d-none">
                                <label for="shop-logo-upload" aria-label="Выбрать логотип магазина">
                                    <img id="logo-preview" class="store-create-logo-upload__preview" alt="Предпросмотр логотипа" hidden>
                                    <span id="logo-upload-placeholder" class="store-create-logo-upload__placeholder">
                                        <img src="{{ asset('cabinet/img/svg/camera.svg') }}" alt="" class="svg">
                                    </span>
                                </label>
                            </div>
                        </div>
                        <h6 class="mb-2">Добавьте фото магазина</h6>
                        <p class="text-muted mb-0">Логотип будет отображаться в карточке магазина и в каталоге продавцов.</p>
                        @error('logo')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>

            <div class="col-12 store-edit-actions">
                <div class="store-edit-submit-bar">
                    <a href="{{ route('account.index') }}" class="btn btn-light btn-default btn-squared">
                        Отмена
                    </a>
                    <button type="submit" class="btn btn-primary btn-default btn-squared radius-md shadow2">
                        Создать магазин
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('shop-logo-upload').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            const preview = document.getElementById('logo-preview');
            preview.src = event.target.result;
            preview.hidden = false;
            document.getElementById('logo-upload-placeholder').hidden = true;
        };
        reader.readAsDataURL(file);
    });
</script>
@endpush
@endsection
