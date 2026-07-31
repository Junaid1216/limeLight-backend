@extends('admin.layout.app')
@section('title', 'Slabs')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">

            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">
                                Slabs
                                <small class="font-weight-bold text-danger">
                                    (Slip Bound Incentive is awarded to Sales Staff based on their sales achievement. Incentives are earned according to the slab reached.)
                                </small>
                            </h4>

                            @if (Auth::guard('admin')->check() ||
                                ($sideMenuPermissions->has('Slip Bound Incentives') && $sideMenuPermissions['Slip Bound Incentives']->contains('edit')) ||
                                ($sideMenuPermissions->has('Slip Bound Incentives') && $sideMenuPermissions['Slip Bound Incentives']->contains('create')))
                                <button type="button" id="addSlabBtn" class="btn btn-primary btn-sm mt-2 mt-md-0">
                                    <i class="fa fa-plus"></i> Add Slab
                                </button>
                            @endif
                        </div>

                        <form action="{{ route('slip.incentive.store') }}" method="POST" id="slabForm">
                            @csrf

                            <div class="card-body table-responsive pb-1">

                                <table class="table table-bordered" id="slabsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Slab</th>
                                            <th>From Amount (PKR)</th>
                                            <th>To Amount (PKR)</th>
                                            <th>Incentive (PKR)</th>
                                            <th style="width: 90px;">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody id="slabsBody">
                                        @forelse($incentives as $existing)
                                            <tr data-id="{{ $existing->id }}">
                                                <td>
                                                    <input type="hidden" name="slab_id[]" value="{{ $existing->id }}">
                                                    <input type="text"
                                                           name="slab_name[]"
                                                           class="form-control text-center slab-name-input"
                                                           value="{{ $existing->slab_name }}"
                                                           maxlength="10"
                                                           required>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="from_amount[]"
                                                           class="form-control"
                                                           value="{{ $existing->from_amount }}"
                                                           min="0"
                                                           step="0.01"
                                                           required>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="to_amount[]"
                                                           class="form-control"
                                                           value="{{ $existing->to_amount }}"
                                                           min="0"
                                                           step="0.01"
                                                           required>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="incentive_amount[]"
                                                           class="form-control"
                                                           value="{{ $existing->incentive_amount }}"
                                                           min="0"
                                                           step="0.01"
                                                           required>
                                                </td>
                                                <td class="text-center">
                                                    @if (Auth::guard('admin')->check() ||
                                                        ($sideMenuPermissions->has('Slip Bound Incentives') && $sideMenuPermissions['Slip Bound Incentives']->contains('delete')))
                                                        <button type="button"
                                                                class="btn p-2 delete-slab-btn"
                                                                style="background-color: #609b90; color: #fff;"
                                                                title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            @foreach(['A','B','C','D','E'] as $slab)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="slab_id[]" value="">
                                                        <input type="text"
                                                               name="slab_name[]"
                                                               class="form-control text-center slab-name-input"
                                                               value="{{ $slab }}"
                                                               maxlength="10"
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               name="from_amount[]"
                                                               class="form-control"
                                                               min="0"
                                                               step="0.01"
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               name="to_amount[]"
                                                               class="form-control"
                                                               min="0"
                                                               step="0.01"
                                                               required>
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               name="incentive_amount[]"
                                                               class="form-control"
                                                               min="0"
                                                               step="0.01"
                                                               required>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                                class="btn p-2 delete-slab-btn"
                                                                style="background-color: #609b90; color: #fff;"
                                                                title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforelse
                                    </tbody>
                                </table>

                            </div>

                            <div class="card-footer text-center py-2">
                                <button type="submit" class="btn btn-primary mb-4">
                                    Save
                                </button>
                            </div>

                        </form>
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

    function nextSlabName() {
        var used = [];
        $('#slabsBody .slab-name-input').each(function () {
            used.push($.trim($(this).val()).toUpperCase());
        });

        for (var i = 0; i < 26; i++) {
            var letter = String.fromCharCode(65 + i);
            if (used.indexOf(letter) === -1) {
                return letter;
            }
        }

        return 'S' + (used.length + 1);
    }

    function buildRow(name, id) {
        id = id || '';
        return `
            <tr data-id="${id}">
                <td>
                    <input type="hidden" name="slab_id[]" value="${id}">
                    <input type="text"
                           name="slab_name[]"
                           class="form-control text-center slab-name-input"
                           value="${name}"
                           maxlength="10"
                           required>
                </td>
                <td>
                    <input type="number" name="from_amount[]" class="form-control" min="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="to_amount[]" class="form-control" min="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="incentive_amount[]" class="form-control" min="0" step="0.01" required>
                </td>
                <td class="text-center">
                    <button type="button"
                            class="btn p-2 delete-slab-btn"
                            style="background-color: #609b90; color: #fff;"
                            title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    $('#addSlabBtn').on('click', function () {
        var name = nextSlabName();
        $('#slabsBody').append(buildRow(name));
        $('#slabsBody tr:last .slab-name-input').focus();
    });

    $(document).on('click', '.delete-slab-btn', function () {
        var $row = $(this).closest('tr');
        var id = $.trim($row.find('input[name="slab_id[]"]').val() || '');

        Swal.fire({
            title: 'Are you sure you want to delete this slab?',
            text: 'If you delete this slab, it will be gone forever.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            // New unsaved row — just remove from UI
            if (!id) {
                $row.remove();
                return;
            }

            // Build from current path so it works with /limelight subdirectory
            // e.g. /limelight/admin/slab -> /limelight/admin/slab-delete/5
            var deleteUrl = window.location.pathname.replace(/\/slab\/?$/, '/slab-delete/') + id;

            $.ajax({
                url: deleteUrl,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function () {
                    $row.remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Slab deleted successfully.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                },
                error: function (xhr) {
                    var msg = 'Failed to delete the slab.';
                    if (xhr.status === 403) {
                        msg = 'You do not have permission to delete slabs.';
                    } else if (xhr.status === 404) {
                        msg = 'Delete route not found. Please deploy latest routes and run: php artisan route:clear';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', msg, 'error');
                }
            });
        });
    });

});
</script>
@endsection
