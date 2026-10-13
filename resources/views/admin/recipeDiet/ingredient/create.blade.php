<x-admin-layout title="Create Ingredient">
    @section('styles')
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        .select2-container--default .select2-selection--single {
            background-color: #343a40;
            border: 1px solid #6c757d;
            border-radius: 4px;
            height: 37px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff;
            line-height: 28px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #888 transparent transparent transparent;
            border-style: solid;
            border-width: 6px 4px 0px 5px;
            height: 0px;
            left: 50%;
            margin-left: -8px;
            margin-top: 4px;
            position: absolute;
            top: 50%;
            width: 10px;
        }
    </style>
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
                            <li class="breadcrumb-item active">Ingredients</li>
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
            <form id="ingredient-form" action="{{ url('admin/ingredient-create') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add Ingredient</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/ingredient-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Name <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" pattern="[A-Za-z_ ]{1,50}" title="Please enter only alphabets.">
                                        </div>
                                    </div>

                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Quantity <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="quantity" name="quantity" class="form-control" value="{{ old('quantity') }}" min="0" title="Please enter only numeric value." placeholder="Quantity">
                                        </div>
                                    </div>

                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Kcal <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="kcal" name="kcal" class="form-control" value="{{ old('kcal') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">Image <span style="color: #ff5252;">*</span></label>
                                    <input title="Please select image" type="file" id="image" name="image" accept="image/*" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="inputDescription">Description <span style="color: #ff5252;">*</span></label>
                                    <textarea id="description" name="description" class="form-control" rows="8">{{ old('description') }}</textarea>
                                </div>

                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status">
                                    <label for="status">Enable</label>
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
            $('#ingredient-form').validate({
                rules: {
                    name: {
                        required: true,
                    },

                    quantity: {
                        required: true,
                    },

                    unit_id: {
                        required: true,
                    },

                    kcal: {
                        required: true,
                    },
                    description: {
                        required: true
                    },
                    image: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter Ingredient Name",
                    },

                    quantity: {
                        required: "Please enter Ingredient Quantity",
                    },

                    unit_id: {
                        required: "Please select unit for ingredient",
                    },

                    kcal: {
                        required: "Please enter Ingredient Calorie",
                    },
                    description: {
                        required: "Please write in some words about this ingredient"
                    },
                    image: {
                        required: "Please select an image."
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
        $('.select2').select2();

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        })
    </script>
    @endsection
</x-admin-layout>
