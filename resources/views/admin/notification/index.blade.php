@extends('admin.layout.app')
@section('title', 'Notifications')

@section('content')
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Notifications</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('create')))
                                    <a class="btn mb-3 text-white" data-bs-toggle="modal" style="background-color: #609b90;"
                                        data-bs-target="#createUserModal">Create</a>
                                @endif

                                @if (Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('delete')))
                                    <form action="{{ route('notifications.deleteAll') }}" method="POST"
                                        class="d-inline-block float-right">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-primary mb-3 delete_all">
                                            Delete All
                                        </button>
                                    </form>
                                @endif
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Title</th>
                                            <th>Message</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notifications as $notification)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $notification->title }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit(strip_tags($notification->description), 150, '...') }}
                                                </td>
                                                <td>{{ $notification->created_at->format('d M Y') }}</td>
                                                <td>
                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Notifications') && $sideMenuPermissions['Notifications']->contains('delete')))
                                                        <form id="delete-form-{{ $notification->id }}"
                                                            action="{{ route('notification.destroy', $notification->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>

                                                        <button class="show_confirm btn" style="background-color: #609b90;"
                                                            data-form="delete-form-{{ $notification->id }}" type="button">
                                                            <span><i class="fa fa-trash"></i></span>
                                                        </button>
                                                    @endif


                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>

    <!-- Create Notification Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="createUserForm" method="POST" action="{{ route('notification.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="user_type" value="user">

                        <div class="form-group" id="user_field">
                            <label><strong>Sellers <span style="color: red;">*</span></strong></label>
                            <div class="form-check mb-2" style="line-height: 1.9;padding-left: 1.5em">
                                <input type="checkbox" id="select_all_users" class="form-check-input">
                                <label class="form-check-label" for="select_all_users">Select All</label>
                            </div>
                            <select name="users[]" id="users" class="form-control select2" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('users') && in_array($user->id, old('users')) ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('users')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label><strong>Title <span style="color:red;">*</span></strong></label>
                            <input type="text" name="title" class="form-control" placeholder="Title" required>
                            @error('title')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label><strong>Description <span style="color:red;">*</span></strong></label>
                            <textarea name="description" class="form-control" placeholder="Type your message here..." rows="4" required></textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="createBtn">
                            <span id="createBtnText">Create Notification</span>
                            <span id="createSpinner" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#table_id_events').DataTable();
            $('.select2').select2({
                placeholder: "Select sellers",
                allowClear: true
            });

            // Re-initialize Select2 when modal opens (fix for hidden content)
            $('#createUserModal').on('shown.bs.modal', function() {
                $('.select2').select2({
                    dropdownParent: $('#createUserModal'),
                    placeholder: "Select sellers",
                    allowClear: true
                });
            });

            $('#select_all_users').on('change', function() {
                $('#users > option').prop('selected', this.checked).trigger('change');
            });

            $('#users').on('change', function() {
                $('#select_all_users').prop('checked', $('#users option:selected').length === $(
                    '#users option').length);
            });

            $('form#createUserForm').submit(function() {
                $("#createSpinner").show();
                $("#createBtnText").hide();
                $("#createBtn").prop("disabled", true);
            });

            // Confirm delete action

            $(document).on('click', '.show_confirm', function(event) {
                var formId = $(this).data("form");
                var form = document.getElementById(formId);
                event.preventDefault();
                Swal.fire({
                    title: 'Are you sure you want to delete this record?',
                    text: "If you delete this Notification record, it will be gone forever.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.action,
                            method: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Recored deleted successfully.',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => location.reload());
                            },
                            error: function() {
                                Swal.fire('Error!', 'Failed to delete the record.',
                                    'error');
                            }
                        });
                    }
                });


            });
            $('.delete_all').click(function(event) { // Changed from '.deleteAll' to '.delete_all'
                event.preventDefault();

                var form = $(this).closest("form");

                Swal.fire({
                    title: 'Are you sure you want to delete all records?',
                    text: "This will permanently remove all records and cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form
                            .submit(); // Note: There's a typo here - should be form.submit()
                    }
                });
            });
        });
    </script>
@endsection
