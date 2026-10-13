<x-admin-layout title="Challenge Level">
    @section('styles')
        <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection

    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Challenges</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Challenges</li>
                            <li class="breadcrumb-item active">Challenge Level</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

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
            @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <form id="challengeLevelForm" action="{{ url('admin/challenge-level-update/'.$levelData->slug .'/'.$levelData->id) }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Add Levels
                                </h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/challenge-level-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="form-group">
                                            <label for="title">Level Title<span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title" name="title" class="form-control" value="{{ $levelData->title }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="status">Status<span style="color: #ff5252;">*</span></label>
                                            <select name="status" id="status" class="form-control">
                                                <option value="1" @if ($levelData->status == 1) selected @endif>Active</option>
                                                <option value="0" @if ($levelData->status == 0) selected @endif>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="description">Level Description<span style="color: #ff5252;">*</span></label>
                                    <textarea type="text" id="description" name="description" class="form-control" >{{ $levelData->description }}</textarea>
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

    </div>
    @section('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
        <script>
            $('#challengeLevelForm').validate({
                rules: {
                    title: {
                        required: true,
                    },
                    description: {
                        required: true,
                    },
                    status: {
                        required: true,
                    }
                },
                messages: {
                    title: {
                        required: "Please enter Level",
                    },
                    description: {
                        required: "Please enter level description",
                    },
                    status: {
                        required: "Please select status for level",
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
        </script>
    @endsection
</x-admin-layout>