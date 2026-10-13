<x-admin-layout title="Create CMS Page">
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>CMS Pages</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item active">CMS Pages</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form id="create-page" @if(!isset($page) || empty($page)) action="{{ url('admin/page-create') }}" @else acion="{{ url('admin/page-update/'.$page['slug']) }}" @endif method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">@if(!isset($page) || empty($page)) Add  @else Edit  @endif CMS Page</h3>
                                    <div class="card-tools">
                                        <a type="button" class="btn btn-secondary" href="{{ url('admin/page-index') }}">
                                            <i class="fas fa-arrow-left"></i>
                                            Return to List
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <label for="inputContent">Select Image </label>
                                        <div class="col-lg-12 d-flex flex-column align-items-center">
                                            <img id="page-image" class="rounded img-thumbnail" @if(isset($page->image)) src="{{ asset('/storage/'.$page->image) }}" @else src="{{ asset('adminAssets/image/default.png') }}" @endif width="150px" alt="Profile">
                                            <div class="pt-2 text-center">
                                                <a id="page-image-upload" href="javascript:void(0);" class="btn btn-primary btn-sm" title="Upload new image">
                                                    <i class="fa fa-upload"></i>
                                                </a>
                                                <input name="image" id="image" class="form-control d-none" onchange="setImage(this)" type="file" id="image" accept="image/png, image/jpeg, .svg, image/gif">
                                                <div class="valid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="inputContent">Title <span style="color: #ff5252;">*</span></label>
                                        <input type="text" id="title" name="title" class="form-control" @if(isset($page)) value="{{ $page->title }}" @else value="{{ old('title') }}" @endif>
                                    </div>

                                    <div class="form-group">
                                        <label for="inputContent">Content <span style="color: #ff5252;">*</span></label>
                                        <textarea id="content" name="content" class="form-control">@if(isset($page)){{ $page->content }}@else{{ old('content') }}@endif</textarea>
                                        @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @else
                                        <div class="invalid-feedback">Please enter CMS Page Content</div>
                                        @enderror
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <input type="submit" @if(!isset($page)) value="Save" @else value="Update" @endif class="btn btn-success float-right">
                        </div>
                    </div>
                </form>
            </div>
        </section>
        <!-- /.content -->
    </div>
    @section('scripts')
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <script>
        function setImage(img) {
            var image = document.getElementById('page-image');
            image.src = URL.createObjectURL(img.files[0])
        }

        $(function() {
            $('#create-page').validate({
                rules: {
                    title: {
                        required: true,
                    },
                    image: {
                        required: true
                    },
                    content: {
                        required: true
                    }
                },
                messages: {
                    title: {
                        required: "Please enter page title",
                    },
                    image: {
                        required: "Select page image",
                    },
                    content: {
                        required: "Please enter page content",
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

            $('#page-image, #page-image-upload').click(function(e) {
                e.preventDefault();
                $("input[id='image']").click();
            });
        })
    </script>

    <script>
        $(function() {
            // Summernote
            $('#content').summernote();
        })
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
    @endsection
</x-admin-layout>
