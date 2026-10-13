<x-admin-layout title="User Level">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">

    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>User Level</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">User Level</li>
                            <li class="breadcrumb-item active">Index</li>
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
            <form id="quickForm" action="{{ url('admin/user-level-create') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Add New Level</h3>
                        <div class="card-tools">
                            <a class="btn btn-secondary" href="{{ url('admin/user-level-index') }}">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Row: Level, Title, Improvement --}}
                        <div class="row">
                            {{-- Level --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="level">Level <span style="color: #ff5252;">*</span></label>
                                    <input type="number" id="level" name="level" class="form-control"
                                        value="{{ $nextLevel }}" readonly>
                                </div>
                            </div>

                            {{-- Title --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="title">Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control"
                                        value="{{ old('title') }}" required>
                                </div>
                            </div>

                            {{-- Improvement --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="improvement">Improvement (%) <span style="color: #ff5252;">*</span></label>
                                    <input type="number" id="improvement" name="improvement" class="form-control"
                                        min="0" max="100" step="0.01" value="{{ old('improvement') }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Row: Description --}}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Description <span style="color: #ff5252;">*</span></label>
                                    <textarea id="description" name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Video Section --}}
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Video</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="video_title">Video Title</label>
                                    <input type="text" id="video_title" name="video_title" class="form-control" value="{{ old('video_title') }}">
                                </div>

                                <div class="form-group">
                                    <label for="video_description">Video Description</label>
                                    <textarea id="video_description" name="video_description" class="form-control">{{ old('video_description') }}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="video_link">Video URL</label>
                                    <input type="url" id="video_link" name="video_link" class="form-control"
                                        placeholder="https://example.com/video" value="{{ old('video_link') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Task Repeater --}}
                        <div class="card card-info mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Tasks</h3>
                            </div>
                            <div class="card-body">
                                <div class="repeater">
                                    <div data-repeater-list="tasks">
                                        <div data-repeater-item>
                                            <div class="row">
                                                {{-- Task Type --}}
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label for="task_type">Task Type</label>
                                                      <select name="task_type" class="form-control dynamic-select" required>
                                                            <option value="">Select Task Type</option>
                                                            <option value="lessons">Lessons</option>
                                                            <option value="workouts">Workouts</option>
                                                            <option value="drink-water">Drink Water</option>
                                                            <option value="streaks">Streaks</option>
                                                            <option value="add-photos">Add Photos</option>
                                                            <option value="feedback">feedback</option>
                                                            <option value="community-posts">Interaction with community posts</option>
                                                            <option value="walks">Walks</option>
                                                            <option value="log-meals">Log meals</option>
                                                            <option value="protiens">Protiens</option>
                                                            <option value="challenge">Challenge</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                {{-- Total Count --}}
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="total_count">Total Count</label>
                                                        <input type="number" name="total_count" class="form-control" required>
                                                    </div>
                                                </div>

                                                {{-- Duration --}}
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label for="duration">Duration</label>
                                                        <input type="time" step="1" name="duration" class="form-control" required>
                                                    </div>
                                                </div>

                                                {{-- Remove Button --}}
                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>Remove</button>
                                                </div>
                                            </div>
                                            <hr>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm mt-2" data-repeater-create>Add Task</button>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-success float-right">Save Level</button>
                        </div>
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
                improvement: { required: true },
                video_title: { required: true },
                video_description: { required: true },
                video_link: { required: true },
                description: { required: true }
            },
            messages: {
                title: { required: "Please enter level title" },
                video_title: { required: "Please enter vidio title" },
                video_description: { required: "Please enter video description" },
                video_link: { required: "Please enter video link" },
                improvement: { required: "Please enter improvment" },
                description: { required: "Please enter description for this level" }
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
            show: function() {
                $(this).slideDown();
                updateDisabledOptions();
            },
            hide: function(deleteElement) {
                if (confirm('Are you sure you want to delete this element?')) {
                    $(this).slideUp(deleteElement);
                }
                let value = $(this).find('.dynamic-select').val();

                if (value) {
                    // Re-enable the deleted option in all selects
                    $('.dynamic-select').find(`option[value="${value}"]`).prop('disabled', false);
                }
            }
        });
        $(document).on('change', '.dynamic-select', function () {
            updateDisabledOptions();
        });

        function updateDisabledOptions() {
            let selectedValues = [];

            // Collect all selected values
            $('.dynamic-select').each(function () {
                let val = $(this).val();
                if (val) selectedValues.push(val);
            });

            // Disable selected values in all dropdowns except their own
            $('.dynamic-select').each(function () {
                let currentVal = $(this).val();
                $(this).find('option').prop('disabled', false);
                selectedValues.forEach(val => {
                    if (val !== currentVal) {
                        $(this).find(`option[value="${val}"]`).prop('disabled', true);
                    }
                });
            });
        }
    });
    </script>
    @endsection
</x-admin-layout>
