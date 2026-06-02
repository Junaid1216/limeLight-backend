@extends('admin.layout.app')
@section('title', 'Dashboard')
@section('content')


    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="row mb-3">
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="{{ route('asm.index') }}" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total ASM</h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalASM }}
                                            </h2>
                                            {{-- <p class="mb-0"><span class="col-green">10%</span> Increase</p> --}}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/1.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="{{ route('branch.manager.index') }}" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total Branch Managers</h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalBranchManagers }}
                                            </h2>
                                            {{-- <p class="mb-0"><span class="col-green">10%</span> Increase</p> --}}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/1.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="{{ route('sale.staff.index') }}" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total Sales Staff</h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalSalesStaff }}
                                            </h2>
                                            {{-- <p class="mb-0"><span class="col-green">10%</span> Increase</p> --}}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/1.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div>
                {{-- <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                     <a href="{{ route('notification.index') }}" style="text-decoration: none; color: inherit;">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">

                                                Total Notifications</h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalFarmers }}
                                            </h2>
                                            <p class="mb-0"><span class="col-green">10%</span> Increase</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/1.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                     </a>
                </div> --}}
                {{-- <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15"> Total Users</h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalDealers }}
                                            </h2>
                                            <p class="mb-0"><span class="col-orange">09%</span> Decrease</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/2.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <div class="card">
                        <div class="card-statistic-4">
                            <div class="align-items-center justify-content-between">
                                <div class="row ">
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                        <div class="card-content">
                                            <h5 class="font-15">Total FAQ's </h5>
                                            <h2 class="mb-3 font-18">
                                                {{ $totalInsuranceCrops }}
                                            </h2>
                                            <p class="mb-0"><span class="col-green">18%</span>
                                                Increase</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                        <div class="banner-img">
                                            <img src="{{ asset('public/admin/assets/img/banner/3.png') }}" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </section>
    </div>
@endsection
