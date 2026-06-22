@extends('admin.layout.app')
@section('title', 'Hierarchy')

@section('content')
<div class="main-content">
<section class="section">
<div class="section-body">

<div class="card">
<div class="card-header">
    <h4>Create Hierarchy</h4>
</div>

<div class="card-body">

<form method="POST" action="{{ route('hierarchy.store') }}">
@csrf
<div class="form-group">
    <label>Select Region</label>
    <select name="region_id" id="region_id" class="form-control select2" required>
        <option value="">Select Region</option>

        @foreach($regions as $region)
            <option value="{{ $region->id }}">
                {{ $region->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Select ASM</label>

    <select name="asm_id" id="asm_id" class="form-control select2" required>
        <option value="">Select ASM</option>
    </select>
</div>

<div class="form-group">
    <label>Select Branch</label>

    <select name="branch_id" id="branch_id" class="form-control select2" required>
        <option value="">Select Branch</option>
    </select>
</div>

<div class="form-group">
    <label>Select Branch Managers</label>

    <select id="branchManagers"
            name="branch_managers[]"
            class="form-control select2"
            multiple>
    </select>
</div>

<hr>

{{-- 🔵 Staff Section --}}
<div id="staffContainer"></div>

<button class="btn btn-primary mt-3">Save Hierarchy</button>

</form>

</div>
</div>

{{-- 🟢 CLEAN HIERARCHY VIEW --}}
<div class="card mt-4">
    <div class="card-header">
        <h4>Current Hierarchy</h4>
    </div>

    <div class="card-body">

        @forelse($hierarchy as $asm)

            <div class="mb-4 p-4 border rounded bg-light shadow-sm">

                {{-- TOP HEADER --}}
                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>
                        {{-- REGION --}}
                        <h6 class="text-green mb-1">
                            <i data-feather="map-pin"></i>
                            Region:
                            <span class="font-weight-bold">
                                {{ $asm->region->name ?? 'N/A' }}
                            </span>
                        </h6>

                        {{-- ASM --}}
                        <h5 class="text-dark mb-0">
                            <i data-feather="user"></i>
                            ASM:
                            <span class="font-weight-bold">
                                {{ $asm->name }}
                            </span>
                        </h5>
                    </div>

                    {{-- REMOVE BUTTON --}}
                    <button class="btn btn-danger btn-sm delete-asm"
                        data-id="{{ $asm->id }}">
                        <i data-feather="trash-2"></i>
                        Remove
                    </button>

                </div>

                {{-- BRANCH MANAGERS --}}
                <div class="row">

                    @foreach($asm->branchManagers as $bm)

                        <div class="col-md-4 mb-3">

                            <div class="card border-0 shadow-sm h-100">

                                <div class="card-body">

                                    {{-- BRANCH --}}
                                    <h6 class="text-info mb-2">
                                        <i data-feather="home"></i>
                                        Branch:
                                        <span class="font-weight-bold">
                                            {{ $bm->branch->name ?? 'N/A' }}
                                        </span>
                                    </h6>

                                    {{-- BM --}}
                                    <h5 class="text-dark">
                                        <i data-feather="user-check"></i>
                                        {{ $bm->name }}
                                    </h5>

                                    <hr>

                                    {{-- STAFF HEADING --}}
                                    <h6 class="mb-2 text-muted">
                                        Sale Staff
                                    </h6>

                                    {{-- STAFF LIST --}}
                                    <div>

                                        @forelse($bm->saleStaff as $staff)

                                            <span class="badge badge-success mb-1 p-2">
                                                <i data-feather="users" style="width:14px;height:14px;"></i>
                                                {{ $staff->name }}
                                            </span>

                                        @empty

                                            <span class="text-muted">
                                                No staff assigned
                                            </span>

                                        @endforelse

                                    </div>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        @empty

            <div class="text-center text-muted py-4">
                No hierarchy created yet.
            </div>

        @endforelse

    </div>
</div>

</div>
</section>
</div>
@endsection

@section('js')

{{-- ✅ Select2 CDN --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>

$(document).ready(function () {

    // =========================================
    // SELECT2
    // =========================================

    $('.select2').select2({
        width: '100%',
        placeholder: "Select options"
    });

    // =========================================
    // REGION → ASM
    // =========================================

    $('#region_id').change(function () {

    let regionId = $(this).val();


    // Reset dropdowns
    $('#asm_id').html('<option value="">Loading...</option>');
    $('#branch_id').html('<option value="">Loading...</option>');
    $('#branchManagers').html('');
    $('#staffContainer').html('');

    // ========================
    // LOAD ASM
    // ========================

    $.ajax({

        url: "{{ url('admin/get-region-asms') }}/" + regionId,

        type: 'GET',

        success: function (response) {

            let options = '<option value="">Select ASM</option>';

            response.forEach(function (asm) {

                options += `
                    <option value="${asm.id}">
                        ${asm.name}
                    </option>
                `;

            });

            $('#asm_id').html(options);

        }

    });

    // ========================
    // LOAD BRANCHES
    // ========================

 $.ajax({
        url: "{{ url('admin/get-region-branches') }}/" + regionId,
        type: 'GET',
        success: function(response) {

            console.log(response);

            let options = '<option value="">Select Branch</option>';

            response.forEach(function(branch) {
                options += `<option value="${branch.id}">${branch.name}</option>`;
            });

            $('#branch_id').html(options);
        }
    });

});
    // =========================================
    // BRANCH → BRANCH MANAGERS
    // =========================================

    $('#branch_id').change(function () {

        let branchId = $(this).val();

        $('#branchManagers').html('');

        $.ajax({

            url: "{{ url('admin/get-branch-managers') }}/" + branchId,

            type: 'GET',

            success: function (response) {

                let options = '';

                response.forEach(function (bm) {

                    options += `
                        <option value="${bm.id}"
                                data-name="${bm.name}">
                            ${bm.name}
                        </option>
                    `;

                });

                $('#branchManagers').html(options);

                $('#branchManagers').trigger('change');

            }

        });

    });

    // =========================================
    // HIERARCHY DATA
    // =========================================

    const saleStaff = @json($saleStaff);

    const hierarchyData = @json($hierarchy);

    let assignedStaff = [];

    hierarchyData.forEach(asm => {

        asm.branch_managers.forEach(bm => {

            bm.sale_staff.forEach(staff => {

                assignedStaff.push(staff.id);

            });

        });

    });

    // =========================================
    // BRANCH MANAGER CHANGE
    // =========================================

    $('#branchManagers').change(function () {

        let selected = $(this).find(':selected');

        let container = $('#staffContainer');

        container.html('');

        selected.each(function () {

            let bmId = $(this).val();

            let bmName = $(this).data('name');

            let html = `

                <div class="card mb-3">

                    <div class="card-body">

                        <label class="font-weight-bold mb-2">
                            Assign Staff for: ${bmName}
                        </label>

                        <select name="staff[${bmId}][]"
                                class="form-control select2 staff-select"
                                multiple
                                required>

                            ${saleStaff.map(staff => {

                                let disabled = assignedStaff.includes(staff.id)
                                    ? 'disabled'
                                    : '';

                                return `
                                    <option value="${staff.id}" ${disabled}>
                                        ${staff.name}
                                        ${disabled ? '(Assigned)' : ''}
                                    </option>
                                `;

                            }).join('')}

                        </select>

                    </div>

                </div>
            `;

            container.append(html);

        });

        $('.select2').select2({
            width: '100%'
        });

        updateStaffRestrictions();

    });

    // =========================================
    // STAFF RESTRICTIONS
    // =========================================

    function updateStaffRestrictions() {

    let selectedStaff = [];

    // collect currently selected staff
    $('.staff-select').each(function () {

        let values = $(this).val();

        if(values) {

            selectedStaff = selectedStaff.concat(
                values.map(Number)
            );

        }

    });

    $('.staff-select').each(function () {

        let currentSelect = $(this);

        let currentValues = (currentSelect.val() || []).map(Number);

        currentSelect.find('option').each(function () {

            let optionVal = Number($(this).val());

            // already permanently assigned
            let permanentlyAssigned = assignedStaff.includes(optionVal);

            // selected in another dropdown
            let selectedElsewhere =
                selectedStaff.includes(optionVal)
                &&
                !currentValues.includes(optionVal);

            if (permanentlyAssigned || selectedElsewhere) {

                $(this).prop('disabled', true);

            } else {

                $(this).prop('disabled', false);

            }

        });

    });

    $('.staff-select').select2({
        width: '100%'
    });

}

    $(document).on('change', '.staff-select', function () {

        updateStaffRestrictions();

    });

    // =========================================
    // DELETE HIERARCHY
    // =========================================

    $(document).on('click', '.delete-asm', function () {

        let id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'This will remove the hierarchy.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Remove'
        }).then((result) => {

            if(result.isConfirmed)
            {
                $.post("{{ url('/admin/hierarchy/remove-asm') }}", {

                    _token: "{{ csrf_token() }}",

                    id: id

                }, function () {

                    location.reload();

                });
            }

        });

    });

    feather.replace();

});

</script>

@endsection