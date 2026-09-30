<?php $__env->startSection('title', 'Announcements'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-content" style="min-height: 562px;">
    <section class="section">
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Announcements</h4>
                        </div>

                        <form action="<?php echo e(route('announcement.store')); ?>" method="POST" id="announcementForm">
                            <?php echo csrf_field(); ?>
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
                                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Send To Roles <span style="color: red;">*</span></label>
                                            <select name="roles[]" id="roles" class="form-control select2" multiple required>
                                                <?php $__currentLoopData = $roleOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr data-kind="<?php echo e($row['kind']); ?>">
                                            <td><?php echo e($loop->iteration); ?></td>
                                            <td>
                                                <?php if($row['kind'] === 'notification'): ?>
                                                    <span class="badge badge-info">Notification</span>
                                                <?php else: ?>
                                                    <span class="badge badge-primary">Announcement</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:#0d9488;color:#fff;border-radius:12px;padding:5px 10px;">
                                                    <?php echo e($row['category_label'] ?: '-'); ?>

                                                </span>
                                            </td>
                                            <td><?php echo e($row['title']); ?></td>
                                            <td><?php echo e(\Illuminate\Support\Str::limit($row['description'], 80)); ?></td>
                                            <td>
                                                <?php if($row['kind'] === 'notification'): ?>
                                                    <div><strong>Audience:</strong> <?php echo e($row['roles']); ?></div>
                                                    <div class="text-muted" style="font-size:12px;">
                                                        <?php echo e(\Illuminate\Support\Str::limit($row['users'], 60)); ?>

                                                    </div>
                                                <?php else: ?>
                                                    <?php echo e($row['roles'] ?: '-'); ?>

                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($row['status_active']): ?>
                                                    <span class="badge badge-success"><?php echo e($row['status']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary"><?php echo e($row['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo e($row['created_at'] ? $row['created_at']->copy()->timezone('Asia/Karachi')->format('d M Y, h:i A') : '-'); ?>

                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap:6px;">
                                                    <?php if($row['kind'] === 'announcement' && !empty($row['edit'])): ?>
                                                        <button type="button"
                                                            class="btn btn-primary p-2 editAnnouncement"
                                                            style="background-color:#609b90;"
                                                            data-id="<?php echo e($row['edit']['id']); ?>"
                                                            data-category="<?php echo e($row['edit']['category']); ?>"
                                                            data-title="<?php echo e($row['edit']['title']); ?>"
                                                            data-description="<?php echo e($row['edit']['description']); ?>"
                                                            data-roles='<?php echo json_encode($row['edit']['roles'], 15, 512) ?>'
                                                            data-date="<?php echo e($row['edit']['date']); ?>"
                                                            data-status="<?php echo e($row['edit']['status']); ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <form id="delete-form-<?php echo e($row['kind']); ?>-<?php echo e($row['id']); ?>"
                                                        action="<?php echo e($row['delete_route']); ?>"
                                                        method="POST">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                    </form>
                                                    <button class="show_confirm btn p-2"
                                                        style="background-color:#609b90;"
                                                        data-form="delete-form-<?php echo e($row['kind']); ?>-<?php echo e($row['id']); ?>"
                                                        data-kind="<?php echo e($row['kind']); ?>"
                                                        type="button">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No announcements or notifications found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
    const usersByRolesUrl = <?php echo json_encode(route('announcement.users.by.roles'), 15, 512) ?>;

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

        $('#announcementForm').attr('action', '<?php echo e(url('admin/announcements/update')); ?>/' + id);
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
        $('#announcementForm').attr('action', '<?php echo e(route('announcement.store')); ?>');
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/announcement/index.blade.php ENDPATH**/ ?>