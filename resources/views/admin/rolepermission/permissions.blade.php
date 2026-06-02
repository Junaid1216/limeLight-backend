@extends('admin.layout.app')
@section('title', 'Assign Permissions')
@section('content')

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

    @php
        $permissionData = getPermissionsData($roles->id);
        $roles = $permissionData['roles'];
        $permissions = $permissionData['permissions'];
        $sideMenus = $permissionData['sideMenus'];
        $existingPermissions = $permissionData['existingPermissions'];
        $sideMenuPermissions = $permissionData['sideMenuPermissions'];
    @endphp

    <div class="main-content">
        <section class="section">
            <div class="section-body">
                <h2>Edit Permissions for Role: {{ $roles->name }}</h2>

                <form id="edit_permissions" action="{{ route('roles.permissions.store', $roles->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $roles->id }}">

                    @foreach ($sideMenus as $menu)
                        @php
                            $hasPermission = collect($existingPermissions)->contains(function ($perm) use ($menu) {
                                return $perm['side_menue_id'] == $menu->id;
                            });

                            $actions = collect($sideMenuPermissions[$menu->id] ?? [])
                                ->pluck('permission.name')
                                ->toArray();
                        @endphp

                        <div class="card mb-3">
                            <div class="border-bottom-0 card-header d-flex align-items-center"
                                style="padding-bottom: 8px !important;padding-left: 40px!important">
                                <input type="checkbox" class="form-check-input menu-toggle me-2"
                                    data-target="#perm-{{ $menu->id }}" id="menu-{{ $menu->id }}"
                                    @if ($hasPermission) checked @endif>
                                <label class="form-check-label main-permission" for="menu-{{ $menu->id }}">
                                    <strong>{{ $loop->iteration }} - {{ ucfirst($menu->name) }}</strong>
                                </label>
                            </div>

                            <div class="px-3 py-2">
                                @foreach ($sideMenuPermissions[$menu->id] ?? [] as $permItem)
                                    <span class="badge bg-success text-white me-1">
                                        {{ ucfirst($permItem->permission->name) }}
                                    </span>
                                @endforeach
                            </div>

                            <div id="perm-{{ $menu->id }}" class="card-body permissions-section"
                                style="@if (!$hasPermission) display: none; @endif">
                                @foreach ($actions as $action)
                                    @php
                                        $hasAction = collect($existingPermissions)->contains(function ($perm) use (
                                            $menu,
                                            $action,
                                        ) {
                                            return $perm['side_menue_id'] == $menu->id &&
                                                $perm['permission']['name'] == $action;
                                        });
                                    @endphp

                                    <div class="form-check sub-permissions">
                                        <input type="checkbox" class="form-check-input"
                                            name="permissions[{{ $menu->id }}][]" value="{{ $action }}"
                                            id="{{ $action }}-{{ $menu->id }}"
                                            @if ($hasAction) checked @endif>
                                        <label class="form-check-label" for="{{ $action }}-{{ $menu->id }}">
                                            {{ ucfirst($action) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="card-footer text-center">
                        <button type="submit" class="btn btn-primary">Update Permissions</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@section('js')
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
@endsection
