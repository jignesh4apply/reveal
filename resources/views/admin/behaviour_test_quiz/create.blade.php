<x-admin-layout title="Create Behaviour Test Question">
    @section('styles')
    <style>
        .inner-repeater {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        .dark-mode .inner-repeater {
            background-color: #343a40;
        }
    </style>
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-lg-6">
                        <h1>Create Question</h1>
                    </div>
                    <div class="col-lg-6">
                        <ol class="breadcrumb float-lg-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Create Question</li>
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

            <form id="quizForm" action="{{ url('admin/create-behaviour-test-quiz') }}" method="post" novalidate>
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Question Details</h3>
                            </div>
                            <div class="card-body">
                                <!-- Question Text -->
                                <div class="form-group">
                                    <label for="question">Question <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="question" name="question" value="{{ old('question') }}" placeholder="Enter Question" required>
                                </div>

                                <div class="row">
                                    <!-- Quiz Group Dropdown -->
                                    <div class="col-md-6 form-group">
                                        <label for="behaviour_test_quiz_group_id">Quiz Group <span class="text-danger">*</span></label>
                                        <select class="form-control" id="behaviour_test_quiz_group_id" name="behaviour_test_quiz_group_id" required>
                                            <option value="">-- Select Quiz Group --</option>
                                            @foreach($quizGroups as $group)
                                                <option value="{{ $group->id }}" {{ old('behaviour_test_quiz_group_id') == $group->id ? 'selected' : '' }}>
                                                    {{ $group->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Amplitude Word Input -->
                                    <div class="col-md-6 form-group">
                                        <label for="amplitude_word">Amplitude Word</label>
                                        <input type="text" class="form-control" id="amplitude_word" name="amplitude_word" value="{{ old('amplitude_word') }}" placeholder="Enter Amplitude Word">
                                    </div>
                                </div>

                                <hr>

                                <!-- Answers Nested Repeater -->
                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">Answers & Actions</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="repeater">
                                            <div data-repeater-list="answers">
                                                <div data-repeater-item class="border rounded p-3 mb-3">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-7 form-group">
                                                            <label>Answer text <span class="text-danger">*</span></label>
                                                            <input type="text" name="answer" class="form-control" placeholder="Enter Answer" required>
                                                        </div>
                                                        <div class="col-md-3 form-group">
                                                            <label>Points <span class="text-muted">(0 - 1)</span></label>
                                                            <input type="number" name="points" class="form-control" placeholder="0.00" step="0.01" min="0" max="1" value="0" required>
                                                        </div>
                                                        <div class="col-md-2 text-right">
                                                            <button type="button" class="btn btn-danger btn-sm mt-3" data-repeater-delete>
                                                                <i class="fas fa-trash"></i> Delete Answer
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Nested Action Repeater -->
                                                    <div class="inner-repeater border-left border-info pl-3" style="border-width: 3px !important;">
                                                        <h6 class="text-info font-weight-bold">Actions</h6>
                                                        <div data-repeater-list="actions">
                                                            <div data-repeater-item class="row align-items-center mb-2">
                                                                <div class="col-md-9">
                                                                    <select name="behaviour_test_quiz_action_id" class="form-control" required disabled>
                                                                        <option value="">-- Select Action --</option>
                                                                        @foreach($actions as $action)
                                                                            <option value="{{ $action->id }}">{{ $action->title }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <button type="button" class="btn btn-danger btn-xs" data-repeater-delete>
                                                                        <i class="fas fa-times"></i> Remove
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-info btn-xs mt-1" data-repeater-create>
                                                            <i class="fas fa-plus"></i> Add Action
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm mt-2" data-repeater-create>
                                                <i class="fas fa-plus"></i> Add Answer
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Is Active & Is Multiple Choice Checkboxes -->
                                <div class="row mt-2">
                                    <div class="col-md-6 form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_active">Is Active</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_multiple_choice" name="is_multiple_choice" {{ old('is_multiple_choice', false) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_multiple_choice">Is Multiple Choice Question?</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <input type="submit" value="Save Question" class="btn btn-success float-right">
                        <a href="{{ route('behaviour-test-quiz-index') }}" class="btn btn-secondary float-right mr-2">Cancel</a>
                    </div>
                </div>
            </form>
        </section>
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.repeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: true,
                show: function() {
                    $(this).find('input, select, textarea').prop('disabled', false);
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                    if ($(this).parents('.inner-repeater').length > 0) {
                        return;
                    }
                    if (confirm('Are you sure you want to delete this answer? All selected actions for this answer will also be lost.')) {
                        $(this).slideUp(deleteElement);
                    }
                },
                repeaters: [{
                    selector: '.inner-repeater',
                    initEmpty: true, // Answers start with 0 actions, user adds actions if they prefer
                    show: function() {
                        $(this).find('input, select, textarea').prop('disabled', false);
                        $(this).slideDown();
                    },
                    hide: function(deleteElement) {
                        if (confirm('Are you sure you want to remove this action?')) {
                            $(this).slideUp(deleteElement);
                        }
                    }
                }]
            });

            $('#quizForm').on('submit', function(e) {
                // Disable all inputs inside hidden repeater items so they don't trigger HTML5 validation
                $(this).find('[data-repeater-item]:hidden').find('input, select, textarea').prop('disabled', true);

                var valid = true;
                
                // Validate Question Text
                var questionInput = $('#question');
                if (questionInput.val().trim() === '') {
                    valid = false;
                    questionInput.addClass('is-invalid');
                } else {
                    questionInput.removeClass('is-invalid');
                }

                // Validate Quiz Group
                var groupSelect = $('#behaviour_test_quiz_group_id');
                if (groupSelect.val() === '') {
                    valid = false;
                    groupSelect.addClass('is-invalid');
                } else {
                    groupSelect.removeClass('is-invalid');
                }

                // Validate Answer Texts
                $(this).find('input[name*="[answer]"]:not(:disabled)').each(function() {
                    if ($(this).val().trim() === '') {
                        valid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!valid) {
                    // Re-enable in case user needs to edit after validation failure
                    $(this).find('[data-repeater-item]:hidden').find('input, select, textarea').prop('disabled', false);
                    e.preventDefault();
                    alert('Please fill out all required fields (Question, Quiz Group, and Answer texts).');
                    return false;
                }

                var pointsValid = true;
                var totalPoints = 0;
                var isMultipleChoice = $('#is_multiple_choice').is(':checked');

                $(this).find('input[name*="[points]"]:not(:disabled)').each(function() {
                    var val = parseFloat($(this).val());
                    if (isNaN(val) || val < 0 || val > 1) {
                        pointsValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                        totalPoints += val;
                    }
                });
                if (!pointsValid) {
                    // Re-enable in case user needs to edit after validation failure
                    $(this).find('[data-repeater-item]:hidden').find('input, select, textarea').prop('disabled', false);
                    e.preventDefault();
                    alert('Points must be between 0 and 1 for each answer.');
                    return false;
                }

                if (isMultipleChoice && totalPoints > 1.00) {
                    // Re-enable in case user needs to edit after validation failure
                    $(this).find('[data-repeater-item]:hidden').find('input, select, textarea').prop('disabled', false);
                    e.preventDefault();
                    alert('For multiple choice questions, the sum of all answer points cannot be greater than 1 (current sum: ' + totalPoints.toFixed(2) + ').');
                    return false;
                }
            });
        });
    </script>
    @endsection
</x-admin-layout>
