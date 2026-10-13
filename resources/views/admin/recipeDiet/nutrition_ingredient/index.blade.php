<x-admin-layout title="Nutrition-Ingredients">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        /* table.table-bordered.dataTable tbody th, table.table-bordered.dataTable tbody td {
            border-bottom-width: 0;
            background-color: #343a40;
        } */

       #nutritionTable_wrapper table.dataTable tbody tr{
            background-color: transparent
        }
        #nutritionTable_wrapper table.dataTable>thead .sorting:after,
        #nutritionTable_wrapper table.dataTable>thead .sorting:before,
        #nutritionTable_wrapper table.dataTable>thead .sorting_asc:after,
        #nutritionTable_wrapper table.dataTable>thead .sorting_asc:before{
           display: none;
        }
        #nutritionTable_wrapper.dataTables_wrapper .dataTables_paginate .paginate_button{
          padding: 0px
        }
        #nutritionTable_wrapper.dataTables_wrapper .dataTables_info,
        #nutritionTable_wrapper.dataTables_wrapper div.dataTables_length label,
        #nutritionTable_wrapper.dataTables_wrapper div.dataTables_filter label{
            color: #dee2e6;
        }
        #nutritionTable_wrapper.dataTables_wrapper div.dataTables_length select{
            width: 50px;
        }
        .dark-mode .card .card {
            background: none;
            padding: 15px;
            margin-top: 0;
            height: auto;
            background-color: white;
            color: #343a40;
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Nutritions</li>
                            <li class="breadcrumb-item active">Nutrition Ingredient</li>
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
                            <h3 class="card-title">Nutrition Ingredient</h3>
                            <div class="card-tools">
                                <a type="button" href="{{ url('admin/nutrition-ingredient-create') }}" class="btn btn-secondary">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Nutrition Ingredient
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="nutritionTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Name</th>
                                        <th>Kcal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#nutritionTable').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                ajax: "/admin/nutrition-ingredient-index",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }, // Index column
                    { data: 'name', name: 'name' },
                    { data: 'kcal', name: 'kcal' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
            });
        });
    </script>

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
