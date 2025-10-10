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
        <!--      <li class="nav-item">
                     <a class="nav-link" id="v-pills-profile-tab" data-bs-toggle="pill" href="#v-pills-profile" role="tab" aria-selected="false">
                        <img src="img/svg/user.svg" alt="briefcase" class="svg">Личная информация</a>
                  </li> -->
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
                              <form>
                                 <div class="form-group mb-25">
                                    <label for="name1">Название магазина</label>
                                    <input type="text" class="form-control" id="name1" placeholder="Магазин одежды мода">
                                 </div>
                                 <div class="form-group mb-25">
                                    <label for="name3">Описание</label>
                                    <input type="text" class="form-control" id="name3" placeholder="Большой выбор зимних курток, спортивной одежды.">
                                 </div>
                                 <div class="form-group mb-25">
                                    <label for="phoneNumber5">Телефон</label>
                                    <input type="tel" class="form-control" id="phoneNumber5" placeholder="077993603">
                                 </div>
                                 <div class="form-group mb-25">
                                    <div class="countryOption">
                                       <label for="countryOption">
                                          Город
                                       </label>
                                       <select class="js-example-basic-single js-states form-control" id="countryOption">
                                          <option value="JAN">Тирасполь</option>
                                       </select>
                                    </div>
                                 </div>
                                 <div class="form-group mb-25">
                                    <div class="cityOption">
                                       <label for="cityOption">
                                          Район Тирасполя
                                       </label>
                                       <select class="js-example-basic-single js-states form-control" id="cityOption">
                                          <option value="JAN">Центр</option>
                                          <option value="FBR">Балка</option>
                                          <option value="FBR">Промзона</option>
                                          <option value="FBR">Бородинка</option>
                                          <option value="FBR">Красные казармы</option>
                                          <option value="FBR">Западный</option>
                                       </select>
                                    </div>
                                 </div>
                                 <div class=" mb-30">
                                    <label for="webUrl">Вебсайт</label>
                                    <div class="input-group flex-nowrap">
                                       <div class="input-group-prepend">
                                          <span class="input-group-text bg-ruby border-ruby text-white wh-44 radius-xs justify-content-center" id="addon-wrapping3">
                                             <i class="las la-basketball-ball fs-18"></i>
                                          </span>
                                       </div>
                                       <input type="text" class="form-control form-control--social" placeholder="Url" aria-label="Username" aria-describedby="addon-wrapping3" id="webUrl">
                                    </div>
                                 </div>
                                 <div class=" mb-30">
                                    <label for="instagramUrl">instagram</label>
                                    <div class="input-group flex-nowrap">
                                       <div class="input-group-prepend">
                                          <span class="input-group-text bg-instagram border-instagram text-white wh-44 radius-xs justify-content-center" id="addon-wrapping4">
                                             <i class="lab la-instagram fs-18"></i>
                                          </span>
                                       </div>
                                       <input type="text" class="form-control form-control--social" aria-describedby="addon-wrapping4" placeholder="Url" id="instagramUrl">
                                    </div>
                                 </div>
                                 <div class="button-group d-flex pt-sm-25 justify-content-md-end justify-content-start ">

                                    <button class="btn btn-light btn-default btn-squared fw-400 text-capitalize radius-md btn-sm">Отменить
                                    </button>

                                    <button class="btn btn-primary btn-default btn-squared text-capitalize radius-md shadow2 btn-sm">Сохранить
                                    </button>
                                 </div>
                              </form>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                  <div class="row justify-content-center">
                     <div class="col-xxl-4 col-10">
                        <div class="mt-40 mb-50">
                           <div class="user-tab-info-title mb-35 text-capitalize">
                              <h5 class="fw-500">О предпринимателе</h5>
                           </div>
                           <div class="edit-profile__body">
                              <form>
                                 <div class="form-group mb-25">
                                    <label for="name4">ФИО</label>
                                    <input type="text" class="form-control" id="name4" placeholder="Фамилия и имя">
                                 </div>
                                 <div class="form-group mb-25">
                                    <label for="phoneNumber1">Личный телефон</label>
                                    <p>Для звонков менеджера техподдержки</p>
                                    <input type="text" class="form-control" id="phoneNumber1" placeholder="077993603">
                                 </div>
                                 <div class="form-group mb-25 status-radio">
                                    <label class="mb-15" for="phoneNumber2">Вид деятельности</label>
                                    <div class="d-flex">
                                       <div class="radio-horizontal-list d-flex flex-wrap">


                                          <div class="radio-theme-default custom-radio ">
                                             <input class="radio" type="radio" name="radio-optional" value=0 id="radio-hl1">
                                             <label for="radio-hl1">
                                                <span class="radio-text">ИП</span>
                                             </label>
                                          </div>




                                          <div class="radio-theme-default custom-radio ">
                                             <input class="radio" type="radio" name="radio-optional" value=0 id="radio-hl2">
                                             <label for="radio-hl2">
                                                <span class="radio-text">Предприятие</span>
                                             </label>
                                          </div>




                                          <div class="radio-theme-default custom-radio ">
                                             <input class="radio" type="radio" name="radio-optional" value=0 id="radio-hl3">
                                             <label for="radio-hl3">
                                                <span class="radio-text">Другое</span>
                                             </label>
                                          </div>


                                       </div>
                                    </div>
                                 </div>
                                 <div class="button-group d-flex pt-20 justify-content-md-end justify-content-start">


                                    <button class="btn btn-light btn-default btn-squared fw-400 text-capitalize radius-md">Отменить
                                    </button>







                                    <button class="btn btn-primary btn-default btn-squared text-capitalize radius-md shadow2">Сохранить
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