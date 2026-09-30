<?php $__env->startSection('title', 'Assign Permissions'); ?>
<?php $__env->startSection('content'); ?>

    <style>
        .permissions-container {
            display: flex;
            flex-direction: column;
        }

        .sub-permissions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-left: 30px;
        }

        .form-check-label.main-permission {
            font-weight: bold;
        }

        .form-check {
            margin-bottom: 10px;
        }

        .d-none {
            display: none;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, .125);
        }

        .card {
            margin-bottom: 1rem;
        }

        .badge {
            font-size: 0.85rem;
        }
    </style>

    <?php
        $permissionData = getPermissionsData($roles->id);
        $roles = $permissionData['roles'];
        $permissions = $permissionData['permissions'];
        $sideMenus = $permissionData['sideMenus'];
        $existingPermissions = $permissionData['existingPermissions'];
        $sideMenuPermissions = $permissionData['sideMenuPermissions'];
    ?>

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <h2>Edit Permissions for Role: <?php echo e($roles->name); ?></h2>

                <form id="edit_permissions" action="<?php echo e(route('roles.permissions.store', $roles->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="role_id" value="<?php echo e($roles->id); ?>">

                    <?php $__currentLoopData = $sideMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $hasPermission = collect($existingPermissions)->contains(function ($perm) use ($menu) {
                                return $perm['side_menu_id'] == $menu->id;
                            });

                            $actions = collect($sideMenuPermissions[$menu->id] ?? [])
                                ->pluck('permission.name')
                                ->toArray();
                        ?>

                        <div class="card mb-3">
                            <div class="border-bottom-0 card-header d-flex align-items-center"
                                style="padding-bottom: 8px !important;padding-left: 40px!important">
                                <input type="checkbox" class="form-check-input menu-toggle me-2"
                                    data-target="#perm-<?php echo e($menu->id); ?>" id="menu-<?php echo e($menu->id); ?>"
                                    <?php if($hasPermission): ?> checked <?php endif; ?>>
                                <label class="form-check-label main-permission" for="menu-<?php echo e($menu->id); ?>">
                                    <strong><?php echo e($loop->iteration); ?> - <?php echo e(ucfirst($menu->name)); ?></strong>
                                </label>
                            </div>

                            <div class="px-3 py-2">
                                <?php $__currentLoopData = $sideMenuPermissions[$menu->id] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="badge bg-success text-white me-1">
                                        <?php echo e(ucfirst($permItem->permission->name)); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>

                            <div id="perm-<?php echo e($menu->id); ?>" class="card-body permissions-section"
                                style="<?php if(!$hasPermission): ?> display: none; <?php endif; ?>">
                                <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $hasAction = collect($existingPermissions)->contains(function ($perm) use (
                                            $menu,
                                            $action,
                                        ) {
                                            return $perm['side_menu_id'] == $menu->id &&
                                                $perm['permission']['name'] == $action;
                                        });
                                    ?>

                                    <div class="form-check sub-permissions">
                                        <input type="checkbox" class="form-check-input"
                                            name="permissions[<?php echo e($menu->id); ?>][]" value="<?php echo e($action); ?>"
                                            id="<?php echo e($action); ?>-<?php echo e($menu->id); ?>"
                                            <?php if($hasAction): ?> checked <?php endif; ?>>
                                        <label class="form-check-label" for="<?php echo e($action); ?>-<?php echo e($menu->id); ?>">
                                            <?php echo e(ucfirst($action)); ?>

                                        </label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <div class="card-footer text-center">
                        <button type="submit" class="btn btn-primary">Update Permissions</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        $(document).ready(function() {
            $('.menu-toggle').on('change', function() {
                const target = $(this).data('target');
                const $permissionsSection = $(target);

                if ($(this).is(':checked')) {
                    $permissionsSection.show();
                    $permissionsSection.find('input[type="checkbox"]').prop('disabled', false);
                } else {
                    $permissionsSection.hide();
                    $permissionsSection.find('input[type="checkbox"]').prop('checked', false);
                }
            });

            $('.menu-toggle').each(function() {
                const target = $(this).data('target');
                const $permissionsSection = $(target);

                if ($(this).is(':checked')) {
                    $permissionsSection.show();
                    $permissionsSection.find('input[type="checkbox"]').prop('disabled', false);
                } else {
                    $permissionsSection.hide();
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/rolepermission/permissions.blade.php ENDPATH**/ ?>