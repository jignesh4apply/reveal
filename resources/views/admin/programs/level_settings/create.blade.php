<x-admin-layout title="Create Level">
    @section('styles')
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Programs</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Programs</li>
                            <li class="breadcrumb-item active">Levels</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <form id="levelForm" action="{{ url('admin/level-create') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Add Levels
                                </h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/level-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="level_title">Level <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="level_title" name="level_title" class="form-control" value="{{ old('level_title') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="level_points">Level Points Range<span style="color: #ff5252;">*</span></label>
                                            <div class="row">
                                                <div class="col-lg-12 mb-2">
                                                    <label for="start_range">Start Range:</label>
                                                    <input type="range" id="start_range" name="start_range" min="0" max="100" step="0.1" value="{{ old('start_range', 0) }}" class="form-control-range" oninput="document.getElementById('start_value').value=this.value">
                                                    <input type="number" id="start_value" name="start_value" min="0" max="100" class="form-control" value="{{ old('start_value', 0) }}" onchange="document.getElementById('start_range').value=this.value">
                                                </div>
                                                <div class="col-lg-12 mb-2">
                                                    <label for="end_range">End Range:</label>
                                                    <input type="range" id="end_range" name="end_range" min="0" max="100" step="0.1" value="{{ old('end_range', 0) }}" class="form-control-range" oninput="document.getElementById('end_value').value=this.value">
                                                    <input type="number" id="end_value" name="end_value" min="0" max="0" class="form-control" value="{{ old('end_value', 0) }}" onchange="document.getElementById('end_range').value=this.value">
                                                </div>
                                            </div>
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
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    <script>
        $(function() {
            $.validator.addMethod('rangeValue', function(value, element, params) {
                let startValue = parseInt($(params.start).val(), 10);
                let endValue = parseInt($(params.end).val(), 10);
                return this.optional(element) || (startValue <= endValue && startValue >= 0 && endValue <= 100);
            }, "End value must be greater than or equal to start value, and within the range 0 to 100.");

            $('#levelForm').validate({
                rules: {
                    level_title: {
                        required: true,
                    },
                    start_range: {
                        required: true,
                        min: 0
                    },
                    end_range: {
                        required: true,
                        max: 100,
                        rangeValue: {
                            start: '#start_range',
                            end: '#end_range'
                        }
                    }
                },
                messages: {
                    level_title: {
                        required: "Please enter Level",
                    },
                    start_range: {
                        required: "Please enter start range",
                        min: "Start value must be at least 0"
                    },
                    end_range: {
                        required: "Please enter end range",
                        max: "End value must not exceed 100",
                        rangeValue: "End value must be greater than or equal to start value and within the range 0 to 100."
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
    @endsection
</x-admin-layout>
