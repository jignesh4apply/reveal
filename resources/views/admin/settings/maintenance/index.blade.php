<x-admin-layout title="General Setting">

    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        .invalid-feedback {
            margin-left: 230px;
        }
    </style>
    @endsection

    @section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>General Setting</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item active">Settings</li>
                            <li class="breadcrumb-item active">Maintenance Mode</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <form id="maintenance-setting" action="" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">General Setting</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group row">
                                    <label for="maintenance_mode" class="col-sm-2 col-form-label">Maintenance Mode for Quiz</label>
                                    <div class="col-sm-4" style="margin-top: 10px;">
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="maintenance_mode" value="0">
                                            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" value="1" {{ $settings->getSetting('maintenance_mode') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <label for="maintenance_mode_mobile" class="col-sm-2 col-form-label">Maintenance Mode for Mobile</label>
                                    <div class="col-sm-4" style="margin-top: 10px;">
                                        <div class="custom-control custom-switch">
                                            <input type="hidden" name="maintenance_mode_mobile" value="0">
                                            <input type="checkbox" class="form-check-input" id="maintenance_mode_mobile" name="maintenance_mode_mobile" value="1" {{ $settings->getSetting('maintenance_mode_mobile') ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')

    <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#description'))
            .catch(error => {
                console.error(error);
            });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif
    </script>

    @endsection
</x-admin-layout>