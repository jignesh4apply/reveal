<x-admin-layout title="Create Ingredient">
    @section('styles')
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">

    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Recipes</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Recipes</li>
                            <li class="breadcrumb-item active">Meal Type</li>
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
            <form id="quickForm" @if(!isset($mealType) || empty($mealType)) action="{{ url('admin/meal-type-create') }}" @else acion="{{ url('admin/meal-type-update/'.$mealType['slug']) }}" @endif method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">@if(!isset($mealType))Add @else Update @endif Meal Type</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/meal-type-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" @if(isset($mealType)) value="{{ $mealType->title }}" @else value="{{ old('title') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="meal_type">Meal Type <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="meal_type" name="meal_type" class="form-control" @if(isset($mealType)) value="{{ $mealType->meal_type }}" @else value="{{ old('meal_type') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="image">Image @if(!isset($mealType)) <span style="color: #ff5252;">*</span> @endif</label>
                                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                                </div>
                                <div class="form-check">
                                    <label for=""></label>
                                    <input type="checkbox" class="form-check-input" id="visible_in_tracker" name="visible_in_tracker" @if(isset($mealType) && $mealType['visible_in_tracker']==1) checked @endif>
                                    <label for="visible_in_tracker">Show in Calories Tracker Tab</label>
                                </div> 
                                <div class="form-check">
                                    <label for=""></label>
                                    <input type="checkbox" class="form-check-input" id="is_popular" name="is_popular" @if(isset($mealType) && $mealType['is_popular']==1) checked @endif>
                                    <label for="is_popular">Popular</label>
                                </div> 
                            </div>
                            <!-- /.card-body -->
                            <div class="col-12 mb-2">
                                <input type="submit" @if(!isset($mealType)) value="Save" @else value="Update" @endif class="btn btn-success float-right">
                            </div>
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <div class="row">
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
            $('#quickForm').validate({
                rules: {
                    title: {
                        required: true,
                    },
                    meal_type: {
                        required: true,
                    },
                    // image: {
                    //     required: true,
                    // }
                },
                messages: {
                    title: {
                        required: "Please enter title."
                    },
                    meal_type: {
                        required: "Please enter Meal Type",
                    },
                    // image: {
                    //     required: "Please select an image."
                    // }
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        @if(Session::has('error'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.error("{{ session('error') }}");
        @endif
    </script>
    @endsection
</x-admin-layout>
