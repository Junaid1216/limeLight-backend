<div class="tab-pane fade show active">
    <h2 class="mb-4">SEO Settings for {{ ucfirst($page->page) }}</h2>
    <form action="{{ route('seo.update', $page->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">OG Title</label>
            <input type="text" name="og_title" value="{{ old('og_title', $page->og_title) }}" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $page->description) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">OG Description</label>
            <textarea name="og_description" class="form-control" rows="3">{{ old('og_description', $page->og_description) }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Keywords</label>
            <textarea name="keywords" class="form-control" rows="3">{{ old('keywords', $page->keywords) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
