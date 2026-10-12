@extends('admin.layout.app')
@section('title', 'Edit Target Distribution')

@section('content')

<div class="main-content">
<section class="section">
<div class="section-body">

    <a class="btn btn-primary mb-3" href="{{ route('target.index') }}">Back</a>

    <form id="targetForm" action="{{ route('target.update', $target->id) }}" method="POST">
        @csrf
        @method('POST')

        <div class="card">
            <h4 class="text-center my-4">Edit Target Distribution</h4>

            <div class="row px-4">

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Month <span style="color: red;">*</span></label>
                        <select name="month" id="month" class="form-control" required>
                            <option value="">Select Month</option>
                            @foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $monthName)
                                <option value="{{ $monthName }}" {{ $target->month == $monthName ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Current Year <span style="color: red;">*</span></label>
                        <input type="text" class="form-control" value="{{ $target->year }}" disabled>
                        <input type="hidden" name="year" value="{{ $target->year }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Branch <span style="color: red;">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-control" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $target->branch_id == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Designation <span style="color: red;">*</span></label>
                        <select name="designation_id" id="designation_id" class="form-control" required>
                            <option value="">Select Designation</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Category <span style="color: red;">*</span></label>
                        <select name="categories[]" id="category" class="form-control" multiple required>
                            <option value="garments">Garments</option>
                            <option value="unstitched">Unstitched</option>
                            <option value="accessories">Accessories</option>
                        </select>
                    </div>
                </div>

                <div class="col-12" id="category-target-panels"></div>
            </div>

            <div class="card-footer text-center">
                <button class="btn btn-primary">Update Target</button>
            </div>
        </div>
    </form>

</div>
</section>
</div>

@endsection

@section('js')
@php
    $targetsData = \App\Models\Target::where('id', '!=', $target->id)
        ->select('branch_id', 'month', 'year', 'category')
        ->get();
@endphp

<script>
const targets = @json($targetsData);
const currentYear = {{ (int) $target->year }};
const currentBranchId = {{ $target->branch_id ?? 'null' }};
const currentDesignationId = {{ $target->designation_id ?? 'null' }};
const currentCategory = @json($target->category);
const currentTargetValues = {
    monthly_target: {{ (float) $target->monthly_target }},
    week_1: {{ (float) $target->week_1 }},
    week_2: {{ (float) $target->week_2 }},
    week_3: {{ (float) $target->week_3 }},
    week_4: {{ (float) $target->week_4 }}
};
const allCategories = [
    { value: 'garments', label: 'Garments' },
    { value: 'unstitched', label: 'Unstitched' },
    { value: 'accessories', label: 'Accessories' }
];

function assignedCategoriesFor(branchId, month) {
    return targets
        .filter(function (target) {
            return String(target.branch_id) === String(branchId)
                && target.month == month
                && String(target.year) == String(currentYear);
        })
        .map(function (target) {
            return String(target.category).toLowerCase();
        });
}

function categoryLabel(value) {
    const found = allCategories.find(function (cat) { return cat.value === value; });
    return found ? found.label : value;
}

function panelHtml(catValue) {
    const label = categoryLabel(catValue);
    return `
        <div class="card mb-3 category-target-panel" data-category="${catValue}" style="border:1px solid #c5d5d2;">
            <div class="card-header py-2" style="background:#f4f8f7;">
                <strong>${label} target</strong>
            </div>
            <div class="card-body pb-2">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label>Monthly Target <span style="color: red;">*</span></label>
                        <input type="number" name="targets[${catValue}][monthly_target]"
                            class="form-control cat-monthly" data-category="${catValue}" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold">1st Week (%) <span style="color: red;">*</span></label>
                        <input type="number" name="targets[${catValue}][week_1]" class="form-control week-input"
                            data-category="${catValue}" data-week="1" required>
                        <small class="text-success pieces" id="${catValue}_week_1_pieces">0</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold">2nd Week (%) <span style="color: red;">*</span></label>
                        <input type="number" name="targets[${catValue}][week_2]" class="form-control week-input"
                            data-category="${catValue}" data-week="2" required>
                        <small class="text-success pieces" id="${catValue}_week_2_pieces">0</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold">3rd Week (%) <span style="color: red;">*</span></label>
                        <input type="number" name="targets[${catValue}][week_3]" class="form-control week-input"
                            data-category="${catValue}" data-week="3" required>
                        <small class="text-success pieces" id="${catValue}_week_3_pieces">0</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold">4th Week (%) <span style="color: red;">*</span></label>
                        <input type="number" name="targets[${catValue}][week_4]" class="form-control week-input"
                            data-category="${catValue}" data-week="4" required>
                        <small class="text-success pieces" id="${catValue}_week_4_pieces">0</small>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function calculatePiecesFor(category) {
    const monthly = parseFloat($('.cat-monthly[data-category="' + category + '"]').val()) || 0;
    $('.week-input[data-category="' + category + '"]').each(function () {
        const percent = parseFloat($(this).val()) || 0;
        const week = $(this).data('week');
        const pieces = (monthly * percent) / 100;
        $('#' + category + '_week_' + week + '_pieces').text(Math.round(pieces) + ' products');
    });
}

function fillCurrentCategoryValues() {
    const cat = currentCategory;
    if (!cat) {
        return;
    }
    const $panel = $('.category-target-panel[data-category="' + cat + '"]');
    if (!$panel.length) {
        return;
    }
    $panel.find('.cat-monthly').val(currentTargetValues.monthly_target);
    $panel.find('.week-input[data-week="1"]').val(currentTargetValues.week_1);
    $panel.find('.week-input[data-week="2"]').val(currentTargetValues.week_2);
    $panel.find('.week-input[data-week="3"]').val(currentTargetValues.week_3);
    $panel.find('.week-input[data-week="4"]').val(currentTargetValues.week_4);
    calculatePiecesFor(cat);
}

function syncCategoryPanels() {
    const selected = $('#category').val() || [];
    const $wrap = $('#category-target-panels');

    $wrap.find('.category-target-panel').each(function () {
        const cat = $(this).data('category');
        if (selected.indexOf(String(cat)) === -1) {
            $(this).remove();
        }
    });

    selected.forEach(function (cat) {
        if ($wrap.find('.category-target-panel[data-category="' + cat + '"]').length === 0) {
            $wrap.append(panelHtml(cat));
            if (cat === currentCategory) {
                fillCurrentCategoryValues();
            }
        }
    });
}

function initCategorySelect() {
    if ($('#category').hasClass('select2-hidden-accessible')) {
        $('#category').select2('destroy');
    }
    $('#category').select2({
        width: '100%',
        placeholder: 'Select categories',
        closeOnSelect: false
    });
}

function refreshCategoryOptions(preselectCurrent) {
    const month = $('#month').val();
    const branchId = $('#branch_id').val();
    const previous = $('#category').val() || [];

    if ($('#category').hasClass('select2-hidden-accessible')) {
        $('#category').select2('destroy');
    }

    const assigned = (month && branchId) ? assignedCategoriesFor(branchId, month) : [];
    let options = '';

    allCategories.forEach(function (cat) {
        const isAssignedOther = assigned.indexOf(cat.value) !== -1;
        const isCurrent = cat.value === currentCategory;
        const disabledAttr = isAssignedOther ? ' disabled' : '';
        const label = isAssignedOther ? (cat.label + ' (Already Assigned)') : cat.label;
        const keepSelected = !isAssignedOther && (
            (preselectCurrent && isCurrent) || previous.indexOf(cat.value) !== -1
        );
        const selectedAttr = keepSelected ? ' selected' : '';
        options += '<option value="' + cat.value + '"' + disabledAttr + selectedAttr + '>' + label + '</option>';
    });

    $('#category').html(options);
    initCategorySelect();
    syncCategoryPanels();
}

$(document).on('input', '.cat-monthly, .week-input', function () {
    const category = $(this).data('category');
    if (category) {
        calculatePiecesFor(category);
    }
});

$('#targetForm').submit(function (e) {
    const selected = $('#category').val() || [];
    if (!selected.length) {
        e.preventDefault();
        toastr.error('Please select at least one category.');
        return false;
    }

    let invalid = false;
    selected.forEach(function (cat) {
        let totalPercent = 0;
        $('.week-input[data-category="' + cat + '"]').each(function () {
            totalPercent += parseFloat($(this).val()) || 0;
        });
        if (totalPercent > 100) {
            invalid = true;
            toastr.error(categoryLabel(cat) + ': weekly percentages cannot exceed 100%.');
        }
    });

    if (invalid) {
        e.preventDefault();
        return false;
    }
});

function loadDesignations(branchId) {
    if (branchId !== '') {
        $('#designation_id').prop('disabled', false);
        $.ajax({
            url: "{{ url('admin/get-branch-designations') }}/" + branchId,
            type: "GET",
            success: function (res) {
                let html = '<option value="">Select Designation</option>';
                res.forEach(function (item) {
                    html += '<option value="' + item.id + '"'
                        + (item.id == currentDesignationId ? ' selected' : '')
                        + '>' + item.name + '</option>';
                });
                $('#designation_id').html(html);
            }
        });
    } else {
        $('#designation_id').prop('disabled', true)
            .html('<option value="">Select Designation</option>');
    }
}

$(document).ready(function () {
    refreshCategoryOptions(true);
    loadDesignations(currentBranchId);

    $('#category').on('change', function () {
        syncCategoryPanels();
    });

    $('#month').on('change', function () {
        refreshCategoryOptions(false);
    });

    $('#branch_id').on('change', function () {
        loadDesignations($(this).val());
        refreshCategoryOptions(false);
    });
});
</script>
@endsection
