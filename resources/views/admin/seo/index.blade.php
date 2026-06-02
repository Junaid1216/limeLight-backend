@extends('admin.layout.app')
@section('title', 'SEO')
<style>
    /* For the active tab */
    .nav-pills .nav-link.active {
        background-color: #ff5608 !important;
        color: white !important;
        /* Optional: change text color for better contrast */
    }

    /* For hover effect */
    .nav-pills .nav-link:hover:not(.active) {
        background-color: #ff560833;
        /* 33 = 20% opacity */
        color: #ff5608;
    }
</style>

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-md-3">
                <div class="nav flex-column nav-pills" id="seo-tabs" role="tablist" aria-orientation="vertical">
                    @foreach ($pages as $page)
                        <a class="nav-link {{ $page->id == $currentPage->id ? 'active' : '' }}" id="tab-{{ $page->id }}"
                            data-page-id="{{ $page->id }}" href="#pane-{{ $page->id }}" role="tab"
                            aria-selected="{{ $page->id == $currentPage->id ? 'true' : 'false' }}" data-bs-toggle="pill">
                            {{ ucfirst($page->page) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-md-9">
                <div class="tab-content" id="seo-content-container">
                    @foreach ($pages as $page)
                        <div class="tab-pane fade {{ $page->id == $currentPage->id ? 'show active' : '' }}"
                            id="pane-{{ $page->id }}" role="tabpanel" aria-labelledby="tab-{{ $page->id }}">
                            <h2 class="mb-4">SEO Settings for {{ ucfirst($page->page) }}</h2>
                            <form id="seo-form-{{ $page->id }}" action="{{ route('seo.update', $page->id) }}"
                                method="POST">
                                @csrf
                                @method('POST')
                                <div class="mb-3">
                                    <label class="form-label">Title <span style="color: red;">*</span></label>
                                    <input type="text" name="title" value="{{ old('title', $page->title) }}"
                                        class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">OG Title <span style="color: red;">*</span></label>
                                    <input type="text" name="og_title" value="{{ old('og_title', $page->og_title) }}"
                                        class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description <span style="color: red;">*</span></label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description', $page->description) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">OG Description <span style="color: red;">*</span></label>
                                    <textarea name="og_description" class="form-control" rows="3">{{ old('og_description', $page->og_description) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Keywords <span style="color: red;">*</span></label>
                                    <textarea name="keywords" class="form-control" rows="3">{{ old('keywords', $page->keywords) }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
            // Handle form submissions
            $(document).on('submit', '[id^="seo-form-"]', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();
                const pageId = form.attr('id').split('-')[2];

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        alert('SEO settings saved successfully!');
                    },
                    error: function(xhr) {
                        console.error('Error saving SEO content');
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            alert('Please fix the errors and try again.');
                        }
                    }
                });
            });
        });
    </script>
@endsection