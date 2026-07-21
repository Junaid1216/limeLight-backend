<div class="d-flex align-items-center" style="gap: 6px;">
    @if (Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('edit')))
        <button
            type="button"
            class="btn btn-primary btn-sm editVideo"
            data-id="{{ $video->id }}"
            data-training-type="{{ $video->training_type }}"
            data-roles='@json($video->roles)'
            data-category="{{ $video->category }}"
            data-title="{{ $video->title }}"
            data-video="{{ $video->video_url }}"
            data-description="{{ $video->description }}"
            data-product-name="{{ $video->product_name }}"
            data-product-code="{{ $video->product_code }}"
            data-price="{{ $video->price }}"
            data-product-category="{{ $video->product_category }}"
            data-product-sub-category="{{ $video->product_sub_category }}"
            data-product-size="{{ $video->product_size }}"
            data-product-color="{{ $video->product_color }}"
            data-product-status="{{ $video->product_status }}"
            data-training-details="{{ $video->training_details }}">
            <i class="fa fa-edit"></i>
        </button>
    @endif

    @if (Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Training Modules') && $sideMenuPermissions['Training Modules']->contains('delete')))
        <form id="delete-form-{{ $video->id }}"
            action="{{ route('training.video.delete', $video->id) }}"
            method="POST">
            @csrf
            @method('DELETE')
        </form>

        <button class="show_confirm btn p-2"
            style="background-color: #609b90;"
            data-form="delete-form-{{ $video->id }}"
            type="button">
            <i class="fa fa-trash"></i>
        </button>
    @endif
</div>
