@extends('admin.layout.app')
@section('title', 'Blogs')
@section('content')

    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12 col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="col-12">
                                    <h4>Blogs</h4>
                                </div>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <div class="clearfix">
                                    <div class="create-btn">
                                        @if (Auth::guard('admin')->check() ||
                                                ($sideMenuPermissions->has('Blogs') && $sideMenuPermissions['Blogs']->contains('create')))
                                            <a class="btn btn-primary mb-3 text-white"
                                                href="{{ url('admin/blogs-create') }}">Create</a>
                                        @endif
                                    </div>
                                </div>

                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Sr.</th>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Image</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-faqs">
                                        @foreach ($blogs as $blog)
                                            <tr data-id="{{ $blog->id }}">
                                                <td class="sort-handler" style="cursor: move; text-align: center;">
                                                    <i class="fas fa-th"></i>
                                                </td>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $blog->title }}</td>
                                                <td>{{ $blog->slug }}</td>
                                                <td>
                                                    @if ($blog->image)
                                                        <img src="{{ asset('public/' . $blog->image) }}" alt="blog image"
                                                            width="50" height="50">
                                                    @else
                                                        <img src="{{ asset('public/admin/assets/images/default.png') }}"
                                                            alt="blog image" width="50" height=50">
                                                    @endif
                                                </td>

                                                <td title="{{ strip_tags(html_entity_decode($blog->content)) }}">
                                                    {{ \Illuminate\Support\Str::limit(strip_tags($blog->content), 150, '...') }}
                                                </td>

                                                <td>
                                                    <label class="custom-switch">
                                                        <input type="checkbox" class="custom-switch-input toggle-status"
                                                            data-id="{{ $blog->id }}"
                                                            {{ $blog->toggle ? 'checked' : '' }}>
                                                        <span class="custom-switch-indicator"></span>
                                                        <span class="custom-switch-description">
                                                            {{ $blog->toggle ? 'Activated' : 'Deactivated' }}
                                                            {{-- @php
                                                                dd($blog->toggle);
                                                            @endphp --}}
                                                        </span>
                                                    </label>
                                                </td>

                                                <td>
                                                    <div class="d-flex">
                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Blogs') && $sideMenuPermissions['Blogs']->contains('edit')))
                                                            <a href="{{ route('blog.edit', $blog->id) }}"
                                                                class="btn btn-primary" style="margin-right: 10px">
                                                                <span><i class="fa fa-edit"></i></span>
                                                            </a>
                                                        @endif

                                                        @if (Auth::guard('admin')->check() ||
                                                                ($sideMenuPermissions->has('Blogs') && $sideMenuPermissions['Blogs']->contains('delete')))
                                                            <form id="delete-form-{{ $blog->id }}"
                                                                action="{{ route('blog.destroy', $blog->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                            </form>

                                                            <!-- Delete Button -->
                                                            <button class="show_confirm btn d-flex "
                                                                style="background-color: #ff5608
;"
                                                                data-form="delete-form-{{ $blog->id }}" type="button">
                                                                <span><i class="fa fa-trash"></i></span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

@endsection


@section('js')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // SweetAlert2 delete confirmation
            $('.show_confirm').click(function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                Swal.fire({
                    title: "Are you sure you want to delete this record?",
                    text: "If you delete this Blog record, it will be gone forever.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.action,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: "Success!",
                                    text: "Record deleted successfully.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire("Error!", "Failed to delete record.",
                                    "error");
                            }
                        });
                    }
                });
            });

            // Toggle status
            $('.toggle-status').change(function() {
                const toggleSwitch = $(this);
                const status = toggleSwitch.is(':checked') ? 1 : 0;
                const blogId = toggleSwitch.data('id');
                const $statusText = toggleSwitch.siblings('.custom-switch-description');

                $.ajax({
                    url: "{{ route('blog.toggle-status') }}",
                    type: "POST",
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: blogId,
                        status: status
                    },
                    success: function(res) {
                        if (res.success) {
                            $statusText.text(res.new_status);
                            toastr.success(res.message);
                        } else {
                            toggleSwitch.prop('checked', !status); // Undo toggle
                            toastr.error(res.message);
                        }
                    },
                    error: function() {
                        toggleSwitch.prop('checked', !status); // Undo toggle
                        toastr.error('Something went wrong!');
                    }
                });
            });

            // Drag and Drop Reordering (jQuery version using Sortable + AJAX)
            var sortable = new Sortable(document.getElementById('sortable-faqs'), {
                animation: 150,
                handle: '.sort-handler',
                onEnd: function() {
                    var order = [];

                    $('#sortable-faqs tr').each(function(index) {
                        order.push({
                            id: $(this).data('id'),
                            position: index + 1
                        });
                    });

                    $.ajax({
                        url: "{{ route('blog.reorder') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        contentType: 'application/json',
                        data: JSON.stringify({
                            order: order
                        }),
                        success: function() {
                            // window.location.reload();
                            toastr.success(
                                'Alignment has been updated successfully');
                            window.location.reload();

                        },
                        error: function() {
                            toastr.error('Failed to reorder blogs.');
                        }
                    });
                }
            });
        });
    </script>
@endsection
