<x-admin-layout title="Create Recipe">
    @section('styles')
    <style>
        .ck.ck-editor__editable_inline {
            color: #000;
            min-height: 200px;
        }

        #add_prep_field {
            display: inline-block;
            width: 95%;
            height: calc(2.25rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-shadow: inset 0 0 0 transparent;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            background-color: #343a40;
            border-color: #6c757d;

            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        .remove_ingredient {
            margin-top: 3rem !important;
            align-content: center;
        }

        .delete_ingredient {
            margin-top: 2.2rem !important;
            align-content: center;
            height: 30px;
        }

        .select2-container--default .select2-selection--multiple {
            background-color: transparent;
            border: 1px solid #6c757d;
        }

        .dark-mode .select2-purple .select2-container--default .select2-search--inline .select2-search__field:focus {
            border: none;
        }

        .select2 {
            background-color: transparent;
        }

        .switch-field {
            display: flex;
            margin-bottom: 8px;
            border-color: #0d6efd;
            overflow: hidden;
        }

        .switch-field input {
            position: absolute !important;
            clip: rect(0, 0, 0, 0);
            height: 1px;
            width: 1px;
            border: 0;
            overflow: hidden;
        }

        .switch-field label {
            background-color: #e4e4e4;
            color: #0d6efd;
            /* color: rgba(0, 0, 0, 0.6); */
            font-size: 14px;
            line-height: 1;
            text-align: center;
            padding: 8px 16px;
            margin-right: -1px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            /* box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px rgba(255, 255, 255, 0.1); */
            transition: all 0.1s ease-in-out;
        }

        .switch-field label:hover {
            cursor: pointer;
        }

        .switch-field input:checked+label {
            background-color: #0d6efd;
            color: whitesmoke;
            box-shadow: none;
        }

        .switch-field label:first-of-type {
            border-radius: 4px 0 0 4px;
        }

        .switch-field label:last-of-type {
            border-radius: 0 4px 4px 0;
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

        .bootstrap-tagsinput input:focus{
            color: #fff;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap4-tagsinput@4.1.3/tagsinput.min.css">
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">

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
                            <li class="breadcrumb-item active">Recipe</li>
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
            <form id="quickForm" @if(!isset($recipe) || empty($recipe)) action="{{ url('admin/recipe-create') }}" @else acion="{{ url('admin/recipe-update/'.$recipe['slug']) }}" @endif method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">@if(!isset($recipe))Add @else Update @endif Recipe</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/recipe-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" @if(isset($recipe)) value="{{ $recipe['title'] }}" @else value="{{ old('title') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="tags">Tag for preferences <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="tags[]" name="tags[]" @if(isset($recipe)) value="{{ $recipe['tags'] }}" @endif data-role="tagsinput" class="form-control" />
                                </div>

                                <div class="form-group">
                                    <label for="meal_type">Meal Type <span style="color: #ff5252;">*</span></label>
                                    <div class="select2-purple">
                                        <select name="meal_type[]" id="meal_type" class="form-control select2" data-dropdown-css-class="select2-purple" multiple>
                                            <option value="" disabled>Select Meal Type</option>
                                            @foreach ($mealTypeData as $mealType)
                                            <option value="{{ $mealType['id'] }}" @if(isset($recipe)) {{in_array($mealType['id'], explode("|", $recipe['meal_type'])) ? 'selected' : ''}} @endif>{{ $mealType['meal_type'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">Picture <span style="color: #ff5252;">*</span></label>
                                    <input title="Please select a image" type="file" id="picture" name="picture" accept="image/*" class="form-control">
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group row">
                                            <label for="inputNumber" class="col-sm-3 col-form-label">Add Person <span style="color: #ff5252;">*</span></label>
                                            <div class="col-sm-9 mt-2">
                                                <div class="switch-field">
                                                    <input type="radio" name="is_person" id="is_person_yes" value="1" />
                                                    <label for="is_person_yes">Yes</label>
                                                    <input type="radio" name="is_person" id="is_person_no" value="0" checked />
                                                    <label for="is_person_no">No</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 person">
                                        <div class="form-group">
                                            <label for="no.of.persons">No. of Persons</label>
                                            <input type="number" class="form-control" id="no_of_persons" name="no_of_persons" min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">Cooking Time <span style="color: #ff5252;">*</span></label>
                                    <input type=time step=1 id="cooking_time" name="cooking_time" class="form-control" @if(isset($recipe)) value="{{ $recipe['cooking_time'] }}" @else value="{{ old('cooking_time') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">Preparation Time <span style="color: #ff5252;">*</span></label>
                                    <input type=time step=1 id="overall_time" name="overall_time" class="form-control" @if(isset($recipe)) value="{{ $recipe['overall_time'] }}" @else value="{{ old('overall_time') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">About <span style="color: #ff5252;">*</span></label>
                                    <textarea id="about" name="about" class="form-control">{{ old('about') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="prep_video">Preparation Video Link</label>
                                    <input type="text" id="prep_video" name="prep_video" class="form-control">
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Preparation Steps <span style="color: #ff5252;">*</span></h3>
                                        <!-- <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div> -->
                                    </div>
                                    <div class="card-body">
                                        <div class="repeater">
                                            <div data-repeater-list="preparation">
                                                <div data-repeater-item>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <label for="stepName">Step Title</label>
                                                                <input type="text" title="Please enter steps to prepare this recipe." id="stepTitle" name="stepTitle" class="form-control" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label for="stepName">Step Description</label>
                                                                <textarea title="Please write short description about this step." id="stepDescription" name="stepDescription" class="form-control" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-2" style="margin-top: 50px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More Steps</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Ingredients</h3>
                                        <!-- <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div> -->
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-primary" title="Add new Ingredient" data-toggle="modal" data-target="#addIngredient">
                                                Add New Ingredient
                                            </button>
                                        </div>
                                    </div>
                                    <div class="repeaterNutrition">
                                        <div data-repeater-list="ingredientsWithNutrition">
                                            <div data-repeater-item class="outer">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-lg-11">
                                                            <div class="card card-primary p-2">
                                                                <div class="card-header">
                                                                    <h3 class="card-title">Nutrition</h3>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="row">
                                                                        <div class="col-sm-3">
                                                                            <div class="form-group">
                                                                                <label for="kcal">Kcal <span style="color: #ff5252;">*</span></label>
                                                                                <input type="number" id="kcal" name="kcal" class="form-control" placeholder="Kcal" value="{{ old('kcal') }}" required min="0" title="Please enter only numeric non-negative value." required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-3">
                                                                            <div class="form-group">
                                                                                <label for="proteins">Proteins <span style="color: #ff5252;">*</span></label>
                                                                                <input type="number" id="protien" name="protien" class="form-control" placeholder="Proteins" value="{{ old('protien') }}" required min="0" title="Please enter only numeric non-negative value." required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-3">
                                                                            <div class="form-group">
                                                                                <label for="fats">Fats <span style="color: #ff5252;">*</span></label>
                                                                                <input type="number" id="fat" name="fat" class="form-control" placeholder="Fats" value="{{ old('fat') }}" required min="0" title="Please enter only numeric non-negative value." required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-3">
                                                                            <div class="form-group">
                                                                                <label for="carbs">Carbs <span style="color: #ff5252;">*</span></label>
                                                                                <input type="number" id="carbs" name="carbs" class="form-control" placeholder="Carbs" value="{{ old('carbs') }}" required min="0" title="Please enter only numeric non-negative value." required>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="repeaterIngredient">
                                                                    <div data-repeater-list="ingredients">
                                                                        <div data-repeater-item class="inner">
                                                                            <div class="row">
                                                                                <div class="col-lg-3">
                                                                                    <div class="form-group">
                                                                                        <label>Ingredient</label>
                                                                                        <select title="Please select an ingredient." class="form-control select2" name="ingredient_id" id="ingredient_id" style="width: 100%;" required>
                                                                                            <option value="">Select</option>
                                                                                            @foreach ($ingredientList as $ingredient)
                                                                                            <option value="{{ $ingredient['id'] }}">{{ $ingredient['name'] }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-lg-2">
                                                                                    <div class="form-group">
                                                                                        <label for="quantity">Quantity <span style="color: #ff5252;">*</span></label>
                                                                                        <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Quantity" min="0" title="Please enter only numeric value." required>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-lg-2">
                                                                                    <div class="form-group">
                                                                                        <label for="quantity">Unit <span style="color: #ff5252;">*</span></label>
                                                                                        <select title="Please Select an unit." name="unit" id="unit" class="form-control" required>
                                                                                            <option value="">Select Unit</option>
                                                                                            <option value="g">gram</option>
                                                                                            <option value="ml">ml</option>
                                                                                            <option value="pcs">pcs</option>
                                                                                            <option value="spoon">spoon</option>
                                                                                        </select>
                                                                                    </div>
                                                                                </div>

                                                                                <div class="col-lg-3">
                                                                                    <div class="form-group">
                                                                                        <label for="ingredientCat">Category <span style="color: #ff5252;">*</span></label>
                                                                                        <Select title="Please Select an ingredient category." class="form-control select2" name="category_id" id="category_id" required>
                                                                                            <option value="">Select</option>
                                                                                            @foreach ($ingredientCategory as $category)
                                                                                            <option value="{{ $category['id'] }}">{{ $category['category_name'] }}</option>
                                                                                            @endforeach
                                                                                        </Select>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-lg-1" style="margin-top: 35px;">
                                                                                    <div class="form-group">
                                                                                        <button type="button" class="btn btn-danger btn-sm inner delete-button-confirm" data-repeater-delete>X</button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="btn btn-primary btn-sm mt-2 float-right inner refreshit" data-repeater-create>Add New</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-1" style="margin-top: 35px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm outer delete-button-confirm" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm mt-2 float-right outer" style="margin-bottom: 10px; margin-right:10px;" data-repeater-create>Add Nutrition</button>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">To Accompany <span style="color: #ff5252;">*</span></h3>
                                        <!-- <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div> -->
                                    </div>
                                    <div class="card-body">
                                        <div class="repeater">
                                            <div data-repeater-list="accompany">
                                                <div data-repeater-item>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <label>Ingredient</label>
                                                                <input type="text" title="Please enter ingredient name" class="form-control" id="ingredient_name" name="ingredient_name" value="{{ old('ingredient_name') }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="form-group">
                                                                <label for="quantity">Quantity <span style="color: #ff5252;">*</span></label>
                                                                <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Quantity" min="0" title="Please enter only numeric value." value="{{ old('quantity') }}" required>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-3">
                                                            <div class="form-group">
                                                                <label for="quantity">Unit <span style="color: #ff5252;">*</span></label>
                                                                <select title="Please Select an unit." name="unit" id="unit" class="form-control" required>
                                                                    <option value="">Select Unit</option>
                                                                    <option value="g">gram</option>
                                                                    <option value="ml">ml</option>
                                                                    <option value="pcs">pcs</option>
                                                                    <option value="spoon">spoon</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-2" style="margin-top: 35px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm inner" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add Accompany</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Materials <span style="color: #ff5252;">*</span></h3>
                                        <!-- <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div> -->
                                    </div>
                                    <div class="card-body">
                                        <div class="repeater">
                                            <div data-repeater-list="materials">
                                                <div data-repeater-item>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <label>Material</label>
                                                                <input type="text" title="Please enter material name" class="form-control" id="material_name" name="material_name" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3">
                                                            <div class="form-group">
                                                                <label for="quantity">Quantity <span style="color: #ff5252;">*</span></label>
                                                                <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Quantity" min="0" title="Please enter only numeric value." required>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-3">
                                                            <div class="form-group">
                                                                <label for="quantity">Unit <span style="color: #ff5252;">*</span></label>
                                                                <select title="Please Select an unit." name="unit" id="unit" class="form-control" required>
                                                                    <option value="">Select Unit</option>
                                                                    <option value="g">gram</option>
                                                                    <option value="ml">ml</option>
                                                                    <option value="pcs">pcs</option>
                                                                    <option value="spoon">spoon</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-2" style="margin-top: 35px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm inner" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add Material</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="status" name="status" @if(isset($recipe) && $recipe['status']==1) checked @endif>
                                            <label for="status">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="free_access" name="free_access" @if(isset($recipe) && $recipe['free_access']==1) checked @endif>
                                            <label for="free_access">Free Access</label>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="special_recipe" name="special_recipe" @if(isset($recipe) && $recipe['special_recipe']==1) checked @endif>
                                            <label for="free_access">Special Recipe</label>
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
                        <input type="submit" @if(!isset($recipe)) value="Save" @else value="Update" @endif class="btn btn-success float-right">
                    </div>
                </div>
            </form>
            <div class="modal fade" id="addIngredient" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add New Ingredient</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="addIngredientForm" action="{{ route('admin.add-ingredient-name') }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="ingredientName" class="form-label">Ingredient Name</label>
                                    <input type="text" class="form-control" id="ingredientName" name="ingredientName" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap4-tagsinput@4.1.3/tagsinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

    <script>
        // Handler for form submission

        $(document).ready(function() {

            $(function() {
                $('#quickForm').validate({
                    rules: {
                        title: {
                            required: true,
                        },
                        category_id: {
                            required: true
                        },

                        cooking_time: {
                            required: true
                        },
                        overall_time: {
                            required: true
                        },
                        about: {
                            required: true
                        },
                        'meal_type[]': {
                            required: true
                        },
                        'tags[]': {
                            required: true
                        },
                        'kcal': {
                            required: true
                        },
                        'protien': {
                            required: true
                        },
                        'fat': {
                            required: true
                        },
                        'carbs': {
                            required: true
                        },
                        'preparation[]': {
                            required: true
                        },
                        'name[]': {
                            required: true
                        },
                        'quantity[]': {
                            required: true
                        },
                        'measure[]': {
                            required: true
                        },
                        picture: {
                            required: true
                        }
                    },
                    messages: {
                        title: {
                            required: "Please enter recipe title",
                        },
                        category_id: {
                            required: "Please select recipe category.",
                        },

                        cooking_time: {
                            required: "Please enter recipe cooking time."
                        },
                        overall_time: {
                            required: "Please  enter recipe overall cooking time."
                        },
                        about: {
                            required: "Please write in some words about this recipe."
                        },
                        'meal_type[]': {
                            required: "Please select atleast one meal type."
                        },
                        'tags[]': {
                            required: "Please enter atleast on tag preference,"
                        },
                        'kcal': {
                            required: "Please enter calories contain in this recipe."
                        },
                        'protien': {
                            required: "Please enter proteins contain in this recipe."
                        },
                        'fat': {
                            required: "Please enter fats contain in this recipe."
                        },
                        'carbs': {
                            required: "Please enter carbs contain in this recipe."
                        },
                        'preparation[]': {
                            required: "Please mention preparation steps of this recipe."
                        },
                        'name[]': {
                            required: "Please enter ingredient name."
                        },
                        'quantity[]': {
                            required: "Please enter ingredient quantity."
                        },
                        'measure[]': {
                            required: "Please enter ingredient quantity measures."
                        },
                        picture: {
                            required: "Please select an image of recipe."
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

            $('.repeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: true,
                show: function() {
                    $(this).slideDown();
                    $(this).find('.select2').removeClass('select2-hidden-accessible');
                    $(this).find('.select2-container').remove();
                    $(this).find('.select2').select2();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

            $('.repeaterNutrition').repeater({
                initEmpty: false,
                isFirstItemUndeletable: true,
                repeaters: [{
                    selector: '.repeaterIngredient',
                    initEmpty: false,
                    isFirstItemUndeletable: true,
                    show: function() {
                        $(this).slideDown();
                        $(this).find('.select2').removeClass('select2-hidden-accessible');
                        $(this).find('.select2-container').remove();
                        $(this).find('.select2').select2();
                    },
                    hide: function(deleteElement) {
                        e.stopPropagation();
                        if (confirm('Are you sure you want to delete this element?')) {
                            $(this).slideUp(deleteElement);
                        }
                    }
                }],
                show: function() {
                    $(this).slideDown();
                    $(this).find('.select2').removeClass('select2-hidden-accessible');
                    $(this).find('.select2-container').remove();
                    $(this).find('.select2').select2();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                },
            });
            // Ensure event handlers are only attached once
            // $(document).on('click', '.delete-button-confirm', function(e) {
            //     // e.stopPropagation();
            //     if (confirm('Are you sure you want to delete this element?')) {
            //         $(this).closest('[data-repeater-item]').slideUp(function() {
            //             $(this).remove();
            //         });
            //     }
            // });
        });
    </script>
    <script>
    $(document).ready(function() {
        $('#addIngredientForm').on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    var newIngredient = response.ingredient;
                    var newOption = `<option value="${newIngredient.id}">${newIngredient.name}</option>`;

                    updateAllDropdowns(newOption);

                    $('#addIngredient').modal('hide');

                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true
                    };
                    toastr.success('Ingredient Inserted Successfully!');
                },
                error: function(xhr) {
                    var errorMsg = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }

                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true
                    };
                    toastr.error(errorMsg);
                }
            });
        });

        // Function to update all dropdowns
        function updateAllDropdowns(newOption) {
            $('select[id="ingredient_id"]').each(function() {
                $(this).append(newOption);
            });
        }

        $(document).on('click', '.refreshit', function() {
            var $try = $(this).siblings().find('select[id="ingredient_id"]:last');
            $.ajax({
                url: "{{ route('ingredients.latest') }}",
                method: 'GET',
                success: function(response) {
                    var options = '';
                    $.each(response.ingredients, function(index, ingredient) {
                        options += `<option value="${ingredient.id}">${ingredient.name}</option>`;
                    });

                    $try.html(options);
                },
                error: function(xhr) {
                    console.log('Error fetching ingredients:', xhr);
                }
            });
        });
    });
    </script>
    <script>
        $(".delete_ingredient").click(function() {
            var id = $(this).attr("rel");
            var deletefunction = $(this).attr("rell");
            var message = $(this).attr("message");
            Swal.fire({
                // position: 'top-end',
                title: "Are you sure, you want to delete this record ? ",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener("mouseenter", Swal.stopTimer);
                            toast.addEventListener("mouseleave", Swal.resumeTimer);
                        },
                    });

                    // Toast.fire({
                    //     icon: "success",
                    //     title: message,
                    // });
                    window.location.href = "/admin/" + deletefunction + "/" + id;
                }
            });
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

    <script>
        $('.select2').select2();

        $('.select2bs4').select2({
            theme: 'bootstrap4'
        })
    </script>

    <script>
        $(document).ready(function() {
            $(".person").hide();
            $("input[name$='is_person']").click(function() {
                var test = $(this).val();
                if (test == 1) {
                    $(".person").show();
                } else {
                    $(".person").hide();
                }
            });
        });
    </script>
    @endsection
</x-admin-layout>
