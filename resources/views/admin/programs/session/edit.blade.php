<?php

use App\Models\Exercise;
?>
<x-admin-layout title="Create Program">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">
      <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <style>
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #007bff !important;
        color: #fff !important;
        }
        .select2-results__option{
        background-color: #343a40 !important;
        color: #ffffff !important;            /* faded text */
        }
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

        /* #sortable {
            list-style-type: none;
            margin: 0;
            padding: 0;
            width: 60%;
            background: slategrey;
            border-radius: 5px;
            padding: inherit;
        } */

        /* #sortable li {
            list-style-type: none;
            margin-bottom: 5px;
            width: 50%;
            background: slategrey;
            border-radius: 5px;
            padding: 5px;
            cursor: move;
            position: relative;
        }

        .remove-btn {
            position: absolute;
            right: 5px;
            top: -1px;
            cursor: pointer;
            color: red;
            font-size: 25px;
        } */

        #sortable li {
            list-style-type: none;
            margin-bottom: 5px;
            padding: 5px;
            width: 50%;
            cursor: move;
            position: relative;
            background: slategrey;
            border-radius: 5px;
        }

        /* .remove-btn {
            position: absolute;
            right: 5px;
            top: -1px;
            cursor: pointer;
            color: red;
            font-size: 25px;
        } */

        .material {
            list-style-type: none;
            padding: 0;
        }

        .material-list {
            background-color: #3f6791;
            border: 0px solid;
            border-radius: 5px;
            margin-top: 2px;
            margin-bottom: 5px;
            padding: 10px;
            cursor: pointer;
        }
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

            @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ Session::get('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <form id="updateSessionForm" action="{{ url('admin/session-update/'.$session->slug.'/'.$session->id) }}" method="post" enctype="multipart/form-data">
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
                                            <input type="text" id="title" name="title" class="form-control" value="{{ $session->title }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="title_for_frontend">Title for Mobile view <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="title_for_frontend" name="title_for_frontend" class="form-control" value="{{ $session->title_for_frontend }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Body Area <span style="color: #ff5252;">*</span></label>
                                            <input type="text" id="body_area" name="body_area" class="form-control" value="{{ $session->body_area }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Calories <span style="color: #ff5252;">*</span></label>
                                            <input type="number" id="calories" name="calories" min="0" class="form-control" value="{{ $session->calories }}">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Time <span style="color: #ff5252;">*</span></label>
                                            <input type="time" id="time" name="time" class="form-control" value="{{ $session->time }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label for="inputName">Session Type <span style="color: #ff5252;">*</span></label>
                                            <select name="session_type" id="session_type" class="form-control">
                                                <option value="">Select Session Type</option>
                                                <option value="Cardio" @if($session['session_type']=="Cardio" ) selected @endif style="font-weight: bold;">Cardio</option>
                                                <option value="Muscle Strengthening" @if($session['session_type']=="Muscle Strengthening" ) selected @endif style="font-weight: bold;">Muscle Strengthening</option>
                                                <option value="Both" @if($session['session_type']=="Both" ) selected @endif style="font-weight: bold;">Both</option>
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
                                                <option value="Difficult" @if($session['difficulty_level']=="Difficult" ) selected @endif style="font-weight: bold;">Difficult</option>
                                                <option value="Medium" @if($session['difficulty_level']=="Medium" ) selected @endif style="font-weight: bold;">Medium</option>
                                                <option value="Low" @if($session['difficulty_level']=="Low" ) selected @endif style="font-weight: bold;">Low</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="form-group">
                                            <label for="video_link">Video Link<span style="color: #ff5252;">*</span></label>
                                            <input title="Please enter video link" type="text" id="video_link" name="video_link" class="form-control" value="{{ $session['video_link'] }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="summary">Summary <span style="color: #ff5252;">*</span></label>
                                    <textarea id="summary" name="summary" class="form-control">{{ $session->summary }}</textarea>
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
                                            <select id="selectOption" name="session_exercise" class="form-control">
                                                <option value="" disabled>Select Exercise</option>
                                                @foreach ($exerciseData as $exercise)
                                                <option value="{{ $exercise['id'] }}" {{in_array($exercise['id'], explode(",", $session->exercise)) ? 'selected' : ''}}>{{ $exercise['title'] }}</option>
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
                                    <input type="checkbox" class="form-check-input" id="is_new_exercise_flow" name="is_new_exercise_flow" @if(old('is_new_exercise_flow', $session->is_new_exercise_flow)) checked @endif>
                                    <label class="form-check-label" for="is_new_exercise_flow">Use New Session Exercise Flow</label>
                                </div>
                                <ul id="sortable">
                                    @php
                                    $listItems = [];
                                    $index=1;
                                    @endphp
                                    @foreach($session->session_exercises as $item)

                                    @if(!empty($item))
                                    <?php
                                    $exercise = Exercise::where(['id' => $item['session_exercise_id']])->first();
                                    ?>

                                    @php
                                    $listItems[] = $item['session_exercise_id'];
                                    @endphp
                                    <div class="row exercise-row" index="{{$index++}}" data-row-id="existing_{{ $item['id'] }}" data-exercise-id="{{ $item['session_exercise_id'] }}">
                                        <li data-id="{{ $item['id'] }}" data-exercise-id="{{ $item['session_exercise_id'] }}" class="mt-2 p-2 col-10">
                                            {{ $exercise['title'] }}
                                            <div class="session-exercise-meta-fields {{ old('is_new_exercise_flow', $session->is_new_exercise_flow) ? '' : 'd-none' }}">
                                                <div class="row mt-2">
                                                    <div class="col-md-3">
                                                        <select class="form-control exercise-form">
                                                            <option value="repetition" {{ ($item->exercise_form ?? 'repetition') === 'repetition' ? 'selected' : '' }}>Repetition</option>
                                                            <option value="duration" {{ ($item->exercise_form ?? '') === 'duration' ? 'selected' : '' }}>Duration</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 rest-period-group">
                                                        <input type="time" step="1" class="form-control rest-period" title="Rest Period" value="{{ $item->rest_period ?? '' }}">
                                                    </div>
                                                    <div class="col-md-3 duration-group {{ ($item->exercise_form ?? '') === 'duration' ? '' : 'd-none' }}">
                                                        <input type="time" step="1" class="form-control duration" title="Duration" value="{{ $item->duration ?? '' }}">
                                                    </div>
                                                    <div class="col-md-6 repetition-group {{ ($item->exercise_form ?? 'repetition') === 'repetition' ? '' : 'd-none' }}" style="display: flex;gap: 15px;">
                                                        <input type="number" min="0" class="form-control repetition" placeholder="Reps" value="{{ $item->repetition ?? '' }}">
                                                        <input type="number" min="0" class="form-control repetition_range" placeholder="Reps" value="{{ $item->repetition_range ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <a rel='{{ $item['id'] }}' rell='delete-session-exercise' sessionid="{{ $session->id }}" href='javascript:void(0);' class='btn btn-sm btn-danger delete_exercise ml-4 mt-2 p-2' message='Exercise Deleted Successfully'>
                                                <i class='fas fa-trash'></i>
                                            </a>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </ul>
                                <input type="hidden" id="listItems" name="listItems" value="{{ implode(',', $listItems) }}">
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
                                <div class="materialrepeater">
                                    <div data-repeater-list="items">
                                        @if($session->materials->count())
                                            @foreach($session->materials as $index => $sessionMaterial)
                                            <div data-repeater-item class="row mb-3 align-items-center">
                                                <input type="hidden" name="items[{{ $index }}][materialId]" value="{{ $sessionMaterial->id }}">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="materialName">Title <span style="color: #ff5252;">*</span></label>
                                                        <input type="text" title="Please enter material name." id="material-name" name="items[{{ $index }}][materialName]" class="form-control" value="{{ $sessionMaterial->material_name }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    @if($sessionMaterial['material_image'])
                                                        <img src="{{ asset('storage/'. $sessionMaterial['material_image']) }}" alt="{{ $sessionMaterial['material_image'] }}" height="70px" width="70px">
                                                    @else
                                                        <div class="form-group">
                                                            <label for="materialImage">Image <span style="color: #ff5252;">*</span></label>
                                                            <input type="file" title="Please select an image." id="material-image" name="items[{{ $index }}][materialImage]" class="form-control" accept="image/*" required>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-2" style="margin-top: 35px;">
                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete><i class='fas fa-trash'></i></button>
                                                </div>
                                            </div>
                                            @endforeach
                                        @else
                                            <div data-repeater-item class="row mb-3 align-items-center">
                                                <input type="hidden" name="items[0][materialId]" value="">
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="materialName">Title <span style="color: #ff5252;">*</span></label>
                                                        <input type="text" title="Please enter material name." id="material-name" name="items[0][materialName]" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="form-group">
                                                        <label for="materialImage">Image <span style="color: #ff5252;">*</span></label>
                                                        <input type="file" title="Please select an image." id="material-image" name="items[0][materialImage]" class="form-control" accept="image/*" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-2" style="margin-top: 35px;">
                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete><i class='fas fa-trash'></i></button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" id="addMore" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More</button>
                                </div>

                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>

                    <div class="form-check ml-2">
                        <input type="checkbox" class="form-check-input" id="status" name="status" @if($session['status']==1) checked @endif>
                        <label for="status">Enable</label>
                    </div>
                    <!-- /.card -->
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <a href="{{ url('admin/session-duplicate/'.$session->slug.'/'.$session->id) }}" class="btn btn-warning">Duplicate Session</a>
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="submit" value="Update" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <!-- ✅ 1. jQuery (must be loaded first) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- ✅ 2. jQuery UI (sortable, draggable, etc.) -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <!-- ✅ 4. Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- ✅ 5. jQuery Validation -->
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    <!-- ✅ 6. jQuery Repeater (must be after jQuery) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

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

            $("#sortable").on('input change', '.rest-period, .duration, .repetition, .repetition_range ', function() {
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

            // Call updateListItems to initialize the hidden input value
            updateListItems();
        });

        function addToList() {
            var selectedOptionId = $("#selectOption").val();
            var selectedOptionTitle = $("#selectOption option:selected").text();
            var newFlowEnabled = $("#is_new_exercise_flow").is(':checked');

            if (selectedOptionId == null) {
                alert("Please select a Exercise.");
                return;
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
                    <li data-id="${selectedOptionId}" data-exercise-id="${selectedOptionId}" class="mt-2 p-2 exercise-item col-md-10">
                        <strong>${selectedOptionTitle}</strong>
                        ${metaFields}
                    </li>
                    <div class="col-md-2 d-flex align-items-center">
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

    <script>
    $(document).on("click", ".delete_exercise", function() {
            var id = $(this).attr("rel");
            var sessionId = $(this).attr("sessionid");
            var new_added = $(this).attr("new_added");
            var element = $(this).closest('.exercise-row');
            var deletefunction = $(this).attr("rell");
            var message = $(this).attr("message");
            Swal.fire({
                // position: 'top-end',
                title: "Are you sure, you want to delete this record ? ",
                text: "You won't be able to revert this!",
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

                    // Toast.fire({
                    //     icon: "success",
                    //     title: message,
                    // });
                    if(new_added == 'true'){
                        element.remove();
                        updateListItems();
                        Toast.fire({
                            icon: "success",
                            title: "Session Exercise has been deleted successfully!!",
                        });
                    } else{
                        element.remove();
                        updateListItems();
                        $.ajax({
                        type:"GET",

                         url:"/admin/" + deletefunction + "/" + id + "/" + sessionId,
                        success:function(response){
                            if(response.error){
                                 $("#sortable").append(element);
                                let sortedItems = $('#sortable .row').sort(function(a, b) {
                                     return parseInt($(a).attr('index')) - parseInt($(b).attr('index'));
                                     });
                                    $("#sortable").html(sortedItems);
                                    Toast.fire({
                                        icon: "error",
                                        title: response.error,
                                    });
                            }
                            if(response.success){
                                 Toast.fire({
                                        icon: "success",
                                        title: response.success,
                                    });
                            }
                        }, error: function(xhr, status, error) {
                            alert("An error occurred: " + error);
                            // If AJAX fails, re-append the element back
                            $("#sortable").append(element);
                            updateListItems();
                            Toast.fire({
                                icon: "error",
                                title: "An error occurred in deletion",
                            });
                        }
                    })

                    }
                }
            });
        });
    </script>

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
                initEmpty: false,
                isFirstItemUndeletable: true,
                show: function() {
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });
            // End Material Required

            $('.materialrepeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: false,
                show: function() {
                    var $repeater = $(this).closest('.materialrepeater');
                    var index = $repeater.find('[data-repeater-item]').length - 1;

                    $(this).find('[name]').each(function() {
                        var name = $(this).attr('name').replace("items[][", "items[" + index + "][");
                        $(this).attr('name', name);
                    });

                    var materialImageInput = `<div class="form-group">
                                                <label for="materialImage">Image <span style="color: #ff5252;">*</span></label>
                                                <input type="file" title="Please select an image." id="material-image" name="items[${index}][materialImage]" class="form-control" accept="image/*" required>
                                            </div>`;

                    if ($(this).find('img').length > 0) {
                        $(this).find('img').remove();
                        $(this).find('.col-md-5').last().append(materialImageInput);
                    }
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

            $.validator.addMethod('atLeastOneExercise', function () {
                return $('#sortable li').length > 0;
                }, 'Please add at least one exercise.');
            // Form Validation
            $('#updateSessionForm').validate({
                rules: {
                    title: {
                        required: true,
                    },
                    program_id: {
                        required: true
                    },
                    trailer: {
                        accept: "video/*, image/*",
                        mimes: "mp4,mov,ogg, jpg,jpeg,png",
                    },
                    difficulty_level: {
                        required: true
                    },
                    body_area: {
                        required: true
                    },
                    required_material: {
                        required: true
                    },
                    frequency: {
                        required: true
                    },
                    time: {
                        required: true
                    },
                    video_link: {
                        required: true,
                    },
                    summary: {
                        required: true
                    },
                    calories: {
                        required: true
                    },
                    session_type: {
                        required: true
                    }, session_exercise:{
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
                    body_area: {
                        required: "Please enter body area"
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
                    video_link: {
                        required: "Please enter a video link",
                    },
                    summary: {
                        required: "Please Write Session Summary"
                    },
                    calories: {
                        required: "Calories field is required"
                    },
                    session_type: {
                        required: "Please select Session Type"
                    },session_exercise: {
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
    @endsection
</x-admin-layout>
