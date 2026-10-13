<?php

use App\Models\MealType;
?>
<x-admin-layout title="Recipes">
    @section('styles')
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
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
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Recipe List</h3>
                            <div class="card-tools">
                                <a type="button" href="{{ route('admin.recipe-create') }}" class="btn btn-secondary">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Recipe
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="customTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Title</th>
                                        <th>Recipe Image</th>
                                        <th>Meal Type</th>
                                        <th>Cooking Time</th>
                                        <th>Overall time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recipesData as $key => $recipe)
                                    <?php
                                    // Pre-fetch meal types in one query for better performance
                                    $mealTypes = MealType::whereIn('id', array_unique(explode("|", $recipe['meal_type_id'])))->pluck('meal_type', 'id')->toArray();
                                    ?>
                                    <?php
                                    // Extract and prepare meal type titles for this recipe
                                    $mealTypeIds = explode("|", $recipe['meal_type_id']);
                                    $mealTitle = array_map(fn($id) => $mealTypes[$id] ?? 'Unknown', $mealTypeIds);
                                    $mealType = implode("|", $mealTitle);
                                    ?>
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $recipe['title'] }}</td>
                                        <td><img src="{{ asset('storage/'. $recipe['picture']) }}" style="width: 150px; height:100px;"></td>
                                        <td>{{ $mealType }}</td>
                                        <td>{{ $recipe['cooking_time'] }}</td>
                                        <td>{{ $recipe['overall_time'] }}</td>
                                        <td>
                                            @if ($recipe['status'] == 1)

                                            <a class="updateRecipeStatus" id="recipe-{{ $recipe['id'] }}" recipe_id="{{ $recipe['id'] }}" href="javascript:void(0)"><span class="badge badge-success" status="Active">Active</span>
                                                @else
                                                <a class="updateRecipeStatus" id="recipe-{{ $recipe['id'] }}" recipe_id="{{ $recipe['id'] }}" href="javascript:void(0)"><span class="badge badge-warning" status="Inactive">Inactive</span>
                                                    @endif
                                        </td>
                                        <td>
                                            <!-- <a href="" class="btn btn-success"><i class="fas fa-eye"></i></a> -->
                                            <a href="{{ url('admin/recipe-update/'.$recipe['slug'] .'/'. $recipe['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                            <!-- <a href="{{ url('admin/delete/'.$recipe['slug'] .'/'. $recipe['id']) }}" class="btn btn-danger"><i class="fas fa-trash"></i></a> -->
                                            <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="recipe" slug="{{ $recipe['slug'] }}" moduleId="{{ $recipe['id'] }}" message="Recipe Deleted Successfully"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Title</th>
                                        <th>Recipe Image</th>
                                        <th>Meal Type</th>
                                        <th>Cooking Time</th>
                                        <th>Overall time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endsection
</x-admin-layout>
