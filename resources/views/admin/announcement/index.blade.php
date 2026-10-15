@extends('admin.layout.app')
@section('title', 'Announcements')

@section('content')
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    @if (Auth::guard('admin')->check() ||
                            ($sideMenuPermissions->has('Announcements') && ($sideMenuPermissions['Announcements']->contains('create') || $sideMenuPermissions['Announcements']->contains('edit'))))
                    <div class="card">
                        <div class="card-header">
                            <h4>Announcements</h4>
                        </div>

                        <form action="{{ route('announcement.store') }}" method="POST" id="announcementForm">
                            @csrf
                            <input type="hidden" name="id" id="announcement_id">

                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Type <span style="color: red;">*</span></label>
                                            <select name="type" id="type" class="form-control" required>
                                                <option value="announcement">Announcement</option>
                                                <option value="notification">Notification</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4" id="category_field">
                                        <div class="form-group">
                                            <label>Category / Title Type <span style="color: red;">*</span></label>
                                            <select name="category" id="category" class="form-control">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Send To Roles <span style="color: red;">*</span></label>
                                            <select name="roles[]" id="roles" class="form-control select2" multiple required>
                                                @foreach ($roleOptions as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4" id="users_field" style="display: none;">
                                        <div class="form-group">
                                            <label>Users <span style="color: red;">*</span></label>
                                            <div class="form-check mb-2" style="line-height: 1.9; padding-left: 1.5em;">
                                                <input type="checkbox" id="select_all_users" class="form-check-input">
                                                <label class="form-check-label" for="select_all_users">Select All</label>
                                            </div>
                                            <select name="users[]" id="users" class="form-control select2" multiple>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Title <span style="color: red;">*</span></label>
                                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Mega Sale is coming soon" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Description <span style="color: red;">*</span></label>
                                            <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter description..." required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-right">
                                <button type="button" class="btn btn-secondary" id="resetFormBtn">Reset</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">Create Announcement</button>
                            </div>
                        </form>
                    </div>
                    @endif

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">All Announcements & Notifications</h4>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table" id="table_id_events">
                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Type</th>
                                        <th>Category / Audience</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Roles / Users</th>
                                        <th>Status</th>
                                        <th>Sent At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr data-kind="{{ $row['kind'] }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                @if ($row['kind'] === 'notification')
                                                    <span class="badge badge-info">Notification</span>
                                                @else
                                                    <span class="badge badge-primary">Announcement</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge" style="background:#0d9488;color:#fff;border-radius:12px;padding:5px 10px;">
                                                    {{ $row['category_label'] ?: '-' }}
                                                </span>
                                            </td>
                                            <td>{{ $row['title'] }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($row['description'], 80) }}</td>
                                            <td>
                                                @if ($row['kind'] === 'notification')
                                                    <div><strong>Audience:</strong> {{ $row['roles'] }}</div>
                                                    <div class="text-muted" style="font-size:12px;">
                                                        {{ \Illuminate\Support\Str::limit($row['users'], 60) }}
                                                    </div>
                                                @else
                                                    {{ $row['roles'] ?: '-' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($row['status_active'])
                                                    <span class="badge badge-success">{{ $row['status'] }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $row['status'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $row['created_at'] ? $row['created_at']->copy()->timezone('Asia/Karachi')->format('d M Y, h:i A') : '-' }}
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap:6px;">
                                                    @if ($row['kind'] === 'announcement' && !empty($row['edit']) &&
                                                        (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Announcements') && $sideMenuPermissions['Announcements']->contains('edit'))))
                                                        <button type="button"
                                                            class="btn btn-primary p-2 editAnnouncement"
                                                            style="background-color:#609b90;"
                                                            data-id="{{ $row['edit']['id'] }}"
                                                            data-category="{{ $row['edit']['category'] }}"
                                                            data-title="{{ $row['edit']['title'] }}"
                                                            data-description="{{ $row['edit']['description'] }}"
                                                            data-roles='@json($row['edit']['roles'])'
                                                            data-date="{{ $row['edit']['date'] }}"
                                                            data-status="{{ $row['edit']['status'] }}">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                    @endif

                                                    @if (Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Announcements') && $sideMenuPermissions['Announcements']->contains('delete')))
                                                    <form id="delete-form-{{ $row['kind'] }}-{{ $row['id'] }}"
                                                        action="{{ $row['delete_route'] }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button class="show_confirm btn p-2"
                                                        style="background-color:#609b90;"
                                                        data-form="delete-form-{{ $row['kind'] }}-{{ $row['id'] }}"
                                                        data-kind="{{ $row['kind'] }}"
                                                        type="button">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No announcements or notifications found.</td>
                                        </tr>
                                    @endforelse
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
$(document).ready(function () {
    const usersByRolesUrl = @json(route('announcement.users.by.roles'));

    if ($.fn.select2) {
        $('#roles').select2({
            width: '100%',
            placeholder: 'Select roles'
        });
        $('#users').select2({
            width: '100%',
            placeholder: 'Select users'
        });
    }

    if ($('#table_id_events tbody tr').length && !$('#table_id_events tbody tr td[colspan]').length) {
        $('#table_id_events').DataTable({
            order: [[7, 'desc']]
        });
    }

    function syncTypeFields() {
        const type = $('#type').val();
        if (type === 'notification') {
            $('#category_field').hide();
            $('#category').prop('required', false).val('');
            $('#users_field').show();
            $('#users').prop('required', true);
            $('#submitBtn').text($('#announcement_id').val() ? 'Update Announcement' : 'Send Notification');
            loadUsersByRoles();
        } else {
            $('#category_field').show();
            $('#category').prop('required', true);
            $('#users_field').hide();
            $('#users').prop('required', false).val(null).trigger('change');
            $('#select_all_users').prop('checked', false);
            $('#submitBtn').text($('#announcement_id').val() ? 'Update Announcement' : 'Create Announcement');
        }
    }

    function loadUsersByRoles() {
        if ($('#type').val() !== 'notification') {
            return;
        }

        const roles = $('#roles').val() || [];
        $('#users').empty().trigger('change');
        $('#select_all_users').prop('checked', false);

        if (!roles.length) {
            return;
        }

        $.ajax({
            url: usersByRolesUrl,
            method: 'GET',
            data: { roles: roles },
            success: function (res) {
                const list = (res && res.data) ? res.data : [];
                list.forEach(function (user) {
                    const option = new Option(user.text, user.id, false, false);
                    $('#users').append(option);
                });
                $('#users').trigger('change');
            }
        });
    }

    $('#type').on('change', syncTypeFields);
    $('#roles').on('change', function () {
        if ($('#type').val() === 'notification') {
            loadUsersByRoles();
        }
    });

    $('#select_all_users').on('change', function () {
        if (this.checked) {
            $('#users > option').prop('selected', true);
        } else {
            $('#users > option').prop('selected', false);
        }
        $('#users').trigger('change');
    });

    $('#users').on('change', function () {
        const total = $('#users option').length;
        const selected = $('#users option:selected').length;
        $('#select_all_users').prop('checked', total > 0 && selected === total);
    });

    syncTypeFields();

    $('.editAnnouncement').on('click', function () {
        const id = $(this).data('id');
        const category = $(this).data('category');
        const title = $(this).data('title');
        const description = $(this).data('description');
        const roles = $(this).attr('data-roles');
        const date = $(this).data('date');

        $('#type').val('announcement').trigger('change');
        $('#announcement_id').val(id);
        $('#category').val(category);
        $('#title').val(title);
        $('#description').val(description);
        $('#announcement_date').val(date);
        $('#roles').val(JSON.parse(roles)).trigger('change');

        $('#announcementForm').attr('action', '{{ url('admin/announcements/update') }}/' + id);
        if ($('#announcementForm input[name="_method"]').length === 0) {
            $('#announcementForm').append('<input type="hidden" name="_method" value="POST">');
        }
        $('#submitBtn').text('Update Announcement');

        $('html, body').animate({ scrollTop: 0 }, 300);
    });

    $('#resetFormBtn').on('click', function () {
        $('#announcementForm')[0].reset();
        $('#announcement_id').val('');
        $('#roles').val(null).trigger('change');
        $('#users').val(null).empty().trigger('change');
        $('#select_all_users').prop('checked', false);
        $('#type').val('announcement');
        $('#announcementForm').attr('action', '{{ route('announcement.store') }}');
        $('#announcementForm input[name="_method"]').remove();
        syncTypeFields();
    });

    $('.show_confirm').on('click', function () {
        const formId = $(this).data('form');
        const kind = $(this).data('kind') || 'announcement';
        Swal.fire({
            title: 'Are you sure?',
            text: kind === 'notification'
                ? 'This notification will be deleted.'
                : 'This announcement will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#609b90',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    });
});
</script>
@endsection
