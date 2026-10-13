<x-admin-layout title="User Milestone">
    @section('styles')
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
                        <h1>User Milestone</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">User Milestone</li>
                            <li class="breadcrumb-item active">Create</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <form id="quickForm" action="{{ url('admin/task-milestone-create') }}" method="post">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add milestone</h3>
                                <div class="card-tools">
                                    <a href="{{ url('admin/task-milestone-index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Return to List
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                                {{-- Milestone Type --}}
                                <div class="form-group">
                                    <label for="milestone_type">Milestone Type <span style="color: #ff5252;">*</span></label>
                                    <select id="milestone_type" name="milestone_type" class="form-control" required>
                                        <option value="">Select Type</option>
                                        <option value="lessons">Lessons</option>
                                        <option value="log-meals">Log Meals</option>
                                        <option value="walks">Walks</option>
                                        <option value="workouts">Workouts</option>
                                        <option value="drink-water">Hydration (Drink Water)</option>
                                        <option value="streaks">Streak</option>
                                        <option value="add-photos">Add Photos</option>
                                        <option value="community-posts">Interact with Community</option>
                                        <option value="protiens">Consume Protiens</option>
                                        <option value="challenge">Challenge</option>
                                    </select>
                                </div>

                                {{-- Milestone Title --}}
                                <div class="form-group">
                                    <label for="milestone_title">Milestone Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="milestone_title" name="milestone_title" class="form-control" value="{{ old('milestone_title') }}" required>
                                </div>

                                {{-- Milestone Description --}}
                                <div class="form-group">
                                    <label for="milestone_description">Milestone Description <span style="color: #ff5252;">*</span></label>
                                    <textarea id="milestone_description" name="milestone_description" class="form-control" rows="3" required>{{ old('milestone_description') }}</textarea>
                                </div>

                                {{-- Milestone Count --}}
                                <div class="form-group">
                                    <label for="milestone_count">Milestone Count <span style="color: #ff5252;">*</span></label>
                                    <input type="number" id="milestone_count" name="milestone_count" class="form-control" value="{{ old('milestone_count') }}" min="1" required>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
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
                    milestone_type: { required: true },
                    milestone_title: { required: true },
                    milestone_description: { required: true },
                    milestone_count: { required: true }
                },
                messages: {
                    milestone_type:{required:'please enter milestone type'},
                    milestone_title: { required: "Please enter milestone title" },
                    milestone_description: { required: "Please enter milestone description " },
                    milestone_count: { required: "Please enter milestone count" }
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
        });
    </script>
    @endsection
</x-admin-layout>
