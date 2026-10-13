<x-admin-layout title="Update Exercise">
    @section('styles')
    <style>
        .ck.ck-editor__editable_inline {
            color: #000;
            min-height: 200px;
        }

        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        .switch-field {
            display: flex;
            margin-bottom: 8px;
            border-color: #0d6efd;
            overflow: hidden;
        }

        .switch-field input {
            position: absolute !important;
            clip: rect(0, 0, 0, 0);
            height: 1px;
            width: 1px;
            border: 0;
            overflow: hidden;
        }

        .switch-field label {
            background-color: #e4e4e4;
            color: #0d6efd;
            /* color: rgba(0, 0, 0, 0.6); */
            font-size: 14px;
            line-height: 1;
            text-align: center;
            padding: 8px 16px;
            margin-right: -1px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            /* box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px rgba(255, 255, 255, 0.1); */
            transition: all 0.1s ease-in-out;
        }

        .switch-field label:hover {
            cursor: pointer;
        }

        .switch-field input:checked+label {
            background-color: #0d6efd;
            color: whitesmoke;
            box-shadow: none;
        }

        .switch-field label:first-of-type {
            border-radius: 4px 0 0 4px;
        }

        .switch-field label:last-of-type {
            border-radius: 0 4px 4px 0;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Sessions</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Sessions</li>
                            <li class="breadcrumb-item active">Exercise</li>
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
            <form id="updateExerciseForm" action="{{ url('admin/exercise-update/'.$exercise->slug.'/'.$exercise->id) }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Update Exercise</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/exercise-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="title">Title for Admin Panel <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title" name="title" class="form-control" value="{{ $exercise->title }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="title_for_frontend">Title for Mobile view <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title_for_frontend" name="title_for_frontend" class="form-control" value="{{ old('title_for_frontend', $exercise->title_for_frontend) }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group form-check">
                                            <input type="checkbox" class="form-check-input" id="is_new_exercise_flow" name="is_new_exercise_flow" @if(old('is_new_exercise_flow', $exercise->is_new_exercise_flow)) checked @endif>
                                            <label class="form-check-label" for="is_new_exercise_flow">Use New Exercise Flow</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="instructions">Instructions <span style="color: #ff5252;">*</span></label>
                                    <textarea id="instructions" name="instructions" class="form-control">{{ $exercise->instructions }}</textarea>
                                </div>

                                <div class="row legacyExerciseFields">
                                    <div class="col-lg-3">
                                        <div class="form-group row">
                                            <div class="col-lg-9 mt-3">
                                                <div class="switch-field">
                                                    <input type="radio" name="exercise_form" id="duration" value="duration" @if(old('exercise_form', $exercise->exercise_form) === 'duration') checked @endif />
                                                    <label for="duration">Duration</label>
                                                    <input type="radio" name="exercise_form" id="repetition" value="repetition" @if(old('exercise_form', $exercise->exercise_form) === 'repetition') checked @endif />
                                                    <label for="repetition">Repetition</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-9">
                                        <div class="form-group duration">
                                            <label for="duration">Duration <span style="color: #ff5252;">*</span></label>
                                            <input type="time" step=1 class="form-control" id="duration_t" name="duration" value="{{ $exercise->duration }}">
                                        </div>

                                        <div class="row repetition">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="no_of_repetition">No of Repetition</label>
                                                    <input type="number" name="no_of_repetition" id="no_of_repetition" class="form-control" min="0" value="{{ $exercise->no_of_repetition }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="range_of_repetition">Range of Repetition</label>
                                                    <input type="number" name="range_of_repetition" id="range_of_repetition" class="form-control" min="0" value="{{ $exercise->range_of_repetition }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 legacyExerciseFields">
                                        <div class="form-group">
                                            <label for="rest_period">Rest Period <span style="color: #ff5252;">*</span></label>
                                            <input type="time" step="1" id="rest_period" name="rest_period" class="form-control" value="{{ $exercise->rest_period }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group gif">
                                            <label for="gif">Select Image <span style="color: #ff5252;">*</span></label>
                                            <input title="Please select Image" type="file" id="gif" name="gif" accept="image/*" class="form-control"><br>
                                            @if(!empty($exercise->gif))
                                                <img src="{{ asset('/storage/'. $exercise->gif) }}" alt="" srcset="" style="height: 100px; width:auto;">
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="video">Video Link <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="video" name="video" class="form-control" value="{{ $exercise->video }}">
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="body_zone">Body Zone <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="body_zone" name="body_zone" class="form-control" value="{{ $exercise->body_zone }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="exercise_type">Exercise Type <span style="color: #ff5252;">*</span></label>
                                            <select name="exercise_type" id="exercise_type" class="form-control">
                                                <option value="">Select Exercise Type</option>
                                                <option value="cardio" @if($exercise->exercise_type == "cardio") selected @endif>Cardio</option>
                                                <option value="muscle strengthening" @if($exercise->exercise_type == "muscle strengthening") selected @endif>Muscle Strengthening</option>
                                                <option value="both" @if($exercise->exercise_type == "both") selected @endif>Both</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" @if($exercise['status']==1) checked @endif>
                                    <label for="status">Enable</label>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit" value="Update" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    <script>
        function toggleExerciseFields(isNewFlow) {
            if (isNewFlow) {
                $('.legacyExerciseFields').hide();
            } else {
                $('.legacyExerciseFields').show();
                var selected = $('input[name="exercise_form"]:checked').val();
                if (selected === 'duration') {
                    $('.duration').show();
                    $('.repetition').hide();
                } else {
                    $('.repetition').show();
                    $('.duration').hide();
                }
            }
        }

        function initUpdateExerciseForm() {
            toggleExerciseFields($('#is_new_exercise_flow').is(':checked'));

            $('#is_new_exercise_flow').on('change', function() {
                toggleExerciseFields($(this).is(':checked'));
            });

            $('input[name="exercise_form"]').on('change', function() {
                if (!$('#is_new_exercise_flow').is(':checked')) {
                    toggleExerciseFields(false);
                }
            });
        }

        $(function() {
            $('#updateExerciseForm').validate({
                rules: {
                    title: { required: true },
                    title_for_frontend: { required: true },
                    instructions: { required: true },
                    body_zone: { required: true },
                    exercise_type: { required: true },
                    video: { required: true },
                    exercise_form: {
                        required: function() {
                            return !$('#is_new_exercise_flow').is(':checked');
                        }
                    },
                    duration: {
                        required: function() {
                            return !$('#is_new_exercise_flow').is(':checked') && $('#duration').is(':checked');
                        }
                    },
                    no_of_repetition: {
                        required: function() {
                            return !$('#is_new_exercise_flow').is(':checked') && $('#repetition').is(':checked');
                        }
                    },
                    range_of_repetition: {
                        required: function() {
                            return !$('#is_new_exercise_flow').is(':checked') && $('#repetition').is(':checked');
                        }
                    },
                    rest_period: {
                        required: function() {
                            return !$('#is_new_exercise_flow').is(':checked');
                        }
                    }
                },
                messages: {
                    title: { required: "Please enter program title" },
                    title_for_frontend: { required: "Please enter mobile title" },
                    duration: { required: "Please enter exercise duration" },
                    rest_period: { required: "Please enter rest period" },
                    video: { required: "Please enter exercise video link." },
                    no_of_repetition: { required: "Please enter number of repetitions" },
                    range_of_repetition: { required: "Please enter range of repetitions" },
                    body_zone: { required: "Please enter body zone." },
                    exercise_type: { required: "Please enter exercise type." },
                    instructions: { required: "Please write something in words about this exercise." }
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

            initUpdateExerciseForm();
        });
    </script>
    @endsection
</x-admin-layout>
