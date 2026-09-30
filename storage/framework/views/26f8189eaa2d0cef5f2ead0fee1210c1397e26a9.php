<?php $__env->startSection('title', 'Roles'); ?>

<?php $__env->startSection('content'); ?>
    <div class="main-content" style="min-height: 562px;">
        <section class="section">
            <div class="section-body">
                <div class="row">

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Roles</h4>
                            </div>
                            <div class="card-body table-striped table-bordered table-responsive">
                                <?php if(Auth::guard('admin')->check() ||
                                        ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('create'))): ?>
                                    <a class="btn btn-primary mb-3 text-white"
                                        href="<?php echo e(url('admin/roles-create')); ?>">Create</a>
                                <?php endif; ?>

                                <table class="table" id="table_id_events">
                                    <thead>
                                        <tr>
                                            <th>Sr.</th>
                                            <th>Name</th>
                                            
                                            <th>Permissions</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($loop->iteration); ?></td>
                                                <td>
                                                    <?php echo e($role->name); ?>

                                                </td>
                                                
                                                <td>
                                                    <?php if(Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('view'))): ?>
                                                        <a class="btn" style="background-color: #609b90;"
                                                            href="<?php echo e(route('role.permissions', $role->id)); ?>"><i
                                                                class="fa fa-eye"></i></a>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if(Auth::guard('admin')->check() ||
                                                            ($sideMenuPermissions->has('Roles') && $sideMenuPermissions['Roles']->contains('delete'))): ?>
                                                        <!-- Delete Form -->
                                                        <form id="delete-form-<?php echo e($role->id); ?>"
                                                            action="<?php echo e(route('delete.role', $role->id)); ?>" method="POST">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?> <!-- Spoof DELETE method -->
                                                        </form>


                                                        <!-- Delete Button -->
                                                        <button class="show_confirm btn d-flex gap-4"
                                                            style="background-color: #609b90;"
                                                            data-form="delete-form-<?php echo e($role->id); ?>" type="button">
                                                            <span><i class="fa fa-trash"></i></span>

                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div> <!-- /.card-body -->
                        </div> <!-- /.card -->
                    </div> <!-- /.col -->
                </div> <!-- /.row -->
            </div> <!-- /.section-body -->
        </section>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('js'); ?>

    <?php if(\Illuminate\Support\Facades\Session::has('message')): ?>
        <script>
            toastr.success('<?php echo e(\Illuminate\Support\Facades\Session::get('message')); ?>');
        </script>
    <?php endif; ?>

    <script type="text/javascript">
        $(document).ready(function() {

            // ✅ DataTable initialize
            if ($.fn.DataTable.isDataTable('#table_id_events')) {
                $('#table_id_events').DataTable().destroy();
            }
            $('#table_id_events').DataTable();

            // ✅ Delete alert confirmation
            $(document).on('click', '.show_confirm', function(event) {
                event.preventDefault();
                var formId = $(this).data("form");
                var form = document.getElementById(formId);

                Swal.fire({
                    title: 'Are you sure you want to delete this record?',
                    text: "If you delete this Role record, it will be gone forever.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.action,
                            method: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: '<?php echo e(csrf_token()); ?>'
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Record deleted successfully.',
                                    showConfirmButton: false,
                                    timer: 2000
                                }).then(() => location.reload());
                            },
                            error: function() {
                                Swal.fire('Error!', 'Failed to delete the record.',
                                    'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\limelight-sales-perfomance\resources\views/admin/rolepermission/index.blade.php ENDPATH**/ ?>