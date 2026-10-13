<x-admin-layout title="Create Program">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">
    <style>
         .select2-container--default .select2-selection--single .select2-selection__rendered{
            color: #fff !important;
        }

        button.btn.btn-sm.btn-primary.mt-2.ml-40  {
            display: block !important;
        }

        .select2-container--default .select2-selection--single{
            background-color: #343a40 !important;
            height: 38px !important;
            border-color: #6c757d !important;
            color: #fff !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #007bff !important;
            color: #fff !important;
        }
        .select2-results__option{
            background-color: #343a40 !important;
            color: #ffffff !important;            /* faded text */
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow{
             height: 38px !important;
        }

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

        .select2-container--default .select2-selection--multiple {
            background-color: transparent;
        }

        .dark-mode .select2-purple .select2-container--default .select2-search--inline .select2-search__field:focus {
            border: none;
        }

        #sortable li {
            list-style-type: none;
            margin-bottom: 5px;
            width: 50%;
            background: slategrey;
            border-radius: 5px;
            padding: 5px;
            cursor: move;
            position: relative;
        }

        /* .remove-btn {
            position: absolute;
            right: 10px;
            top: -1px;
            cursor: pointer;
            color: red;
            font-size: 25px;
        } */
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
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
                            <li class="breadcrumb-item active">Session</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            @if($errors->any())
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
            <form id="createSessionForm" action="{{ url('admin/session-create') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Session Information</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- <div class="form-group">
                                    <label for="inputDescription">Program</label>
                                    <select name="program_id" id="program_id" class="form-control">
                                        <option value="">Select Program</option>
                                        @foreach($programs as $program)
                                        @if(!is_null($program['category']))
                                        <option value="{{ $program['id'] }}" @if(isset($session) && $session['program_id']==$program['id']) selected @endif style="font-weight: bold;">{{ $program['title'] }}</option>
                                        @endif
                                        @endforeach
                                    </select>
                                </div> -->

                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="title">Title for Admin Panel <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="title_for_frontend">Title for Mobile view <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title_for_frontend" name="title_for_frontend" class="form-control" value="{{ old('title_for_frontend') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Body Area </label>
                                            <input type="text" id="body_area" name="body_area" class="form-control" value="{{ old('body_area') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Calories </label>
                                            <input type="number" id="calories" name="calories" min="0" class="form-control" value="{{ old('calories') }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Time <span style="color: #ff5252;">*</span></label>
                                            <input type="time" id="time" name="time" class="form-control" value="{{ old('time') }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Session Type <span style="color: #ff5252;">*</span></label>
                                            <select name="session_type" id="session_type" class="form-control">
                                                <option value="">Select Session Type</option>
                                                <option value="Cardio" style="font-weight: bold;">Cardio</option>
                                                <option value="Muscle Strengthening" style="font-weight: bold;">Muscle Strengthening</option>
                                                <option value="Both" style="font-weight: bold;">Both</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Difficulty Level <span style="color: #ff5252;">*</span></label>
                                            <select name="difficulty_level" id="difficulty_level" class="form-control">
                                                <option value="">Select Level</option>
                                                <option value="Difficult" style="font-weight: bold;">Difficult</option>
                                                <option value="Medium" style="font-weight: bold;">Medium</option>
                                                <option value="Low" style="font-weight: bold;">Low</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label for="inputName">Video Link</label>
                                            <input title="Please enter video link" type="text" id="video_link" name="video_link"  class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="inputName">Summary <span style="color: #ff5252;">*</span></label>
                                    <textarea id="summary" name="summary" class="form-control">{{ old('summary') }}</textarea>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>

                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Exercise Information</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exercise">Exercise <span style="color: #ff5252;">*</span></label>
                                            <select name="session_exercise" id="selectOption" class="form-control">
                                                <option value="" disabled selected>Select Exercise</option>
                                                @foreach ($exerciseData as $exercise)
                                                <option value="{{ $exercise['id'] }}">{{ $exercise['title'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <button id="add_to_list" class="btn btn-sm btn-primary" style="margin-top: 35px;" onclick="addToList(); return false;">Add Exercise</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="is_new_exercise_flow" name="is_new_exercise_flow" {{ old('is_new_exercise_flow') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_new_exercise_flow">Use New Session Exercise Flow</label>
                                </div>
                                <ul id="sortable" name="sortable">
                                    <!-- List items will be added dynamically here -->
                                </ul>

                                <input type="hidden" id="listItems" name="listItems">
                                <input type="hidden" id="session_exercise_meta" name="session_exercise_meta" value="{{ old('session_exercise_meta', '[]') }}">
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>

                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Material Required</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="repeater">
                                    <div data-repeater-list="items">
                                        <div data-repeater-item>
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="materialName">Title</label>
                                                        <input type="text" title="Please enter material name." id="material-name" name="materialName" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="materialImage">Image</label>
                                                        <input type="file" title="Please select an image." id="material-image" name="materialImage" class="form-control" accept="image/*" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-2" style="margin-top: 35px;">
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More</button>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <div class="form-check ml-4">
                        <input type="checkbox" class="form-check-input" id="status" name="status">
                        <label for="status">Enable</label>
                    </div>
                    <!-- /.card -->
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    <!-- <script src="https://cdn.ckeditor.com/ckeditor5/35.1.0/classic/ckeditor.js"></script> -->


    <!-- Add Exercise -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#sortable").sortable({
                update: function(event, ui) {
                    updateListItems();
                }
            });
            $("#sortable").disableSelection();

            $("#is_new_exercise_flow").on('change', function() {
                var show = $(this).is(':checked');
                $('.session-exercise-meta-fields').toggleClass('d-none', !show);
                serializeSessionExerciseMeta();
            });

            $("#sortable").on('change', '.exercise-form', function() {
                var row = $(this).closest('.exercise-row');
                updateExerciseMetaFieldVisibility(row);
                serializeSessionExerciseMeta();
            });

            $("#sortable").on('input change', '.rest-period, .duration, .repetition, .repetition_range', function() {
                serializeSessionExerciseMeta();
            });

            // Event delegation to handle dynamically added remove buttons
            $("#sortable").on("click", ".remove-btn", function() {
                $(this).closest(".exercise-row").remove();
                updateListItems();
            });

            function updateExerciseMetaFieldVisibility(row) {
                var selected = row.find('.exercise-form').val();
                row.find('.rest-period-group').removeClass('d-none');
                row.find('.duration-group').toggleClass('d-none', selected !== 'duration');
                row.find('.repetition-group').toggleClass('d-none', selected !== 'repetition');
            }
        });

        function addToList() {
            var selectedOptionId = $("#selectOption").val();
            var selectedOptionTitle = $("#selectOption option:selected").text();
            var newFlowEnabled = $("#is_new_exercise_flow").is(':checked');

            if (selectedOptionId == null) {
                alert("Please select a Exercise.");
                return; // Exit the function if no valid option is selected
            }

            var rowId = 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
            var metaFields = `
                <div class="session-exercise-meta-fields ${newFlowEnabled ? '' : 'd-none'}">
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <select class="form-control exercise-form">
                                <option value="repetition">Repetition</option>
                                <option value="duration">Duration</option>
                            </select>
                        </div>
                        <div class="col-md-3 rest-period-group">
                            <input type="time" step="1" class="form-control rest-period" title="Rest Period">
                        </div>
                        <div class="col-md-3 duration-group d-none">
                            <input type="time" step="1" class="form-control duration" title="Duration">
                        </div>
                        <div class="col-md-6 repetition-group" style="display: flex;gap: 15px;">
                            <input type="number" min="0" class="form-control repetition" placeholder="Reps">
                            <input type="number" min="0" class="form-control repetition_range" placeholder="Range of Reps">
                        </div>
                    </div>
                </div>
            `;

            var newItem = `
                <div class="row exercise-row" data-row-id="${rowId}" data-exercise-id="${selectedOptionId}">
                    <li data-id="${selectedOptionId}" data-exercise-id="${selectedOptionId}" class="mt-2 p-2 exercise-item col-10">
                        <strong>${selectedOptionTitle}</strong>
                        ${metaFields}
                    </li>
                    <div class="col-2 d-flex align-items-center">
                        <span class="remove-btn btn-danger ml-4 mt-2 p-2"><i class="fas fa-trash"></i></span>
                    </div>
                </div>
            `;

            $("#sortable").append(newItem);
            updateListItems();
        }

        function updateListItems() {
            var listItems = $("#sortable li").map(function() {
                return $(this).data('exercise-id');
            }).get();
            $("#listItems").val(listItems.join(','));
            serializeSessionExerciseMeta();
        }

        function serializeSessionExerciseMeta() {
            var metaData = [];
            $("#sortable .exercise-row").each(function() {
                var rowId = $(this).data('row-id');
                var exerciseId = $(this).data('exercise-id');
                if (!exerciseId) {
                    return;
                }
                metaData.push({
                    row_id: rowId || null,
                    exercise_id: exerciseId,
                    exercise_form: $(this).find('.exercise-form').val() || null,
                    rest_period: $(this).find('.rest-period').val() || null,
                    duration: $(this).find('.duration').val() || null,
                    repetition: $(this).find('.repetition').val() || null,
                    repetition_range: $(this).find('.repetition_range').val() || null,
                });
            });
            $("#session_exercise_meta").val(JSON.stringify(metaData));
        }
    </script>
    <!-- End Add Exercise -->

    <script>
        $(document).ready(function() {
            // Summer Note
            $(function() {
                // Summernote
                $('#required_material').summernote();
            })
            // End Summer Note

            // Select Dropdown
            $('.select2').select2();
            $('#selectOption').select2( );

            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })
            // End Select DropDown

            // Material Required
            $('.repeater').repeater({
                initEmpty: true,
                isFirstItemUndeletable: true,
                show: function() {
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                var $parent = $(this).parent();
                Swal.fire({
                        // position: 'top-end',
                        title: "Are you sure, you want to delete this record ? ",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, delete it!"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener("mouseenter", Swal.stopTimer);
                                    toast.addEventListener("mouseleave", Swal.resumeTimer);
                                },
                            });
                            $(this).slideUp(function() {
                            $(this).remove();
                            if ($parent.find('[data-repeater-item]').length === 0) {
                                $parent.append('<div data-repeater-item class="row mb-3 align-items-center">' +
                                    '<input type="hidden" name="items[0][materialId]" value="">' +
                                    '<div class="col-md-5">' +
                                    '<div class="form-group">' +
                                    '<label for="materialName">Title <span style="color: #ff5252;">*</span></label>' +
                                    '<input type="text" title="Please enter material name." id="material-name" name="items[0][materialName]" class="form-control" required>' +
                                    '</div>' +
                                    '</div>' +
                                    '<div class="col-md-5">' +
                                    '<div class="form-group">' +
                                    '<label for="materialImage">Image <span style="color: #ff5252;">*</span></label>' +
                                    '<input type="file" title="Please select an image." id="material-image" name="items[0][materialImage]" class="form-control" accept="image/*" required>' +
                                    '</div>' +
                                    '</div>' +
                                    '<div class="col-md-2">' +
                                    '<button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>' +
                                    '</div>' +
                                    '</div>');
                            }
                        });
                        }
                    });
                }
            });
            // End Material Required

            // Form Validation
            $.validator.addMethod('atLeastOneExercise', function () {
                return $('#sortable .exercise-item').length > 0;
                }, 'Please add at least one exercise.');
            $('#createSessionForm').validate({

                rules: {
                    title: {
                        required: true,
                    },
                    program_id: {
                        required: true
                    },
                    trailer: {
                        required: true,
                        accept: "video/*, image/*",
                        extension: "mp4,mov,ogg, jpg,jpeg,png",
                    },
                    difficulty_level: {
                        required: true
                    },
                    frequency: {
                        required: true
                    },
                    time: {
                        required: true
                    },
                    summary: {
                        required: true
                    },
                    session_type: {
                        required: true
                    },
                    session_exercise:{
                         atLeastOneExercise :true
                    }
                },
                messages: {
                    title: {
                        required: "Please enter program title",
                    },
                    category_id: {
                        required: "Please select category",
                    },
                    trailer: {
                        required: "Please upload a video file",
                        accept: "This field only accept video file"
                    },
                    difficulty_level: {
                        required: "Please Select Level"
                    },
                    duration: {
                        required: "Please enter program duration"
                    },
                    frequency: {
                        required: "Please enter program frequency"
                    },
                    session_duration: {
                        required: "Please enter program's session duratoin"
                    },
                    summary: {
                        required: "Please Write Session Summary"
                    },
                    session_type: {
                        required: "Please select Session Type"
                    },
                    session_exercise: {
                        required: "Please select atleast one excercise"
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
            // End Form Validation
        });
    </script>
    <!-- <script>
        $(document).ready(function() {
            var maxField = 10; //Input fields increment limitation
            var addButton = $(".add_material"); //Add button selector
            var wrapper = $(".material_field"); //Input field wrapper
            var fieldHTML =
                "<div class='material_field'>" +
                "<div>" +
                "<div class='row'>" +
                "<div class='col-sm-3 dynamicInput mt-5'>" +
                "<label for='material-name'>Title</label>" +
                "<input type='text' id='material-name' name='materialName[]' class='form-control material-name' pattern='[A-Za-z_ ]{1,50}' title='Please enter material name.' required>" +
                "</div>" +
                "<div class='col-sm-3 dynamicInput mt-5'>" +
                "<label for='material-image'>Image</label>" +
                "<input type='file' id='material-image' name='materialImage[]' class='form-control material-image' title='Please select an Image.' required>" +
                "</div>" +
                "<a href='javascript:void(0);' class='btn btn-danger btn-sm float-right remove_material' title='Remove field'>X</a>" +
                "</div>" +
                "</div>" +
                "</div>";

            //Once add button is clicked
            $(addButton).click(function() {
                $(wrapper).append(fieldHTML); //Add field html
            });

            //Once remove button is clicked
            $(wrapper).on("click", ".remove_material", function(e) {
                e.preventDefault();
                $(this).parent("div").remove(); //Remove field html
                x--; //Decrement field counter
            });

            console.log(wrapper);
        });
    </script> -->

    @endsection
</x-admin-layout>
