{{--Создание магазина--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
<div class="contents">

<div class="container-fluid">
   <div class="row">
      <div class="col-lg-12">
         <div class="d-flex align-items-center user-member__title mb-30">
            <h4 class="text-capitalize"></h4>
         </div>
      </div>
   </div>
   <div class="row">
      <div class="col-lg-12">
         <div class="user-info-tab w-100 bg-white global-shadow radius-xl mb-50">
            <div class="ap-tab-wrapper border-bottom ">
               <ul class="nav px-30 ap-tab-main text-capitalize" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                  <li class="nav-item">
                     <a class="nav-link active" id="v-pills-home-tab" data-bs-toggle="pill" href="#v-pills-home" role="tab" aria-selected="true">
                        <img src="img/svg/briefcase.svg" alt="user" class="svg">Страница магазина</a>
                  </li>
               </ul>
            </div>
            <div class="tab-content" id="v-pills-tabContent">
               <div class="tab-pane fade  show active" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                  <div class="row justify-content-center">
                     <div class="col-xxl-4 col-10">
                        <div class="mt-sm-40 mb-sm-50 mt-20 mb-20">
                           <div class="user-tab-info-title mb-sm-40 mb-20 text-capitalize">
                              <h5 class="fw-500">О магазине</h5>
                           </div>
                           <div class="account-profile d-flex align-items-center mb-4 ">
                              <div class="ap-img pro_img_wrapper">
                                 <input id="file-upload" type="file" name="fileUpload" class="d-none">
                                 <!-- Profile picture image-->
                                 <label for="file-upload">
                                    <img class="ap-img__main rounded-circle wh-120 bg-lighter d-flex" src="img/author/profile.png" alt="profile">
                                    <span class="cross" id="remove_pro_pic">
                                       <img src="img/svg/camera.svg" alt="camera" class="svg">
                                    </span>
                                 </label>
                              </div>
                              <div class="account-profile__title">
                                 <h6 class="fs-15 ms-20 fw-500 text-capitalize">Добавьте фото</h6>
                              </div>
                           </div>
                           <div class="edit-profile__body">
                                <form action="{{ route('cabinet.store_shop') }}" method="POST">
                                @csrf    
                                    <div class="form-group mb-25">
                                        <label for="name">Название магазина</label>
                                        <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-group mb-25">
                                        <label for="description">Описание</label>
                                        <input id="description" type="text" class="form-control"  name="description" value="{{ old('description') }}" >
                                    </div>
                                    <div class="form-group mb-25">
                                        <label for="phone">Телефон</label>
                                        <input type="tel" class="form-control" name="phone" id="phone" value="{{ old('phone') }}" >
                                    </div>
                                    <div class="form-group mb-25">
                                        <div class="countryOption">
                                        <label for="town">
                                            Город
                                        </label>
                                        <select class="js-example-basic-single js-states form-control" id="town" name="town" >
                                            <option value="Тирасполь">Тирасполь</option>
                                        </select>
                                        </div>
                                    </div>
                                    <div class="form-group mb-25">
                                        <div class="cityOption">
                                        <label for="sellerplace">
                                            Район Тирасполя
                                        </label>
                                        <select class="js-example-basic-single js-states form-control" id="sellerplace" name="sellerplace">
                                            <option value="Центр">Центр</option>
                                            <option value="Балка">Балка</option>
                                            <option value="Промзона">Промзона</option>
                                            <option value="Бородинка">Бородинка</option>
                                            <option value="Красные казармы">Красные казармы</option>
                                            <option value="Западный">Западный</option>
                                        </select>
                                        </div>
                                    </div>
                                    <div class="form-group mb-25">
                                        <label for="address">Адресс</label>
                                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address')}}">
                                    </div>
                                    <div class="button-group d-flex pt-sm-25 justify-content-md-end justify-content-start ">

                                        <button type="submit" class="btn btn-primary btn-default btn-squared text-capitalize radius-md shadow2 btn-sm">Создать
                                        </button>
                                    </div>
                                </form>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

</div>
@endsection