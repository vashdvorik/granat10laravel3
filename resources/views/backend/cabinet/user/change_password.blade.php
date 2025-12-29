{{-- ПОЛЬЗОВАТЕЛЬ. СМЕНИТЬ ПАРОЛЬ--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
{{--
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
</div> --}}

<!-- Edit PASSWORD -->
 <div class="contents">
<div class="container-fluid"><div class="row"><div class="col-12">
<div class="edit-profile mt-25">
   <div class="card">
      <div class="card-header px-sm-25 px-3 mt-4">
         <div class="edit-profile__title">
            <h6>Смена пароля</h6>
            <span class="fs-13 color-light fw-400">Измените или восстановите пароль вашего аккаунта</span>
         </div>
      </div>
      <div class="card-body">
         <div class="row justify-content-center">
            <div class="col-xxl-6">
               <div class="edit-profile__body mx-xl-20">
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

                  <form method="POST" action="{{ route('account.user.update_password') }}">
                     @csrf

                     <div class="form-group mb-20">
                        <label for="current_password">Текущий пароль</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                               id="current_password" name="current_password" required>
                        @error('current_password')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="form-group mb-20">
                        <label for="password">Новый пароль</label>
                        <div class="position-relative">
                           <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                  id="password" name="password" placeholder="Пароль" required>
                           <span class="uil uil-eye-slash text-lighten fs-15 field-icon toggle-password" style="cursor: pointer;"></span>
                        </div>
                        <small class="text-light fs-13">Минимум 8 символов</small>
                        @error('password')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                     </div>

                     <div class="form-group mb-1">
                        <label for="password_confirmation">Подтверждение пароля</label>
                        <div class="position-relative">
                           <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                  id="password_confirmation" name="password_confirmation" placeholder="Подтвердите пароль" required>
                           <span class="uil uil-eye-slash text-lighten fs-15 field-icon toggle-password2" style="cursor: pointer;"></span>
                        </div>
                        @error('password_confirmation')
                           <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror

                        <div class="button-group d-flex flex-wrap pt-45 mb-35">
                           <button type="submit" class="btn btn-primary btn-default btn-squared me-15 text-capitalize">
                              Сохранить изменения
                           </button>
                           <a href="{{ route('account.index') }}" class="btn btn-light btn-default btn-squared fw-400 text-capitalize">
                              Отмена
                           </a>
                        </div>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
</div></div></div></div>
<!-- Edit Profile End -->

<script>
   // Toggle password visibility
   document.querySelectorAll('.toggle-password, .toggle-password2').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
         let input = this.previousElementSibling;
         if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('uil-eye-slash');
            this.classList.add('uil-eye');
         } else {
            input.type = 'password';
            this.classList.add('uil-eye-slash');
            this.classList.remove('uil-eye');
         }
      });
   });
</script>
@endsection