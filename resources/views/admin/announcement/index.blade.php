@extends('admin.layout.app')
@section('title', 'Announcements')

@section('content')
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
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
                                            <label>Category / Title Type <span style="color: red;">*</span></label>
                                            <select name="category" id="category" class="form-control" required>
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

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Title <span style="color: red;">*</span></label>
                                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Mega Sale is coming soon" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Description <span style="color: red;">*</span></label>
                                            <textarea name="description" id="description" class="form-control" rows="4" placeholder="Announcement details..." required></textarea>
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

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">All Announcements</h4>
                            {{-- <div>
                                <button type="button" class="btn btn-sm filter-btn active" data-filter="all" style="background:#0d9488;">All</button>
                                <button type="button" class="btn btn-sm filter-btn" data-filter="hr" style="border-radius:20px;">HR</button>
                                <button type="button" class="btn btn-sm filter-btn" data-filter="performance" style="border-radius:20px;">Performance</button>
                                <button type="button" class="btn btn-sm filter-btn" data-filter="promotions" style="border-radius:20px;">Promotions</button>
                            </div> --}}
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table" id="table_id_events">
                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Category</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Roles</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($announcements as $announcement)
                                        <tr data-category="{{ $announcement->category }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <span class="badge" style="background:#0d9488;color:#fff;border-radius:12px;padding:5px 10px;">
                                                    {{ $announcement->category_label }}
                                                </span>
                                            </td>
                                            <td>{{ $announcement->title }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($announcement->description, 80) }}</td>
                                            <td>
                                                {{ collect($announcement->roles)->map(function ($role) use ($roleOptions) {
                                                    return $roleOptions[$role] ?? $role;
                                                })->implode(', ') }}
                                            </td>
                                            <td>
                                                @if ($announcement->status)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap:6px;">
                                                    <button type="button"
                                                        class="btn btn-primary p-2 editAnnouncement"
                                                        style="background-color:#609b90;"
                                                        data-id="{{ $announcement->id }}"
                                                        data-category="{{ $announcement->category }}"
                                                        data-title="{{ $announcement->title }}"
                                                        data-description="{{ $announcement->description }}"
                                                        data-roles='@json($announcement->roles)'
                                                        data-date="{{ optional($announcement->announcement_date)->format('Y-m-d') }}"
                                                        data-status="{{ $announcement->status ? 1 : 0 }}">
                                                        <i class="fa fa-edit"></i>
                                                    </button>

                                                    <form id="delete-form-{{ $announcement->id }}"
                                                        action="{{ route('announcement.destroy', $announcement->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button class="show_confirm btn p-2"
                                                        style="background-color:#609b90;"
                                                        data-form="delete-form-{{ $announcement->id }}"
                                                        type="button">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
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
$(document).ready(function () {
    if ($.fn.select2) {
        $('#roles').select2({
            width: '100%',
            placeholder: 'Select roles'
        });
    }

    $('#table_id_events').DataTable();

    $('.filter-btn').on('click', function () {
        const filter = $(this).data('filter');
        $('.filter-btn').removeClass('active').css({ background: '', color: '' });
        $(this).addClass('active').css({ background: '#0d9488', color: '#fff' });

        $('#table_id_events tbody tr').each(function () {
            const category = $(this).data('category');
            if (filter === 'all' || category === filter) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('.editAnnouncement').on('click', function () {
        const id = $(this).data('id');
        const category = $(this).data('category');
        const title = $(this).data('title');
        const description = $(this).data('description');
        const roles = $(this).attr('data-roles');
        const date = $(this).data('date');

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
        $('#announcementForm').attr('action', '{{ route('announcement.store') }}');
        $('#announcementForm input[name="_method"]').remove();
        $('#submitBtn').text('Create Announcement');
        $('#announcement_date').val('{{ date('Y-m-d') }}');
    });

    $('.show_confirm').on('click', function () {
        const formId = $(this).data('form');
        Swal.fire({
            title: 'Are you sure?',
            text: 'This announcement will be deleted.',
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
