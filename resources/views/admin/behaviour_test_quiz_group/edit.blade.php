<x-admin-layout title="Update Behaviour Test Quiz Group">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">
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
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Behaviour Test</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Quiz Group</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
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
            <form id="quizGroupForm" action="{{ url('admin/update-behaviour-test-quiz-group/'.$quizGroupData['slug'].'/'.$quizGroupData['id']) }}" method="post">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Quiz Group</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="title">Title</label>
                                            <input type="text" class="form-control" id="title" name="title" value="{{ $quizGroupData['title'] }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="quiz_group_order">Order</label>
                                            <input type="number" class="form-control" min="1" id="quiz_group_order" name="quiz_group_order" value="{{ $quizGroupData['quiz_group_order'] }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

    <script>
        $(document).ready(function() {
            $(function() {
                $('#quizGroupForm').validate({
                    rules: {
                        title: {
                            required: true,
                        },
                        quiz_group_order: {
                            required: true,
                        },
                    },
                    messages: {
                        title: {
                            required: "Please enter Quiz group title.",
                        },
                        quiz_group_order: {
                            required: "Please enter quiz group order",
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
            });
        });
    </script>

    <script>
        var jsArray = @json($quizGroupOrder);
        var form = document.getElementById('quizGroupForm');
        var inputOrder = document.getElementById('quiz_group_order');

        form.addEventListener('submit', function(event) {
            var value = parseInt(inputOrder.value);
            if (jsArray.includes(value)) {
                if (!confirm("Another Quiz Group is already associated with this position. Do you want to replace it?")) {
                    event.preventDefault();
                }
            }
        });
    </script>
    @endsection
</x-admin-layout>
