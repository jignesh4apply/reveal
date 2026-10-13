<x-admin-layout title="Update Nutrition Ingredient">
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
                        <h1>Nutritions</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Nutritions</li>
                            <li class="breadcrumb-item active">Nutrition Ingredients</li>
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
            <form id="ingredient-form" acion="{{ url('admin/nutrition-ingredient-update/'.$ingredient['slug']) }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Update Nutrition Ingredient</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/nutrition-ingredient-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label for="inputName">Name <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="name" name="name" class="form-control" value="{{ $ingredient->name }}" title="Please enter only alphabets.">
                                            {{-- <input type="text" id="name" name="name" class="form-control" value="{{ $ingredient->name }}" pattern="[A-Za-z_ ]{1,50}" title="Please enter only alphabets."> --}}
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="kcal">Kcal <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="kcal" name="kcal" class="form-control" value="{{ $ingredient->kcal }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="protein">Protein (in gm) <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="protein" name="protein" class="form-control" value="{{ $ingredient->protein }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="fats">Fats (in gm) <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="fats" name="fats" class="form-control" value="{{ $ingredient->fats }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="carbs">Carbs (in gm) <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="carbs" name="carbs" class="form-control" value="{{ $ingredient->carbs }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="sugar">Sugar (in gm) <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="sugar" name="sugar" class="form-control" value="{{ $ingredient->sugar }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label for="salt">Salt (in gm) <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="salt" name="salt" class="form-control" value="{{ $ingredient->salt }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label for="small_image_url">Small Image URL</label>
                                            <input type="text" id="small_image_url" name="small_image_url" class="form-control" value="{{ $ingredient->small_image_url }}">
                                        </div>
                                        <div class="form-group">
                                            <label for="image_url">Image URL</label>
                                            <input type="text" id="image_url" name="image_url" class="form-control" value="{{ $ingredient->image_url }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="small_image_url">Small Image preview</label> </br>
                                            <image src="{{ $ingredient->small_image_url }}" alt="not found" style="MAX-HEIGHT: 300PX; MAX-WIDTH: 300PX;">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Barcodes Section -->
                                    <div class="card card-primary col-lg-4">
                                        <div class="card-header">
                                            <h3 class="card-title">Barcodes </h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="repeater">
                                                <div data-repeater-list="barcodes_list">
                                                    @forelse ($barcodes as $index => $barcode)
                                                    <div data-repeater-item>
                                                        <div class="row">
                                                            <div class="col-sm-10">
                                                                <div class="form-group">
                                                                    <!-- Use old() to retain values if form is submitted, else use the existing value -->
                                                                    <input type="number" name="barcodes_list[{{ $index }}][barcode]" class="form-control" value="{{ old('barcodes_list.' . $index . '.barcode', $barcode->barcode ?? '') }}" title="Please enter Barcode">
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2" style="margin-top: 3px;">
                                                                <div class="form-group">
                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @empty
                                                    <div data-repeater-item>
                                                        <div class="row">
                                                            <div class="col-sm-10">
                                                                <div class="form-group">
                                                                    <input type="number" id="barcode" name="barcode" class="form-control" value="{{ old('barcode') }}" title="Please enter Barcode" >
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2" style="margin-top: 3px;">
                                                                <div class="form-group">
                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforelse
                                                </div>
                                                <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More Barcodes</button>
                                            </div>
                                        </div>
                                    </div>
                                
                                    <!-- Serving Units Section -->
                                    <div class="card card-primary col-lg-8">
                                        <div class="card-header">
                                            <h3 class="card-title">Serving Units <span style="color: #ff5252;">*</span></h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="repeater">
                                                <div data-repeater-list="serving_units">
                                                    @forelse ($servingUnits as $index => $unit)
                                                    <div data-repeater-item>
                                                        <div class="row">
                                                            <div class="col-lg-4">
                                                                <div class="form-group">
                                                                    <label for="size_key">Size Name</label>
                                                                    <!-- Pre-populate size_key -->
                                                                    <input type="text" name="serving_units[{{ $index }}][size_key]" class="form-control" value="{{ old('serving_units.' . $index . '.size_key', $unit->size_key ?? '') }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="value">Size (value)</label>
                                                                    <!-- Pre-populate value -->
                                                                    <input type="number" name="serving_units[{{ $index }}][value]" class="form-control" value="{{ old('serving_units.' . $index . '.value', $unit->value ?? '') }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="units">Measurement Units</label>
                                                                    <!-- Pre-populate value -->
                                                                    <input type="text" name="serving_units[{{ $index }}][units]" class="form-control" value="{{ old('serving_units.' . $index . '.units', $unit->units ?? '') }}" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2" style="margin-top: 35px;">
                                                                <div class="form-group">
                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @empty
                                                    <div data-repeater-item>
                                                        <div class="row">
                                                            <div class="col-lg-4">
                                                                <div class="form-group">
                                                                    <label for="size_key">Size Name</label>
                                                                    <input type="text" title="Please enter Name for this size." id="size_key" name="size_key" class="form-control" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="value">Size (value)</label>
                                                                    <input type="number" title="Please enter quantity of serving key." id="value" name="value" class="form-control" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-3">
                                                                <div class="form-group">
                                                                    <label for="units">Measurement Units</label>
                                                                    <input type="text" title="Please enter measurement unit for serving key." id="units" name="units" class="form-control" placeholder="grams" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2" style="margin-top: 35px;">
                                                                <div class="form-group">
                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforelse
                                                </div>                                  
                                                <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More Serving Units</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="mark_as_reviewed" name="mark_as_reviewed" @if($ingredient['mark_as_reviewed']==1) checked @endif>
                                    <label for="mark_as_reviewed">Mark as Reviewed</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" @if($ingredient['status']==1) checked @endif>
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
                        <input type="submit" value="Update" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

    <script>
        $(function() {
            $('#ingredient-form').validate({
                rules: {
                    name: {
                        required: true,
                    },
                    kcal: {
                        required: true,
                    },
                    protein: {
                        required: true,
                    },
                    fats: {
                        required: true,
                    },
                    carbs: {
                        required: true,
                    },
                    sugar: {
                        required: true,
                    },
                    salt: {
                        required: true,
                    },
                },
                messages: {
                    name: {
                        required: "Please enter Ingredient Name",
                    },
                    kcal: {
                        required: "Please enter Ingredient Calorie",
                    },
                    protein: {
                        required: "Please enter Nutrition Ingredient Protein",
                    },
                    fats: {
                        required: "Please enter Nutrition Ingredient Fats",
                    },
                    carbs: {
                        required: "Please enter Nutrition Ingredient Carbohydrates",
                    },
                    sugar: {
                        required: "Please enter Nutrition Ingredient sugar",
                    },
                    salt: {
                        required: "Please enter Nutrition Ingredient salt",
                    },
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
            // Initialize repeater for barcodes and serving units
            $('.repeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: false,  // Allow deletion of first item as well
                show: function() { $(this).slideDown(); },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

        });
    </script>
    @endsection
</x-admin-layout>
