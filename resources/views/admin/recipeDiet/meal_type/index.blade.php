<x-admin-layout title="Ingredients">
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
                            <li class="breadcrumb-item active">Recipes</li>
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
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Meal Type</h3>
                            <div class="card-tools">
                                <a type="button" href="{{ url('admin/meal-type-create') }}" class="btn btn-secondary">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Meal Type
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
                                        <th>Meal Type</th>
                                        <th>Slug</th>
                                        <th>Show in Tracker</th> 
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mealTypetData as $key => $mealType)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $mealType['title'] }}</td>
                                        <td>{{ $mealType['meal_type'] }}</td>
                                        <td>{{ $mealType['slug'] }}</td>
                                        <td>
                                            @if ($mealType['visible_in_tracker'] == 1)
                                                <div class="updateMealTypeStatus" id="meal_type-{{ $mealType['id'] }}" meal_type_id="{{ $mealType['id'] }}" ><span class="badge badge-success" status="Active">Yes</span></div>
                                            @else
                                                <div class="updateMealTypeStatus" id="meal_type-{{ $mealType['id'] }}" meal_type_id="{{ $mealType['id'] }}" ><span class="badge badge-warning" status="Inactive">No</span></div>
                                            @endif
                                        </td> 
                                        <td>
                                            <a href="{{ url('admin/meal-type-update/'.$mealType['slug'] .'/'. $mealType['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="meal-type" slug="{{ $mealType['slug'] }}" moduleId="{{ $mealType['id'] }}" message="Meal Type Deleted Successfully"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Title</th>
                                        <th>Meal Type</th>
                                        <th>Slug</th>
                                        <th>Show in Tracker</th>
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
