<?php

use App\Models\UsersSubscriptions;
use App\Models\SubscriptionHistories;
use Illuminate\Support\Facades\Auth;
?>
<x-home-layout title="User Profile">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
    </style>

    <style>
        #password_rules ul li {
            /*font-size: 12px;
        font-weight: normal;*/
            color: #fff;
        }

        #password_rules ul li.complete {
            color: #5DB406;
        }

        span.togglePassword {
            color: gray;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
    <style>
        /* theme-classes */
        .back-black {
            background-color: rgb(11, 35, 53);
        }

        .white-b {
            background-color: rgb(158, 186, 208);
        }

        .bg-1 {
            background-color: #24303F;
        }

        .i-bg {
            background-color: #122741c0;
        }

        .wht {
            color: white;
        }

        .gry {
            color: gray;
        }

        /* main-box */
        .flx-box {
            min-width: 100%;
            height: 100vh;
        }

        /* main-navbar */
        .nbar {
            height: 10%;

            ::placeholder {
                color: gray;
            }

            i {
                height: auto;
                width: 45px;
                background-color: #152e4ea9;
                border-radius: 50%;
            }
        }

        .profile-pic {
            height: 45px;
            width: 45px;
            border-radius: 50%;
        }

        .dropdown-menu li a {
            color: white;
        }

        .dropdown-menu li a:hover {
            background-color: #1c3554;
            color: rgb(255, 255, 255);

        }

        .toast-success {
            background-color: #4CAF50 !important;
            /* Green */
        }

        .toast-error {
            background-color: #f44336 !important;
            /* Red */
        }

        .card-primary {
            background-color: rgb(36 43 63) !important;
            color: #fff !important;
            box-shadow: #fff;
        }

        .invalid-feedback {
            margin-left: 200px;
        }

        /* subscription history */

        .subscription-history {
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .subscription-history h3 {
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }

        .subscription-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .subscription-table th, .subscription-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }


        .badge {
            display: inline-block;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
            color: #fff;
        }

        .badge-active {
            background-color: #28a745; /* Green */
        }

        .badge-trialing {
            background-color: #ffc107; /* Yellow */
        }

        .badge-cancelled {
            background-color: #dc3545; /* Red */
        }

        .badge-inactive {
            background-color: #6c757d; /* Gray */
        }

        .badge-trial {
            background-color: #17a2b8; /* Cyan */
        }

        .badge-no-trial {
            background-color: #6c757d; /* Gray */
        }

        .badge-discount {
            background-color: #007bff; /* Blue */
        }

        .badge-no-discount {
            background-color: #6c757d; /* Gray */
        }


    </style>
    @endsection
    <div class="back-back">
        <div class="flx-box container">
            <div class="row h-100 position-relative">
                <!-- <div class=" side-bar col-2 h-100 bg-1 wht text-center overflow-scroll nav-tabs" role="tablist" id="nav-tab" style="display:none">
                </div> -->
                <div class="col h-100 p-0">
                    <div class="nbar w-100 d-flex align-items-center bg-1">
                        <div class="nbar-l  w-25 h-75 d-flex align-items-center">
                            <i class="fas fa-bars cancle-btn wht  text-center align-content-center"></i>
                            <input type="hidden" class="form-control bg-transparent text-light h-100 border-0" placeholder="Search here something..." value="search here..">
                        </div>
                        <div class="nbar-r h-75 w-75 wht d-flex justify-content-end align-items-center gap-3 ">
                            <div class="profile mw-25 h-75 d-flex align-items-center">
                                <div class="profile-namer d-none d-md-flex gap-2 me-2">
                                    <span class="d-block fs-6">{{ Auth::guard('user')->user()->email }} <br> </span>
                                    <!-- <span class="text-secondary">WEB <br> DEVELOPER</span> -->
                                </div>
                                <div class="profile-pic rounded-circle p-0 me-2">
                                    <img src="https://i.pinimg.com/736x/5c/91/7a/5c917a46cdce453fe8c3d11b0d5618db.jpg" alt="" class="h-100 w-100 img-fluid rounded-circle m-0 p-0">
                                </div>
                                <div class="dropdown">
                                    <button class="btn text-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: transparent; border: none; box-shadow: none; width: 10%; height:10%">
                                    </button>
                                    <ul class="dropdown-menu bg-dark text-primary">
                                        <li><a class="dropdown-item" href="#">Profile</a></li>
                                        <li><a class="dropdown-item" href="{{ route('community') }}">Community</a></li>
                                        <!-- <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exampleModal">Log-Out</a></li> -->
                                        <li><a href="{{ route('user-logout') }}" class="dropdown-item">Log-Out</a></li>
                                        <li><a href="{{ route('delete-account') }}" class="dropdown-item">Delete Account</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-dark">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Log Out</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body bg-dark d-flex flex-column gap-3">
                                            <input type="text" class="form-control bg-dark border text-light" value="nayan@gmail.com">
                                            <input type="password" class="form-control bg-dark text-light" value="123456543">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                                <label class="form-check-label" for="flexCheckDefault">
                                                    Remember Login Info
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-dark">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancle</button>
                                            <button type="button" class="btn btn-primary">Log-Out</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pages tab-content p-3" id="nav-tabContent" style="background-color: rgb(11, 35, 53);">
                        <div class="row w-80 p-3">
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">User Profile</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mt-2">
                                <div class="card card-primary card-outline">
                                    <div class="card-body box-profile">
                                        <div class="text-center">
                                            @if(Auth::guard('user')->user()->avatar)
                                            <img class="profile-user-img img-fluid img-circle" src="{{ asset('/storage/'.Auth::guard('user')->user()->avatar) }}" id="user_img" alt="User profile picture">
                                            @else
                                            <img class="profile-user-img img-fluid img-circle" src="{{ asset('adminAssets/dist/img/user2-160x160.jpg') }}" id="user_img" alt="User profile picture">
                                            @endif
                                        </div>

                                        <h3 class="profile-username text-center">{{ Auth::guard('user')->user()->name }}</h3>

                                        <ul class="list-group list-group-unbordered mb-3">
                                            <li class="list-group-item mt-2">
                                                <b>Name</b> <a class="float-right">{{ Auth::guard('user')->user()->name }}</a>
                                            </li>
                                            <li class="list-group-item mt-2">
                                                <b>Email</b> <a class="float-right">{{ Auth::guard('user')->user()->email }}</a>
                                            </li>
                                            <li class="list-group-item mt-2">
                                                <b>Mobile</b> <a class="float-right">{{ Auth::guard('user')->user()->mobile }}</a>
                                            </li>
                                        </ul>

                                        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-block"><b>Dashboard</b></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9 mt-2">
                                <div class="card card-primary card-outline">
                                    <div class="card-body ">
                                        <div class="tab-content">
                                            <div class="active tab-pane" id="settings">
                                                <form id="user-profile" class="form-horizontal" action="{{ route('user-profile') }}" method="post" enctype="multipart/form-data">
                                                    {{ csrf_field() }}
                                                    <div class="form-group row mb-3">
                                                        <label for="inputName" class="col-sm-2 col-form-label">Name <span style="color: #ff5252;">*</span></label>
                                                        <div class="col-sm-10">
                                                            <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="{{ Auth::guard('user')->user()->name }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                                        <div class="col-sm-10">
                                                            <input type="email" class="form-control" name="email" id="email" value="{{ Auth::guard('user')->user()->email }}" placeholder="Email" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                        <label for="inputName2" class="col-sm-2 col-form-label">Mobile <span style="color: #ff5252;">*</span></label>
                                                        <div class="col-sm-10">
                                                            <input type="tel" class="form-control" name="mobile" id="mobile" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" placeholder="xxx-xxx-xxxx" value="{{ Auth::guard('user')->user()->mobile }}">
                                                        </div>
                                                    </div>
                                                    <!-- <div class="form-group row mb-3">
                                                        <label for="inputSkills" class="col-sm-2 col-form-label">Profile Picture</label>
                                                        <div class="col-sm-10">
                                                            <input type="file" class="form-control" name="avatar" id="avatar" onchange="show(this)">
                                                        </div>
                                                    </div> -->
                                                    <div class="form-group row">
                                                        <div class="offset-sm-2 col-sm-10">
                                                            <button type="submit" class="btn btn-danger" style="margin-left: 70%; width:20%;">Submit</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row w-80 p-3">
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Subscription Details</h3>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $subscriptionDetails = UsersSubscriptions::where(['user_id' => Auth::guard('user')->id()])->first();
                            // dd($subscriptionDetails['status'])
                            ?>
                            <div class="col-md-12 mt-2">
                                <div class="card card-primary card-outline">
                                    <div class="card-body">
                                        <div class="form-group row mb-3">
                                            <div class="col-sm-10">
                                                <label for="inputName" class="col-sm-2 col-form-label">Subscription Status <span style="color: #ff5252;">*</span></label>
                                                @if ($subscriptionDetails == null || $subscriptionDetails['status'] == 'canceled')
                                                    <span class="badge text-bg-danger" status="Inactive">Not Subscribed</span>
                                                    <div class="form-group">
                                                        <div class="col-sm-10">
                                                            <a href="{{ route('user-subscription-package') }}" class="btn btn-success">Activate New Subscription</a>
                                                        </div>
                                                    </div>
                                                @else
                                                    @if($subscriptionDetails['status'] == 'trialing')
                                                        <span class="badge text-bg-primary" status="Active">Trialing</span>
                                                        <br>
                                                        <label class="col-sm-2 col-form-label">Trial Ending on: </label>
                                                    @elseif($subscriptionDetails['status'] == 'active')
                                                        <span class="badge text-bg-success" status="Active">Active</span>
                                                        <br>
                                                        <label for="inputEmail" class="col-sm-2 col-form-label">Subscription End Date :</label>
                                                    @endif
                                                        <span class="">{{ Carbon\Carbon::parse($subscriptionDetails['end_date'])->format('d-M-Y') }}</span>
                                                        
                                                    <div class="form-group">
                                                        <div class="col-sm-10">
                                                            <a href="{{ url('feedback/1') }}" class="btn btn-danger">Cancel Subscription</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- <div class="form-group">
                                            <div class="col-sm-10">
                                                <a href="{{ url('get-questions') }}" class="btn btn-warning">Attempt Questions</a>
                                            </div>
                                        </div> -->
                                    </div>
                                </div>
                                <div class="card card-primary card-outline">
                                    <?php $subscriptionHistories = SubscriptionHistories::where(['user_id' => Auth::guard('user')->id()])->get(); ?>
                                    <div class="subscription-history">
                                        <h3>Subscription History</h3>
                                        <table class="subscription-table">
                                            <thead>
                                                <tr>
                                                    <th>Plan Amount</th>
                                                    <th>Status</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Taken Extra Trial</th>
                                                    <th>Taken Extra Discount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($subscriptionHistories as $subscription)
                                                <tr>
                                                    <td>@if($subscription['status'] == 'trialing') 00 @else {{ $subscription['amount'] }} @endif</td>
                                                    <td>
                                                        <span class="badge @if($subscription['status'] == 'active')badge-active @elseif($subscription['status'] == 'trialing')badge-trialing @elseif($subscription['status'] == 'cancelled')badge-cancelled @else badge-inactive @endif">
                                                            <?php echo htmlspecialchars(ucfirst($subscription['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>{{ htmlspecialchars(date('Y-m-d', strtotime($subscription['start_date']))) }}</td>
                                                    <td>{{ htmlspecialchars(date('Y-m-d', strtotime($subscription['end_date']))) }}</td>
                                                    <td>
                                                        <span class="badge {{ $subscription['taken_trial'] ? 'badge-trial' : 'badge-no-trial' }}">
                                                            {{ $subscription['taken_trial'] ? 'Yes' : 'No' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $subscription['taken_discount'] ? 'badge-discount' : 'badge-no-discount'; ?>">
                                                            <?php echo $subscription['taken_discount'] ? 'Yes' : 'No'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                    <tr> <td colspan="6"> No Record Found </td> </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>                                  
                                </div>
                            </div>
                        </div>

                        <div class="row w-80 p-3">
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Change Password</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mt-2">
                                <div class="card card-primary card-outline">
                                    <div class="card-body">
                                        <form id="change-password" action="{{ route('user-change-password') }}" method="post">
                                            {{ csrf_field() }}
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="old_password">Old Password <span style="color: #ff5252;">*</span></label>
                                                        <input type="text" id="old_password" name="old_password" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="new_password">New Password <span style="color: #ff5252;">*</span></label>
                                                        <input type="password" id="new_password" name="new_password" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="confirm_password">Confirm Password <span style="color: #ff5252;">*</span></label>
                                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="password_rules">
                                                <h4>Passwords must meet these requirements:</h4>
                                                <ul>
                                                    <li class="password_length">At least 8 characters</li>
                                                    <li class="password_uppercase">At least 1 uppercase letter</li>
                                                    <li class="password_number">At least one number</li>
                                                    <li class="password_special_character">At least one special character</li>
                                                    <li class="password_match">Password and confirmation must match</li>
                                                </ul>
                                            </div>
                                            <button type="submit" class="btn btn-info float-right">Submit</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('scripts')
    <SCRIPT type="text/javascript">
        function show(input) {
            debugger;
            var validExtensions = ['jpg', 'png', 'jpeg']; //array of valid extensions
            var fileName = input.files[0].name;
            var fileNameExt = fileName.substr(fileName.lastIndexOf('.') + 1);
            if ($.inArray(fileNameExt, validExtensions) == -1) {
                input.type = ''
                input.type = 'file'
                $('#user_img').attr('src', "");

                Swal.fire({
                    title: "Invalid Extension!",
                    text: "Only these file types are accepted : " + validExtensions.join(', '),
                    icon: "error"
                });

                alert("Only these file types are accepted : " + validExtensions.join(', '));
            } else {
                if (input.files && input.files[0]) {
                    var filerdr = new FileReader();
                    filerdr.onload = function(e) {
                        $('#user_img').attr('src', e.target.result);
                    }
                    filerdr.readAsDataURL(input.files[0]);
                }
            }
        }
    </SCRIPT>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif

        @if(Session::has('error'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.error("{{ session('error') }}");
        @endif
    </script>

    <script>
        var phone_input = document.getElementById("mobile");

        phone_input.addEventListener('input', () => {
            phone_input.setCustomValidity('');
            phone_input.checkValidity();
        });

        phone_input.addEventListener('invalid', () => {
            if (phone_input.value === '') {
                phone_input.setCustomValidity('Enter phone number!');
            } else {
                phone_input.setCustomValidity('Enter phone number in this format: 123-456-7890');
            }
        });
    </script>

    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <script>
        $(function() {
            $('#change-password').validate({
                rules: {
                    old_password: {
                        required: true,
                    },
                    new_password: {
                        minlength: 8,
                        maxlength: 30,
                        required: true,
                    },
                    confirm_password: {
                        equalTo: '#new_password',
                    },
                },
                messages: {
                    old_password: {
                        required: "Please enter your current password",
                    },
                    new_password: {
                        required: "Please enter new password",
                        pwcheck: "Password is not strong enough",

                    },
                    confirm_password: {
                        equalsTo: "New password and confirm password should be same"
                    }
                },
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                }
            });
        });
    </script>

    <script>
        $(function() {
            $('#user-profile').validate({
                rules: {
                    name: {
                        required: true,
                    },
                    mobile: {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: "Please enter your name",
                    },
                    mobile: {
                        required: "Please enter your mobile number",

                    }
                },
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    @endsection
</x-home-layout>
