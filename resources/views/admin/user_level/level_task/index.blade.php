<x-admin-layout title="User Level">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>User Level</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">User Level</li>
                            <li class="breadcrumb-item active">Index</li>
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
                            <h3 class="card-title">Level List</h3>
                            <div class="card-tools">
                                <a type="button" href="{{ route('admin.user-level-create') }}" class="btn btn-secondary">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Level
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="customTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Title</th>
                                        <th>Improvement</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id=reorder_position>
                                    @foreach($userLevel as $level)
                                    <tr class='tableRow' data-id="{{ $level['id'] }}">
                                        <td class=level_id>{{ $level['level'] }}</td>
                                        <td>{{ $level['title'] }}</td>
                                        <td>{{ $level['improvement_per'] }} %</td>
                                        <td>
                                            <a href="{{ url('admin/user-level-update/'. $level['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                            <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="userLevel"  moduleId="{{ $level['id'] }}" message="Level Deleted Successfully"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Level</th>
                                        <th>Title</th>
                                        <th>Improvement</th>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif
           $(document).ready(function() {
            $("#reorder_position").sortable({
                items: "tr",
                cursor: 'move',
                opacity: 0.6,
                update: function() {
                    updateQuizGroupPosition();
                }
            });
            function updateQuizGroupPosition() {
                var orderPosition = [];
                var token = $('meta[name="csrf-token"]').attr('content');
                var url = "/admin/set-quiz-position"

                $('tr.tableRow').each(function(index, element) {
                    orderPosition.push({
                        id: $(this).attr('data-id'),
                        position: index + 1
                    });
                });

                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: "/admin/set-level-position",
                    data: {
                        order: orderPosition,
                        _token: token
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            // Update UI indexes after successful update
                            $('tr.tableRow').each(function(index) {
                                $(this).find('td.level_id').text(index + 1);
                                });
                        } else {
                            alert("error");
                            console.log(response);
                        }
                    }
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endsection
</x-admin-layout>
