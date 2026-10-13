<x-admin-layout title="Behaviour Test Quiz Group">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
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
                            <li class="breadcrumb-item active">Quiz Group</li>
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
                                <h3 class="card-title">Quiz Group</h3>
                                <div class="card-tools">
                                    <a type="button" href="{{ route('create-behaviour-test-quiz-group') }}" class="btn btn-secondary">
                                        <i class="nav-icon fas fa-plus"></i>
                                        Add Quiz Group
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="customTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reorder_position">
                                        @foreach($quizGroups as $key => $quizGroup)
                                        <tr class="tableRow" data-id="{{ $quizGroup['id'] }}">
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $quizGroup['title'] }}</td>
                                            <td>{{ $quizGroup['slug'] }}</td>
                                            <td>
                                                <a href="{{ url('admin/update-behaviour-test-quiz-group/'.$quizGroup['slug'] .'/'. $quizGroup['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="behaviour-test-quiz-group" slug="{{ $quizGroup['slug'] }}" moduleId="{{ $quizGroup['id'] }}" message="Quiz Group Deleted Successfully"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Title</th>
                                            <th>Slug</th>
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
                    updateQuizGroupPosition();
                }
            });

            function updateQuizGroupPosition() {
                var orderPosition = [];
                var token = $('meta[name="csrf-token"]').attr('content');
                var url = "/admin/set-behaviour-test-quiz-group-position";

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
                            console.log(response);
                        } else {
                            console.log(response);
                        }
                    }
                });
            }
        });
    </script>
    @endsection
</x-admin-layout>
