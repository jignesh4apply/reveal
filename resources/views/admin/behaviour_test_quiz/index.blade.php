<x-admin-layout title="Behaviour Test Questions">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        .tableRow {
            cursor: move;
        }
    </style>
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Behaviour Test</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Questions</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Questions</h3>
                                <div class="card-tools">
                                    <a type="button" href="{{ route('create-behaviour-test-quiz') }}" class="btn btn-secondary">
                                        <i class="nav-icon fas fa-plus"></i>
                                        Add Question
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="customTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Question</th>
                                            <th>Group</th>
                                            <th>Amplitude Word</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reorder_position">
                                        @foreach($quizzes as $key => $quiz)
                                        <tr class="tableRow" data-id="{{ $quiz['id'] }}">
                                            <td data-sort="{{ $key + 1 }}"><i class="fas fa-grip-vertical mr-2 text-muted"></i> {{ $key + 1 }}</td>
                                            <td>{{ $quiz['question'] }}</td>
                                            <td>{{ $quiz['group']['title'] ?? 'N/A' }}</td>
                                            <td>{{ $quiz['amplitude_word'] ?? 'N/A' }}</td>
                                            <td>
                                                @if($quiz['is_active'] == 1)
                                                <span class="badge badge-success">Active</span>
                                                @else
                                                <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ url('admin/update-behaviour-test-quiz/'.$quiz['slug'] .'/'. $quiz['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="behaviour-test-quiz" slug="{{ $quiz['slug'] }}" moduleId="{{ $quiz['id'] }}" message="Question Deleted Successfully"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Order</th>
                                            <th>Question</th>
                                            <th>Group</th>
                                            <th>Amplitude Word</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            $("#reorder_position").sortable({
                items: "tr",
                cursor: 'move',
                opacity: 0.6,
                update: function() {
                    updateQuizPosition();
                }
            });

            function updateQuizPosition() {
                var orderPosition = [];
                var token = $('meta[name="csrf-token"]').attr('content');
                var url = "/admin/set-behaviour-test-quiz-position";

                $('tr.tableRow').each(function(index, element) {
                    orderPosition.push({
                        id: $(this).attr('data-id'),
                        position: index + 1
                    });
                });

                $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: url,
                    data: {
                        order: orderPosition,
                        _token: token
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            toastr.success("Order updated successfully!");
                        } else {
                            toastr.error("Failed to update order.");
                        }
                    },
                    error: function() {
                        toastr.error("An error occurred while reordering.");
                    }
                });
            }
        });
    </script>
    @endsection
</x-admin-layout>
