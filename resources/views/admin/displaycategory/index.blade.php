@extends('admin.layout.app')
@section('title', 'Display Categories')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">

            <div class="card">
                <div class="card-header">
                    <h4>Add Display Category</h4>
                </div>

                <form action="{{ route('display.category.store') }}" method="POST" id="displayCategoryForm">
                    @csrf
                    <input type="hidden" name="id" id="category_id">
                    <input type="hidden" name="_method" id="form_method" value="POST">

                    <div class="card-body pb-2">
                        <div class="row mb-0">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Category Name <span style="color:red;">*</span></label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="form-control"
                                           placeholder="e.g. Unstitched"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-center">
                        <button type="button" class="btn btn-secondary mb-2" id="resetFormBtn">Reset</button>
                        <button type="submit" class="btn btn-primary mb-2" id="submitBtn">Save Category</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Display Category List</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered" id="table_id_events">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->slug }}</td>
                                    <td>
                                        @if ($category->status)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap:6px;">
                                            @if (Auth::guard('admin')->check() ||
                                                ($sideMenuPermissions->has('Display Categories') && $sideMenuPermissions['Display Categories']->contains('edit')))
                                                <button type="button"
                                                    class="btn btn-primary p-2 editCategory"
                                                    style="background-color:#609b90;"
                                                    data-id="{{ $category->id }}"
                                                    data-name="{{ $category->name }}"
                                                    data-status="{{ $category->status ? 1 : 0 }}">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            @endif

                                            @if (Auth::guard('admin')->check() ||
                                                ($sideMenuPermissions->has('Display Categories') && $sideMenuPermissions['Display Categories']->contains('delete')))
                                                <form action="{{ route('display.category.delete', $category->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Delete this category?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger p-2">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No display categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var form = document.getElementById('displayCategoryForm');
        var idInput = document.getElementById('category_id');
        var methodInput = document.getElementById('form_method');
        var nameInput = document.getElementById('name');
        var statusInput = document.getElementById('status');
        var submitBtn = document.getElementById('submitBtn');
        var storeUrl = "{{ route('display.category.store') }}";

        function resetForm() {
            idInput.value = '';
            methodInput.value = 'POST';
            form.action = storeUrl;
            nameInput.value = '';
            statusInput.value = '1';
            submitBtn.textContent = 'Save Category';
        }

        document.getElementById('resetFormBtn').addEventListener('click', resetForm);

        document.querySelectorAll('.editCategory').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-id');
                idInput.value = id;
                methodInput.value = 'POST';
                form.action = "{{ url('admin/display-categories/update') }}/" + id;
                nameInput.value = this.getAttribute('data-name') || '';
                statusInput.value = this.getAttribute('data-status') || '1';
                submitBtn.textContent = 'Update Category';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    })();
</script>
@endsection
