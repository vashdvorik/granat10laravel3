@foreach($sellers as $seller)
<div class="col-md-4 col-sm-6 col-xs-12 blog-post-item">
    <div class="blog-img">
        <a href="{{ route('seller', ['alias' => $seller->alias])}}" class="hover-images">
            <img src="{{ $seller->photo ? Storage::url($seller->photo) : asset('img/blog/blog_3.jpg') }}" alt="{{ $seller->name }}" class="img-reponsive">
        </a>
        <div class="blog-post-date e-gradient abs">
            <span class="b-date">{{ $seller->products_count ?? 0 }}</span>
            <span class="b-month">ТОВ</span>
        </div>
    </div>
    <div class="blog-info">
        <h3 class="blog-post-title">
            <a href="{{ route('seller', ['alias' => $seller->alias])}}">{{ $seller->name ?? 'Название продавца' }}</a>
        </h3>
        <p class="blog-post-desc">{{ $seller->description ?? '' }}</p>
        <div class="blog-post-author">
            <div class="author">
                Адрес: <span class="c-black">{{ $seller->address ?? '' }}</span>
            </div>
            <div class="blog-post-comment">
                <span class="c-black"></span>{{ $seller->comments_count ?? 2 }}
            </div>
        </div>
    </div>
</div>
@endforeach
