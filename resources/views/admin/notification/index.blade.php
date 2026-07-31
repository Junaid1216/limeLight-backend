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
                                            <th>Date & Time</th>
                                            <th>User Type</th>
                                            <th>Users</th>
                                            <th>Title</th>
                                            <th>Message</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notifications as $notification)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $notification->created_at->format('d M Y, h:i A') }}</td>
                                                <td>{{ ucfirst($notification->user_type) }}</td>
                                                <td>
                                                    @php
                                                        $targetNames = $notification->targets
                                                            ->pluck('targetable.name')
                                                            ->filter()
                                                            ->values();
                                                    @endphp
                                                    <span id="user-preview-{{ $notification->id }}">
                                                        @foreach ($targetNames->take(2) as $name)
                                                            <span class="badge me-1 mb-1"
                                                                style="background-color: #609b90; color: #fff;">
                                                                {{ $name }}
                                                            </span>
                                                        @endforeach
                                                        @if ($targetNames->count() > 2)
                                                            <a href="javascript:void(0);"
                                                                onclick="toggleUsers({{ $notification->id }})">...more</a>
                                                        @endif
                                                    </span>
                                                    <div id="user-full-{{ $notification->id }}" style="display: none;">
                                                        @foreach ($targetNames as $name)
                                                            <span class="badge me-1 mb-1"
                                                                style="background-color: #609b90; color: #fff;">
                                                                {{ $name }}
                                                            </span>
                                                        @endforeach
                                                        <a href="javascript:void(0);"
                                                            onclick="toggleUsers({{ $notification->id }})">less</a>
                                                    </div>
                                                </td>
                                                <td>{{ $notification->title }}</td>
                                                <td>{{ \Illuminate\Support\Str::limit(strip_tags($notification->description), 150, '...') }}
                                                </td>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-labelledby="createUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="createUserForm" method="POST" action="{{ route('notification.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>User Type <span style="color:red;">*</span></strong></label>
                                    <select id="user_type" name="user_type" class="form-control" required>
                                        <option value="">Select user type</option>
                                        <option value="staff">Sales Staff</option>
                                        <option value="manager">Branch Manager</option>
                                        <option value="asm">ASM</option>
                                        <option value="all">All</option>
                                    </select>
                                    @error('user_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Title <span style="color:red;">*</span></strong></label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="Title" required>
                                    @error('title')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group" id="user_field" style="display: none;">
                                    <label><strong>Users <span style="color: red;">*</span></strong></label>
                                    <div class="form-check mb-2" style="line-height: 1.9;padding-left: 1.5em">
                                        <input type="checkbox" id="select_all_users" class="form-check-input">
                                        <label class="form-check-label" for="select_all_users">Select All</label>
                                    </div>
                                    <select name="users[]" id="users" class="form-control select2" multiple></select>

                                    <select id="staff_list" style="display: none;">
                                        @foreach ($staff as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <select id="manager_list" style="display: none;">
                                        @foreach ($managers as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <select id="asm_list" style="display: none;">
                                        @foreach ($asms as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>

                                    @error('users')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Description <span style="color:red;">*</span></strong></label>
                                    <textarea name="description" id="description" class="form-control" placeholder="Type your message here..." rows="4" required></textarea>
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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
                placeholder: "Select users",
                allowClear: true
            });

            $('#createUserModal').on('shown.bs.modal', function() {
                $('.select2').select2({
                    dropdownParent: $('#createUserModal'),
                    placeholder: "Select users",
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

            $('#user_type').on('change', function() {
                const userType = $(this).val();
                $('#users').empty();
                $('#select_all_users').prop('checked', false);

                if (userType === 'staff') {
                    $('#users').html($('#staff_list').html());
                    $('#user_field').slideDown(initSelect2("Select sales staff"));
                } else if (userType === 'manager') {
                    $('#users').html($('#manager_list').html());
                    $('#user_field').slideDown(initSelect2("Select branch managers"));
                } else if (userType === 'asm') {
                    $('#users').html($('#asm_list').html());
                    $('#user_field').slideDown(initSelect2("Select ASMs"));
                } else if (userType === 'all') {
                    $('#users').html($('#staff_list').html() + $('#manager_list').html() + $('#asm_list').html());
                    $('#user_field').slideDown(initSelect2("Select users"));
                } else {
                    $('#user_field').slideUp();
                }

                $('#users').val(null).trigger('change');
            });

            function initSelect2(placeholderText) {
                return function() {
                    $('#users').select2('destroy').select2({
                        dropdownParent: $('#createUserModal'),
                        placeholder: placeholderText,
                        allowClear: true,
                        width: '100%'
                    });
                };
            }

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
                                    text: 'Record deleted successfully.',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => location.reload());
                            },
                            error: function() {
                                Swal.fire('Error!', 'Failed to delete the record.', 'error');
                            }
                        });
                    }
                });
            });

            $('.delete_all').click(function(event) {
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
                        form.submit();
                    }
                });
            });
        });

        function toggleUsers(id) {
            const preview = document.getElementById(`user-preview-${id}`);
            const full = document.getElementById(`user-full-${id}`);
            preview.style.display = preview.style.display === 'none' ? 'inline' : 'none';
            full.style.display = full.style.display === 'none' ? 'inline' : 'none';
        }
    </script>
@endsection
