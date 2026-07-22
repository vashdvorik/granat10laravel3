{{--  Смена пароля пользователя--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="container mt-4">
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Сменить пароль</h5>
      <form method="POST" action="{{ route('account.user.update_password') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Текущий пароль</label>
          <input type="password" name="current_password" class="form-control" required>
          @error('current_password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Новый пароль</label>
          <input type="password" name="password" class="form-control" required>
          @error('password') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
          <label class="form-label">Подтверждение пароля</label>
          <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button class="btn btn-primary" type="submit">Сменить пароль</button>
      </form>
    </div>
  </div>
</div>


@endsection