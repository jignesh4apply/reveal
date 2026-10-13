<x-admin-layout title="Create Cancel-Subscription Screen">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
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
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Community Header</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Community Header</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
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
            <form action="{{ route('admin.community-header') }}" method="POST" enctype="multipart/form-data" id="communityHeaderForm">
                @csrf

                <!-- Title Field -->
                <div class="form-group">
                    <label for="title">Title</label>
                    <input 
                        type="text" 
                        class="form-control @error('title') is-invalid @enderror" 
                        id="title" 
                        name="title" 
                        value="{{ old('title', $header->title ?? '') }}" 
                        placeholder="Enter title">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        class="form-control @error('description') is-invalid @enderror" 
                        id="description" 
                        name="description" 
                        rows="3"
                        placeholder="Enter description">{{ old('description', $header->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Image Field -->
                <div class="form-group">
                    <label for="image">Image</label>
                    <input 
                        type="file" 
                        class="form-control-file @error('image') is-invalid @enderror" 
                        id="image" 
                        name="image"
                        accept="image/*">
                    @if(!empty($header->image))
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $header->image) }}" alt="Current Image" style="max-height: 100px;">
                        </div>
                    @endif
                    @error('image')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Button Text Field -->
                <div class="form-group">
                    <label for="button">Button Text</label>
                    <input 
                        type="text" 
                        class="form-control @error('button') is-invalid @enderror" 
                        id="button_text" 
                        name="button_text" 
                        value="{{ old('button_text', $header->button_text ?? '') }}" 
                        placeholder="Enter button text">
                    @error('button_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Link Type Radio Buttons -->
                <div class="form-group">
                    <label>Link Type</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="button_type" id="button_type_external" value="external" {{ old('button_type', $header->button_type ?? 'app') == 'external' ? 'checked' : '' }}>
                            <label class="form-check-label" for="button_type_external">External Link</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="button_type" id="button_type_app" value="app" {{ old('button_type', $header->button_type ?? 'app') == 'app' ? 'checked' : '' }}>
                            <label class="form-check-label" for="button_type_app">Redirect within APP</label>
                        </div>
                    </div>
                </div>

                <!-- External Link Input (hidden by default) -->
                <div class="form-group" id="button_url_group" style="display: none;">
                    <label for="button_url">External Link URL</label>
                    <input 
                        type="url" 
                        class="form-control @error('button_url') is-invalid @enderror" 
                        id="button_url" 
                        name="button_url" 
                        value="{{ old('button_url', $header->button_url ?? '') }}" 
                        placeholder="Enter external URL">
                    @error('button_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Button URL Field (moved here for toggling) -->
                <div class="form-group" id="button_red_group" style="display: none;">
                    <label for="button_redirect_to">Button Option</label>
                    <select 
                        class="form-control @error('button_redirect_to') is-invalid @enderror" 
                        id="button_redirect_to" 
                        name="button_redirect_to">
                        <option value="">Select an option</option>
                        <option value="dashboard" {{ old('button_redirect_to', $header->button_redirect_to ?? '') == 'dashboard' ? 'selected' : '' }}>Dashboard</option>
                        <option value="program" {{ old('button_redirect_to', $header->button_redirect_to ?? '') == 'program' ? 'selected' : '' }}>Program</option>
                        <option value="calory_tracker" {{ old('button_redirect_to', $header->button_redirect_to ?? '') == 'calory_tracker' ? 'selected' : '' }}>Calory Tracker</option>
                        <option value="recipes" {{ old('button_redirect_to', $header->button_redirect_to ?? '') == 'recipes' ? 'selected' : '' }}>Recipes</option>
                    </select>
                    @error('button_redirect_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Save Button -->
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#communityHeaderForm').validate({
                rules: {
                    title: {
                        required: true,
                        maxlength: 255
                    },
                    description: {
                        required: true
                    },
                    button: {
                        required: true,
                        maxlength: 100
                    },
                    button_url: {
                        required: true
                    },
                    image: {
                        extension: "jpg|jpeg|png|gif"  // You can adjust the allowed file types as needed
                    }
                },
                messages: {
                    title: {
                        required: "Please enter the title.",
                        maxlength: "Title can't be longer than 255 characters."
                    },
                    description: {
                        required: "Please enter a description."
                    },
                    button: {
                        required: "Please enter the button text.",
                        maxlength: "Button text can't be longer than 100 characters."
                    },
                    button_url: {
                        required: "Please select a button option."
                    },
                    image: {
                        extension: "Only image files (jpg, jpeg, png, gif) are allowed."
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
    </script>
    <script>
        $(document).ready(function() {
            function toggleLinkType() {
                var selected = $('input[name="button_type"]:checked').val();
                if (selected === 'external') {
                    $('#button_url_group').show();
                    $('#button_red_group').hide();
                } else {
                    $('#button_url_group').hide();
                    $('#button_red_group').show();
                }
            }
            // Initial toggle
            toggleLinkType();
            // On change
            $('input[name="button_type"]').on('change', toggleLinkType);
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

    @endsection
</x-admin-layout>
