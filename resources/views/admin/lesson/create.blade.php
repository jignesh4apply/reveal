<x-admin-layout title="Create Lesson">
    @section('styles')
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap4-tagsinput@4.1.3/tagsinput.min.css">
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">

    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Lessons</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Lessons</li>
                            <li class="breadcrumb-item active">Lesson</li>
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
            <form id="quickForm" action="{{ url('admin/lesson-create') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add Lesson</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/lesson-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}">
                                </div>

                                <div class="form-group">
                                    <label for="description">Description <span style="color: #ff5252;">*</span></label>
                                    <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
                                </div>

                                <!-- <div class="form-group">
                                    <label for="video_link">Video Link</label>
                                    <input type="text" name="video_link" id="video_link" class="form-control" 
                                        value="{{ old('video_link') }}" 
                                        placeholder="Please enter a valid Video Link">
                                </div> -->

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Video </h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="video_title">Title </label>
                                            <input type="text" id="video_title" name="video_title" class="form-control" value="{{ old('video_title') }}"></input>
                                        </div>
                                        <div class="form-group">
                                            <label for="video_description">Description </label>
                                            <input type="text" id="video_description" name="video_description" class="form-control" value="{{ old('video_description') }}"></input>
                                        </div>
                                        <div class="form-group">
                                            <label for="video_link">Link </label>
                                            <input type="text" id="video_link" name="video_link" class="form-control" value="{{ old('video_link') }}" placeholder="Please enter a valid Video Link"></input>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Question <span style="color: #ff5252;">*</span></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="lesson_question">Question For Lesson <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="lesson_question" name="lesson_question" class="form-control" value="{{ old('lesson_question') }}"></input>
                                        </div>
                                        <div class="repeater">
                                            <label for="tip_title">Answer</label>
                                            <div data-repeater-list="answers">
                                                <div data-repeater-item>
                                                    <div class="row">
                                                        <div class="col-lg-11">
                                                            <div class="form-group">
                                                                <input type="text" title="Please enter answers for the questions." id="answer" name="answer" class="form-control" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-1" style="margin-top: 5px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add Answer</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Task <span style="color: #ff5252;">*</span></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label for="task_title">Task Title</label>
                                                    <input type="text" title="Please enter task title for this tip." id="task_title" name="task_title" class="form-control" value="{{ old('task_title') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="stepName">Task Content</label>
                                                    <textarea title="Please write something about this tip." id="task_content" name="task_content" class="form-control" required>{{ old('task_content') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Tip <span style="color: #ff5252;">*</span></h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="repeater">
                                            <div data-repeater-list="tips">
                                                <div data-repeater-item>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <label for="tip_title">Tip Title</label>
                                                                <input type="text" title="Please enter title for this tip." id="tip_title" name="tip_title" class="form-control" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <label for="stepName">Tip Content</label>
                                                                <textarea title="Please write something about this tip." id="tip_content" name="tip_content" class="form-control" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-2" style="margin-top: 35px;">
                                                            <div class="form-group">
                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More Tips</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group row">
                                            <div class="col-lg-6">
                                                <label for="isFeature">Do you want to add Feature link for this Lesson?</label>
                                            </div>
                                            <div class="col-lg-1">
                                                <input type="checkbox" id="isFeature" name="isFeature" style="width: 20px; height:20px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 featureName">
                                        <div class="form-group">
                                            <label for="feature_name">Feature Link <span style="color: #ff5252;">*</span></label>
                                            <select class="form-control" name="feature_name" id="feature_name">
                                                <option value="">Select Feature Link</option>
                                                <option value="calorie_tracker">Calorie Tracker</option>
                                                <option value="program">Program</option>
                                                <option value="photo">Photo</option>
                                                <option value="recipes">Recipes</option>
                                                <option value="community">Community</option>
                                                <option value="bracelet">Bracelet</option>
                                                <option value="seance">Seance</option>
                                                <option value="challenge">Challenge</option>
                                                <option value="liste_de_course">Liste de course</option>
                                                <option value="suggestion">Suggestion</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row featureName">
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label for="feature_title">Feature Title</label>
                                            <input type="text" title="Please enter title for the Feature Screen." id="feature_title" name="feature_title" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="form-group">
                                            <label for="feature_desc">Feature Description</label>
                                            <textarea title="Please enter Description for this feature screen." id="feature_desc" name="feature_desc" class="form-control" required></textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label for="feature_image">Feature Image<span style="color: #ff5252;">*</span></label>
                                            <input title="Please select a image" type="file" id="feature_image" name="feature_image" accept="image/*" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="status" name="status">
                                            <label for="status">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit"value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
    </div>

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap4-tagsinput@4.1.3/tagsinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#quickForm').validate({
                rules: {
                    title: { required: true },
                    lesson_question: { required: true },
                    description: { required: true }
                },
                messages: {
                    title: { required: "Please enter lesson title" },
                    lesson_question: { required: "Please enter question for this lesson" },
                    description: { required: "Please enter description for this lesson" }
                },
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                }
            });
            $('.repeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: true,
                show: function() { $(this).slideDown(); },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

            $(window).on("load", function() {
                var isFeature = $('input[name="isFeature"]:checked').val();
                if (isFeature === 'on') {
                    $('.featureName').show();
                } else {
                    $('.featureName').hide();
                }
            })

            $('#isFeature').change(function() {
                if (!this.checked)
                    $('.featureName').hide();
                else
                    $('.featureName').show();
            });
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