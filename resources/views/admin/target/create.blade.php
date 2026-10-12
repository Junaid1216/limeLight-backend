@extends('admin.layout.app')
@section('title', 'Target Distribution')

@section('content')

<div class="main-content">
<section class="section">
<div class="section-body">

    <a class="btn btn-primary mb-3" href="{{ route('target.index') }}">Back</a>

    <form id="targetForm" action="{{ route('target.store') }}" method="POST">
        @csrf

        <div class="card">
            <h4 class="text-center my-4">Target Distribution</h4>

            <div class="row px-4">

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Month <span style="color: red;">*</span></label>
                        <select name="month" id="month" class="form-control" required>
                            <option value="">Select Month</option>
                            <option value="January">January</option>
                            <option value="February">February</option>
                            <option value="March">March</option>
                            <option value="April">April</option>
                            <option value="May">May</option>
                            <option value="June">June</option>
                            <option value="July">July</option>
                            <option value="August">August</option>
                            <option value="September">September</option>
                            <option value="October">October</option>
                            <option value="November">November</option>
                            <option value="December">December</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Current Year</label>
                        <input type="text" class="form-control" value="{{ date('Y') }}" disabled>
                        <input type="hidden" name="year" value="{{ date('Y') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Branch <span style="color: red;">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-control" disabled>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Designation <span style="color: red;">*</span></label>
                        <select name="designation_id" id="designation_id" class="form-control" disabled>
                            <option value="">Select Designation</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Category <span style="color: red;">*</span></label>
                        <select name="categories[]" id="category" class="form-control" multiple required disabled>
                            <option value="garments">Garments</option>
                            <option value="unstitched">Unstitched</option>
                            <option value="accessories">Accessories</option>
                        </select>
                    </div>
                </div>

                <div class="col-12" id="category-target-panels"></div>
            </div>

            <div class="card-footer text-center">
                <button class="btn btn-primary">Save Target</button>
            </div>
        </div>
    </form>

</div>
</section>
</div>

@endsection

@section('js')
<script>
const targets = @json($targets);
const branches = @json($branchOptions);
const currentYear = {{ (int) date('Y') }};
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

function branchHasAvailableCategory(branchId, month) {
    const assigned = assignedCategoriesFor(branchId, month);
    return allCategories.some(function (cat) {
        return assigned.indexOf(cat.value) === -1;
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

function refreshCategoryOptions() {
    const month = $('#month').val();
    const branchId = $('#branch_id').val();
    const previous = $('#category').val() || [];

    if ($('#category').hasClass('select2-hidden-accessible')) {
        $('#category').select2('destroy');
    }

    let options = '';
    const assigned = (month && branchId) ? assignedCategoriesFor(branchId, month) : [];

    allCategories.forEach(function (cat) {
        const isAssigned = assigned.indexOf(cat.value) !== -1;
        const disabledAttr = isAssigned ? ' disabled' : '';
        const label = isAssigned ? (cat.label + ' (Already Assigned)') : cat.label;
        const keepSelected = !isAssigned && previous.indexOf(cat.value) !== -1;
        const selectedAttr = keepSelected ? ' selected' : '';
        options += '<option value="' + cat.value + '"' + disabledAttr + selectedAttr + '>' + label + '</option>';
    });

    $('#category').html(options);

    const enable = !!(month && branchId);
    $('#category').prop('disabled', !enable);
    initCategorySelect();
    if (!enable) {
        $('#category').val(null).trigger('change');
        $('#category-target-panels').empty();
        return;
    }
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

$(document).ready(function () {
    initCategorySelect();

    $('#category').on('change', function () {
        syncCategoryPanels();
    });

    $('#month').on('change', function () {
        const selectedMonth = $(this).val();
        let branchHtml = '<option value="">Select Branch</option>';

        $('#designation_id').html('<option value="">Select Designation</option>').prop('disabled', true);

        if (selectedMonth !== '') {
            $('#branch_id').prop('disabled', false);
            branches.forEach(function (branch) {
                if (branchHasAvailableCategory(branch.id, selectedMonth)) {
                    branchHtml += '<option value="' + branch.id + '">' + branch.name + '</option>';
                }
            });
            $('#branch_id').html(branchHtml);
        } else {
            $('#branch_id').prop('disabled', true).html('<option value="">Select Branch</option>');
        }

        refreshCategoryOptions();
    });

    $('#branch_id').on('change', function () {
        const branchId = $(this).val();
        refreshCategoryOptions();

        if (branchId !== '') {
            $('#designation_id').prop('disabled', false);
            $.ajax({
                url: "{{ url('admin/get-branch-designations') }}/" + branchId,
                type: "GET",
                success: function (res) {
                    let options = '<option value="">Select Designation</option>';
                    res.forEach(function (item) {
                        options += '<option value="' + item.id + '">' + item.name + '</option>';
                    });
                    $('#designation_id').html(options);
                }
            });
        } else {
            $('#designation_id').prop('disabled', true).html('<option value="">Select Designation</option>');
        }
    });
});
</script>
@endsection
