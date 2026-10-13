<x-admin-layout title="Edit Points Result">
    @section('styles')
    <style>
        .range-repeater-item {
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .dark-mode .range-repeater-item {
            background-color: #343a40;
        }
        .range-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 4px;
        }
    </style>
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-lg-6">
                        <h1>Edit Points Result</h1>
                    </div>
                    <div class="col-lg-6">
                        <ol class="breadcrumb float-lg-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Edit Points Result</li>
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

            <form id="pointsResultForm" action="{{ url('admin/update-behaviour-test-points-result/'.$pointsResult->slug.'/'.$pointsResult->id) }}" method="post">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Points Result Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="behaviour_test_quiz_group_id">Quiz Group <span class="text-danger">*</span></label>
                                    <select class="form-control" id="behaviour_test_quiz_group_id" name="behaviour_test_quiz_group_id" required>
                                        <option value="">-- Select Quiz Group --</option>
                                        @foreach($quizGroups as $group)
                                            <option value="{{ $group->id }}" {{ old('behaviour_test_quiz_group_id', $pointsResult->behaviour_test_quiz_group_id) == $group->id ? 'selected' : '' }}>
                                                {{ $group->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <hr>

                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">Result Ranges</h3>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">
                                            Define consecutive percentage ranges from 0% to 100%. Each new range must start at the previous range end + 1.
                                        </p>
                                        <div class="repeater">
                                            <div data-repeater-list="ranges">
                                                @php
                                                    $existingRanges = old('ranges', $pointsResultData['ranges'] ?? []);
                                                    if (empty($existingRanges)) {
                                                        $existingRanges = [['range_start' => 0, 'range_end' => '', 'result_text' => '']];
                                                    }
                                                @endphp
                                                @foreach($existingRanges as $range)
                                                <div data-repeater-item class="border rounded p-3 mb-3 range-repeater-item">
                                                    <div class="row align-items-end">
                                                        <div class="col-md-3 form-group">
                                                            <label>Start Range (%) <span class="text-danger">*</span></label>
                                                            <input type="number" name="range_start" class="form-control range-start-input" min="0" max="100" step="1" value="{{ $range['range_start'] ?? 0 }}" required>
                                                        </div>
                                                        <div class="col-md-3 form-group">
                                                            <label>End Range (%) <span class="text-danger">*</span></label>
                                                            <input type="number" name="range_end" class="form-control range-end-input" min="0" max="100" step="1" value="{{ $range['range_end'] ?? '' }}" placeholder="e.g. 49" required>
                                                        </div>
                                                        <div class="col-md-4 form-group">
                                                            <label>Result Text <span class="text-danger">*</span></label>
                                                            <input type="text" name="result_text" class="form-control result-text-input" value="{{ $range['result_text'] ?? '' }}" placeholder="e.g. Needs improvement" required>
                                                        </div>
                                                        <div class="col-md-2 text-right">
                                                            <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="range-item-error range-error"></div>
                                                </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-success btn-sm mt-2" data-repeater-create>
                                                <i class="fas fa-plus"></i> Add Range
                                            </button>
                                        </div>
                                        <div id="rangesGlobalError" class="range-error mt-2"></div>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group mt-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ old('is_active', $pointsResult->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Is Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <input type="submit" value="Update Points Result" class="btn btn-success float-right">
                        <a href="{{ route('behaviour-test-points-result-index') }}" class="btn btn-secondary float-right mr-2">Cancel</a>
                    </div>
                </div>
            </form>
        </section>
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    @include('admin.behaviour_test_points_result.partials.range_repeater_scripts')
    @endsection
</x-admin-layout>
