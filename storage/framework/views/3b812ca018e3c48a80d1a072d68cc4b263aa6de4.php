<?php $__env->startSection('title', 'Assigned Targets'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Assigned Targets</h4>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="<?php echo e(route('assigned.target.index')); ?>" class="mb-4">
                                <div class="row align-items-end">
                                    <div class="col-md-3 mb-3">
                                        <label>Status</label>
                                        <select name="status" class="form-control" required>
                                            <option value="pending" <?php echo e($status === 'pending' ? 'selected' : ''); ?>>Pending</option>
                                            <option value="approved" <?php echo e($status === 'approved' ? 'selected' : ''); ?>>Approved</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Month</label>
                                        <select name="month" class="form-control" required>
                                            <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($m); ?>" <?php echo e($month === $m ? 'selected' : ''); ?>><?php echo e($m); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label>Year</label>
                                        <select name="year" class="form-control" required>
                                            <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($y); ?>" <?php echo e((string) $year === (string) $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                    </div>
                                </div>
                            </form>

                            <?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="card mb-4 border">
                                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <h5 class="mb-1"><?php echo e($group['branch_name']); ?></h5>
                                            <small class="text-muted">
                                                Branch Manager: <?php echo e($group['branch_manager_name']); ?>

                                                | Designation: <?php echo e($group['designation']); ?>

                                                | <?php echo e($group['month']); ?> <?php echo e($group['year']); ?>

                                                | Staff: <?php echo e($group['staff_count']); ?>

                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center" style="gap:10px;">
                                            <?php if($group['status'] === 'approved'): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                                <form method="POST" action="<?php echo e(route('assigned.target.approve')); ?>" class="approve-form">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="branch_id" value="<?php echo e($group['branch_id']); ?>">
                                                    <input type="hidden" name="branch_manager_id" value="<?php echo e($group['branch_manager_id']); ?>">
                                                    <input type="hidden" name="month" value="<?php echo e($group['month']); ?>">
                                                    <input type="hidden" name="year" value="<?php echo e($group['year']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0 font-weight-bold">Monthly Target Assignment</h6>
                                                <small class="text-muted">Distribute Among Sales Staff</small>
                                            </div>
                                            <span class="badge" style="background:#0d9488;color:#fff;border-radius:8px;padding:6px 10px;">
                                                <?php echo e(count($group['staff'])); ?>

                                            </span>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Garments</th>
                                                        <th>Unstitched</th>
                                                        <th>Accessories</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__empty_2 = true; $__currentLoopData = $group['staff']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center" style="gap:10px;">
                                                                    <span style="width:36px;height:36px;border-radius:50%;background:#f3d9c4;color:#8a4b2d;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">
                                                                        <?php echo e($staff['initials']); ?>

                                                                    </span>
                                                                    <span><?php echo e($staff['name']); ?></span>
                                                                </div>
                                                            </td>
                                                            <td><?php echo e(number_format($staff['garments'])); ?></td>
                                                            <td><?php echo e(number_format($staff['unstitched'])); ?></td>
                                                            <td><?php echo e(number_format($staff['accessories'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">No staff targets found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="alert alert-info mb-0">
                                    No assigned targets found from the app yet.
                                </div>
                            <?php endif; ?>
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
    $('.approve-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Approve targets?',
            text: 'This will mark all targets in this assignment as approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#609b90',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/assignedtarget/index.blade.php ENDPATH**/ ?>