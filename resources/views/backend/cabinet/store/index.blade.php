{{-- МАГАЗИН--}}

@extends('backend.cabinet.layout_cabinet')

@section('content_cabinet')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="contents">

   <div class="crm mb-25">
      <div class="container-fluid">
         <div class="row ">
                  @if(!$seller)
                  @include('backend.cabinet.store.nostore')
                  @else 

         </div>@endif
      </div>
   </div>

</div>
@endsection