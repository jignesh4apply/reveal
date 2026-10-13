<x-home-layout title="Dashboard">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        /* theme-classes */

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

        .form-group button {
            font-weight: 600;
        font-size: 14px;
        line-height: 20px;
        background-color: #7662F1;
        padding: 14px;
        width: 100%;
        border-radius: 50px;
        border: none;
        outline: none;
        box-shadow: none;
        cursor: pointer;
        color: #ffffff;
        }

        .form-group button:focus {
        border: none;
        outline: none;
        box-shadow: none;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    @endsection

    <body class="back-black">
        <div class="flx-box container">
            <div class="row h-100 position-relative">
                <div class="col  h-100 p-0">
                    

                    <section class="personalized-program space-tb">
                        <div class="container">
                            <div class="section-title-2">
                                <h2 class="main-title-2 text-align-center"> Delete Account</h2>
                            </div>
                            <div class="app-download-block">
                                <form id="delete-account-form" action="{{ route('delete-user') }}" method="post">
                                    {{ csrf_field() }}
                                    <div class="form-group">
                                        <label for="email" style="color:#ffffff"> <strong>Email</strong></label>
                                        <input type="email" id="email" name="email" class="form-control" placeholder="Please Enter Your Email" >
                                    </div>
                                    <div class="form-group">
                                        <label for="password" style="color:#ffffff"> <strong>Password</strong></label>
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Please Enter Your Password">
                                    </div>

                                    <div class="form-group mt-4" style="display: flex !important; align-items:center;gap:10px;">
                                        <button type="submit" class="btn-lg btn-danger">Submit</button>
                                        <button type="reset" class="btn-lg btn-primary">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </body>
    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showMethod: 'slideDown',
            timeOut: 40000
        };

        @if(Session::has('success'))
        toastr.success("{{ session('success') }}", 'Success', {
            "background-color": "#4CAF50"
        });
        @endif

        @if(Session::has('error'))
        toastr.error("{{ session('error') }}", 'Error', {
            "background-color": "#f44336"
        });
        @endif
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#delete-account-form').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: "/delete-user",
                method: 'POST',
                data: $(this).serialize(),
                success: function (response) {                    
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You will not be able to recover this account once it's deleted!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Delete',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "/delete-user-account",
                                    method: 'POST',
                                    data: {
                                        userId: response.userId,
                                    },
                                    success: function () {
                                        Swal.fire(
                                            'Deleted!',
                                            'Your account has been deleted.',
                                            'success'
                                        ).then(() => {
                                            window.location.href = '/delete-user';
                                        });
                                    },
                                    error: function(xhr) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops...',
                                            text: xhr.responseJSON.message || 'Something went wrong.',
                                        });
                                    }
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: xhr.responseJSON.message || 'Something went wrong.',
                    });
                }
            });
        });
    });
</script>
    @endsection
</x-home-layout>