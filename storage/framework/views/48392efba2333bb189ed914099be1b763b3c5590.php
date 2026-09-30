
<?php $__env->startSection('title', 'Feedback'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="mb-0">Feedback Submissions</h4>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="<?php echo e(route('feedback.index')); ?>" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-3">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="all" <?php echo e($type === 'all' ? 'selected' : ''); ?>>All</option>
                                            <option value="staff" <?php echo e($type === 'staff' ? 'selected' : ''); ?>>Sales Staff</option>
                                            <option value="branch_manager" <?php echo e($type === 'branch_manager' ? 'selected' : ''); ?>>Branch Manager</option>
                                            <option value="asm" <?php echo e($type === 'asm' ? 'selected' : ''); ?>>ASM</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Type</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Branch / Region</th>
                                            <th>Feedback</th>
                                            <th>Submitted At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $feedbacks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <?php
                                                $userType = $feedback->user_type;
                                                if (!$userType) {
                                                    $userType = filled($feedback->region_name)
                                                        ? 'area_sale_manager'
                                                        : 'sale_staff';
                                                }

                                                if ($userType === 'area_sale_manager') {
                                                    $typeLabel = 'ASM';
                                                    $badgeClass = 'badge-info';
                                                    $location = $feedback->region_name ?: '-';
                                                } elseif ($userType === 'branch_manager') {
                                                    $typeLabel = 'Branch Manager';
                                                    $badgeClass = 'badge-warning';
                                                    $location = $feedback->branch_name ?: '-';
                                                } else {
                                                    $typeLabel = 'Sales Staff';
                                                    $badgeClass = 'badge-primary';
                                                    $location = $feedback->branch_name ?: '-';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo e($loop->iteration); ?></td>
                                                <td>
                                                    <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($typeLabel); ?></span>
                                                </td>
                                                <td><?php echo e($feedback->code ?: '-'); ?></td>
                                                <td><?php echo e($feedback->name ?: '-'); ?></td>
                                                <td><?php echo e($location); ?></td>
                                                <td style="white-space: pre-wrap;"><?php echo e($feedback->feedback); ?></td>
                                                <td><?php echo e($feedback->created_at ? $feedback->created_at->copy()->timezone('Asia/Karachi')->format('d M Y, h:i A') : '-'); ?></td>
                                                <td>
                                                    <form id="delete-form-<?php echo e($feedback->id); ?>"
                                                        action="<?php echo e(route('feedback.destroy', $feedback->id)); ?>"
                                                        method="POST"
                                                        style="display:inline;">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm delete-btn"
                                                            data-form="delete-form-<?php echo e($feedback->id); ?>"
                                                            title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">No feedback submissions found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<script>
    $(document).ready(function () {
        if ($('#table_id_events tbody tr').length && !$('#table_id_events tbody tr td[colspan]').length) {
            $('#table_id_events').DataTable();
        }

        $(document).on('click', '.delete-btn', function () {
            var formId = $(this).data('form');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This feedback will be deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it'
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/feedback/index.blade.php ENDPATH**/ ?>