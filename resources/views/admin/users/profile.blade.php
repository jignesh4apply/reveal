<x-admin-layout title="User Profile">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        .invalid-feedback {
            margin-left: 170px;
        }

        .morphology-report-cell {
            max-width: 520px;
            white-space: normal;
            max-height: 640px;
            overflow-y: auto;
        }

        .morphology-report-rendered {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .morphology-report-section {
            border: 1px solid #dfe6ee;
            border-radius: 10px;
            background: #ffffff;
            padding: 12px 14px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        .morphology-report-key {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4c5c72;
            margin-bottom: 8px;
        }

        .morphology-report-values {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .morphology-report-card {
            border: 1px solid #dfe6ee;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            background: #ffffff;
        }

        .morphology-report-card-header {
            background: #ffffff;
            border-bottom: 1px solid #e5eaf0;
        }

        .morphology-report-toggle {
            color: #1f2937 !important;
            font-weight: 700;
            text-decoration: none !important;
            padding: 12px 14px;
            display: block;
            width: 100%;
        }

        .morphology-report-toggle:hover,
        .morphology-report-toggle:focus {
            color: #1f2937 !important;
            text-decoration: none !important;
        }

        .morphology-report-toggle .fa-chevron-down {
            transition: transform 0.2s ease;
        }

        .morphology-report-toggle[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }

        .morphology-report-card-body {
            color: #1f2937;
            padding: 12px 14px;
        }

        .morphology-report-value {
            font-size: 0.92rem;
            color: #1f2937;
            line-height: 1.35;
            word-break: break-word;
        }

        .morphology-report-list {
            margin: 0;
            padding-left: 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            color: #1f2937;
        }

        .morphology-report-key {
            color: #1f2937;
        }

        .morphology-report-table {
            margin-top: 8px;
            font-size: 0.78rem;
            color: #1f2937;
        }

        .morphology-report-table th,
        .morphology-report-table td {
            vertical-align: middle;
            color: #1f2937;
        }

        .morphology-report-cell,
        .morphology-report-cell * {
            color: #1f2937;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Profile</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">User Profile</li>
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
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">User Profile</h3>
                            <!-- <div class="card-tools">
                                <a type="button" class="btn btn-secondary" href="{{ url('admin/cms-index') }}">
                                    <i class="fas fa-arrow-left"></i>
                                    Return to List
                                </a>
                            </div> -->
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <!-- Profile Image -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                @if($user->avatar)
                                <img class="profile-user-img img-fluid img-circle" src="{{ asset('/storage/'.$user->avatar) }}" id="user_img" alt="User profile picture">
                                @else
                                <img class="profile-user-img img-fluid img-circle" src="{{ asset('adminAssets/dist/img/user4-128x128.jpg') }}" id="user_img" alt="User profile picture">
                                @endif
                            </div>

                            <h3 class="profile-username text-center">{{ $user->name }}</h3>

                            <!-- <p class="text-muted text-center">Software Engineer</p> -->

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Name</b> <a class="float-right">{{ $user->name }}</a>
                                </li>
                                <li class="list-group-item">
                                    <b>Email</b> <a class="float-right">{{ $user->email }}</a>
                                </li>
                                <li class="list-group-item">
                                    <b>Mobile</b> <a class="float-right">{{ $user->mobile }}</a>
                                </li>
                            </ul>

                            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-block"><b>Dashboard</b></a>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                    <!-- /.card -->
                </div>
                <!-- /.col -->
                <div class="col-md-9">
                    <div class="card card-primary card-outline">
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="active tab-pane" id="settings">
                                    <form id="user-profile" class="form-horizontal" action="" method="post" enctype="multipart/form-data">
                                        {{ csrf_field() }}
                                        <div class="form-group row">
                                            <label for="inputName" class="col-sm-2 col-form-label">Name <span style="color: #ff5252;">*</span></label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="name" id="name" placeholder="Name" value="{{ $user->name }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input type="email" class="form-control" name="email" id="email" value="{{ $user->email }}" placeholder="Email" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputName2" class="col-sm-2 col-form-label">Mobile</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="mobile" id="mobile" placeholder="Mobile" value="{{ $user->mobile }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputSkills" class="col-sm-2 col-form-label">Profile Picture</label>
                                            <div class="col-sm-10">
                                                <input type="file" class="form-control" name="avatar" id="avatar" onchange="show(this)">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="Address" class="col-sm-2 col-form-label">Address</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="address" id="address" placeholder="Address" value="{{ $user->address }}">
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="city" id="city" placeholder="City" value="{{ $user->city }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="city" class="col-sm-2 col-form-label"></label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="country" id="country" placeholder="Country" value="{{ $user->country }}">
                                            </div>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" name="postal_code" id="postal_code" placeholder="Postal Code" value="{{ $user->postal_code }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="submit" class="btn btn-danger float-right">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.tab-pane -->
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
                <div class="col-md-12">
                    <div class="card card-primary card-outline shadow-sm">
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="active tab-pane" id="reset-password">
                                    <h4 class="mb-4">Reset User Password</h4>
                                    <form action="{{ route('admin.userResetPassword', $user->id) }}" method="POST">
                                        @csrf
                                        <div class="form-group row align-items-center">
                                            <label for="generatedPassword" class="col-sm-3 col-form-label fw-bold">New Password</label>
                                            <div class="col-sm-7">
                                                <div class="input-group">
                                                    <input type="text" id="generatedPassword" name="password" class="form-control" >
                                                    <button type="button" class="btn btn-secondary" onclick="generatePassword()" style="margin-left: 22px;">Generate</button>
                                                    <button type="button" class="btn btn-primary" onclick="copyPassword()" style="margin-left: 22px;">Copy</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row mt-4">
                                            <div class="col-sm-10 offset-sm-3">
                                                <button type="submit" class="btn btn-success px-4">Reset Password</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card card-primary card-outline shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">Morphology Reports</h3>
                        </div>
                        <div class="card-body">
                            @if($morphologyReports->isEmpty())
                                <div class="alert alert-info mb-0">No morphology reports found for this user.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Session ID</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Generated At</th>
                                                <th>Report</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($morphologyReports as $morphologyReport)
                                                @php
                                                    $reportData = is_array($morphologyReport->report_json) ? $morphologyReport->report_json : json_decode($morphologyReport->report_json, true);
                                                    $reportData = is_array($reportData) ? $reportData : [];

                                                    $renderMorphologyReport = function ($value, $depth = 0, $accordionGroupId = null) use (&$renderMorphologyReport) {
                                                        if (is_array($value)) {
                                                            $isList = array_is_list($value);

                                                            if ($isList && count($value) > 0 && is_array($value[0])) {
                                                                $headers = array_keys($value[0]);
                                                                $table = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 morphology-report-table"><thead><tr>';
                                                                foreach ($headers as $header) {
                                                                    $table .= '<th>' . e(ucwords(str_replace(['_', '-'], ' ', $header))) . '</th>';
                                                                }
                                                                $table .= '</tr></thead><tbody>';

                                                                foreach ($value as $row) {
                                                                    $table .= '<tr>';
                                                                    foreach ($headers as $header) {
                                                                        $cell = $row[$header] ?? '';
                                                                        if (is_array($cell)) {
                                                                            $cellHtml = $renderMorphologyReport($cell, $depth + 1);
                                                                        } else {
                                                                            $cellHtml = e($cell);
                                                                        }
                                                                        $table .= '<td>' . $cellHtml . '</td>';
                                                                    }
                                                                    $table .= '</tr>';
                                                                }

                                                                $table .= '</tbody></table></div>';
                                                                return $table;
                                                            }

                                                            if ($isList && count($value) > 0 && !is_array($value[0])) {
                                                                $list = '<ul class="morphology-report-list">';
                                                                foreach ($value as $item) {
                                                                    $list .= '<li>' . $renderMorphologyReport($item, $depth + 1) . '</li>';
                                                                }
                                                                $list .= '</ul>';
                                                                return $list;
                                                            }

                                                            if ($depth === 0) {
                                                                $accordionGroupId = $accordionGroupId ?? 'morphology-accordion-' . uniqid();
                                                                $html = '<div class="accordion morphology-report-accordion" id="' . e($accordionGroupId) . '">';

                                                                foreach ($value as $key => $item) {
                                                                    $keyTitle = ucwords(str_replace(['_', '-'], ' ', $key));
                                                                    $collapseId = 'morphology-collapse-' . uniqid();

                                                                    $html .= '<div class="card morphology-report-card">';
                                                                    $html .= '<div class="card-header p-0 border-0 morphology-report-card-header" id="heading-' . e($collapseId) . '">';
                                                                    $html .= '<h2 class="mb-0">';
                                                                    $html .= '<button class="btn btn-link btn-block text-left collapsed morphology-report-toggle" type="button" data-toggle="collapse" data-target="#' . e($collapseId) . '" aria-expanded="false" aria-controls="' . e($collapseId) . '">';
                                                                    $html .= '<span class="d-flex justify-content-between align-items-center">';
                                                                    $html .= '<span>' . e($keyTitle) . '</span>';
                                                                    $html .= '<span class="fas fa-chevron-down"></span>';
                                                                    $html .= '</span>';
                                                                    $html .= '</button>';
                                                                    $html .= '</h2>';
                                                                    $html .= '</div>';
                                                                    $html .= '<div id="' . e($collapseId) . '" class="collapse" data-parent="#' . e($accordionGroupId) . '" aria-labelledby="heading-' . e($collapseId) . '">';
                                                                    $html .= '<div class="card-body morphology-report-card-body">';

                                                                    if (is_array($item)) {
                                                                        $html .= $renderMorphologyReport($item, $depth + 1);
                                                                    } else {
                                                                        $html .= '<div class="morphology-report-value">' . e($item) . '</div>';
                                                                    }

                                                                    $html .= '</div>';
                                                                    $html .= '</div>';
                                                                    $html .= '</div>';
                                                                }

                                                                $html .= '</div>';
                                                                return $html;
                                                            }

                                                            $html = '<div class="morphology-report-values">';
                                                            foreach ($value as $key => $item) {
                                                                $keyTitle = ucwords(str_replace(['_', '-'], ' ', $key));
                                                                $html .= '<div class="morphology-report-section">';
                                                                $html .= '<span class="morphology-report-key">' . e($keyTitle) . '</span>';

                                                                if (is_array($item)) {
                                                                    $html .= $renderMorphologyReport($item, $depth + 1);
                                                                } else {
                                                                    $html .= '<div class="morphology-report-value">' . e($item) . '</div>';
                                                                }

                                                                $html .= '</div>';
                                                            }
                                                            $html .= '</div>';

                                                            return $html;
                                                        }

                                                        return '<div class="morphology-report-value">' . e($value) . '</div>';
                                                    };
                                                @endphp
                                                <tr>
                                                    <td>{{ $morphologyReport->session_id }}</td>
                                                    <td>{{ $morphologyReport->type }}</td>
                                                    <td>
                                                        @php
                                                            $statusClass = match($morphologyReport->generation_status) {
                                                                'completed' => 'badge-success',
                                                                'processing' => 'badge-warning',
                                                                'failed' => 'badge-danger',
                                                                default => 'badge-secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $statusClass }}">{{ ucfirst($morphologyReport->generation_status) }}</span>
                                                    </td>
                                                    <td>{{ $morphologyReport->report_generated_at ?? $morphologyReport->created_at }}</td>
                                                    <td class="morphology-report-cell">
                                                        <div class="morphology-report-rendered">
                                                            {!! $renderMorphologyReport($reportData) !!}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('admin.delete-user-morphology-report', $morphologyReport->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this morphology report?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                <div class="col-md-12">
                    <div class="card card-primary card-outline shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">Behaviour Test Reports</h3>
                        </div>
                        <div class="card-body">
                            @if($behaviourTestReports->isEmpty())
                                <div class="alert alert-info mb-0">No behaviour test reports found for this user.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Session ID</th>
                                                <th>Status</th>
                                                <th>Generated At</th>
                                                <th>Report</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($behaviourTestReports as $behaviourTestReport)
                                                @php
                                                    $reportData = is_array($behaviourTestReport->report_json) ? $behaviourTestReport->report_json : json_decode($behaviourTestReport->report_json, true);
                                                    $reportData = is_array($reportData) ? $reportData : [];

                                                    $renderBehaviourTestReport = function ($value, $depth = 0, $accordionGroupId = null) use (&$renderBehaviourTestReport) {
                                                        if (is_array($value)) {
                                                            $isList = array_is_list($value);

                                                            if ($isList && count($value) > 0 && is_array($value[0])) {
                                                                $headers = array_keys($value[0]);
                                                                $table = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 morphology-report-table" style="background-color: #fff;"><thead><tr>';
                                                                foreach ($headers as $header) {
                                                                    $table .= '<th>' . e(ucwords(str_replace(['_', '-'], ' ', $header))) . '</th>';
                                                                }
                                                                $table .= '</tr></thead><tbody>';

                                                                foreach ($value as $row) {
                                                                    $table .= '<tr>';
                                                                    foreach ($headers as $header) {
                                                                        $cell = $row[$header] ?? '';
                                                                        if (is_array($cell)) {
                                                                            $cellHtml = $renderBehaviourTestReport($cell, $depth + 1);
                                                                        } else {
                                                                            $cellHtml = e($cell);
                                                                        }
                                                                        $table .= '<td>' . $cellHtml . '</td>';
                                                                    }
                                                                    $table .= '</tr>';
                                                                }

                                                                $table .= '</tbody></table></div>';
                                                                return $table;
                                                            }

                                                            if ($isList && count($value) > 0 && !is_array($value[0])) {
                                                                $list = '<ul class="morphology-report-list">';
                                                                foreach ($value as $item) {
                                                                    $list .= '<li>' . $renderBehaviourTestReport($item, $depth + 1) . '</li>';
                                                                }
                                                                $list .= '</ul>';
                                                                return $list;
                                                            }

                                                            if ($depth === 0) {
                                                                $accordionGroupId = $accordionGroupId ?? 'behaviour-accordion-' . uniqid();
                                                                $html = '<div class="accordion morphology-report-accordion" id="' . e($accordionGroupId) . '">';

                                                                foreach ($value as $key => $item) {
                                                                    $keyTitle = ucwords(str_replace(['_', '-'], ' ', $key));
                                                                    $collapseId = 'behaviour-collapse-' . uniqid();

                                                                    $html .= '<div class="card morphology-report-card">';
                                                                    $html .= '<div class="card-header p-0 border-0 morphology-report-card-header" id="heading-' . e($collapseId) . '">';
                                                                    $html .= '<h2 class="mb-0">';
                                                                    $html .= '<button class="btn btn-link btn-block text-left collapsed morphology-report-toggle" type="button" data-toggle="collapse" data-target="#' . e($collapseId) . '" aria-expanded="false" aria-controls="' . e($collapseId) . '">';
                                                                    $html .= '<span class="d-flex justify-content-between align-items-center">';
                                                                    $html .= '<span>' . e($keyTitle) . '</span>';
                                                                    $html .= '<span class="fas fa-chevron-down"></span>';
                                                                    $html .= '</span>';
                                                                    $html .= '</button>';
                                                                    $html .= '</h2>';
                                                                    $html .= '</div>';
                                                                    $html .= '<div id="' . e($collapseId) . '" class="collapse" data-parent="#' . e($accordionGroupId) . '" aria-labelledby="heading-' . e($collapseId) . '">';
                                                                    $html .= '<div class="card-body morphology-report-card-body">';

                                                                    if (is_array($item)) {
                                                                        $html .= $renderBehaviourTestReport($item, $depth + 1);
                                                                    } else {
                                                                        $html .= '<div class="morphology-report-value">' . e($item) . '</div>';
                                                                    }

                                                                    $html .= '</div>';
                                                                    $html .= '</div>';
                                                                    $html .= '</div>';
                                                                }

                                                                $html .= '</div>';
                                                                return $html;
                                                            }

                                                            $html = '<div class="morphology-report-values">';
                                                            foreach ($value as $key => $item) {
                                                                $keyTitle = ucwords(str_replace(['_', '-'], ' ', $key));
                                                                $html .= '<div class="morphology-report-section">';
                                                                $html .= '<span class="morphology-report-key">' . e($keyTitle) . '</span>';

                                                                if (is_array($item)) {
                                                                    $html .= $renderBehaviourTestReport($item, $depth + 1);
                                                                } else {
                                                                    $html .= '<div class="morphology-report-value">' . e($item) . '</div>';
                                                                }

                                                                $html .= '</div>';
                                                            }
                                                            $html .= '</div>';

                                                            return $html;
                                                        }

                                                        return '<div class="morphology-report-value">' . e($value) . '</div>';
                                                    };
                                                @endphp
                                                <tr>
                                                    <td>{{ $behaviourTestReport->session_id }}</td>
                                                    <td>
                                                        @php
                                                            $statusClass = match($behaviourTestReport->status) {
                                                                'completed' => 'badge-success',
                                                                'processing' => 'badge-warning',
                                                                'failed' => 'badge-danger',
                                                                default => 'badge-secondary',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $statusClass }}">{{ ucfirst($behaviourTestReport->status) }}</span>
                                                    </td>
                                                    <td>{{ $behaviourTestReport->report_generated_at ?? $behaviourTestReport->created_at }}</td>
                                                    <td class="morphology-report-cell">
                                                        <div class="morphology-report-rendered">
                                                            {!! $renderBehaviourTestReport($reportData) !!}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('admin.delete-user-behaviour-test-report', $behaviourTestReport->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this behaviour test report?');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.row -->
        </section>
        <!-- /.content -->
    </div>
    @section('scripts')
    <SCRIPT type="text/javascript">
        function show(input) {
            debugger;
            var validExtensions = ['jpg', 'png', 'jpeg']; //array of valid extensions
            var fileName = input.files[0].name;
            var fileNameExt = fileName.substr(fileName.lastIndexOf('.') + 1);
            if ($.inArray(fileNameExt, validExtensions) == -1) {
                input.type = ''
                input.type = 'file'
                $('#user_img').attr('src', "");

                Swal.fire({
                    title: "Invalid Extension!",
                    text: "Only these file types are accepted : " + validExtensions.join(', '),
                    icon: "error"
                });

                alert("Only these file types are accepted : " + validExtensions.join(', '));
            } else {
                if (input.files && input.files[0]) {
                    var filerdr = new FileReader();
                    filerdr.onload = function(e) {
                        $('#user_img').attr('src', e.target.result);
                    }
                    filerdr.readAsDataURL(input.files[0]);
                }
            }
        }
    </SCRIPT>
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

    <script>
        var phone_input = document.getElementById("mobile");

        phone_input.addEventListener('input', () => {
            phone_input.setCustomValidity('');
            phone_input.checkValidity();
        });

        phone_input.addEventListener('invalid', () => {
            if (phone_input.value === '') {
                phone_input.setCustomValidity('Enter phone number!');
            } else {
                phone_input.setCustomValidity('Enter phone number in this format: 123-456-7890');
            }
        });
    </script>

    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    <script>
        $(function() {
            $('#user-profile').validate({
                rules: {
                    name: {
                        required: true,
                    }
                },
                messages: {
                    name: {
                        required: "Please enter your name",
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
        function generatePassword() {
            let length = 12;
            let charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()";
            let password = "";
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            document.getElementById("generatedPassword").value = password;
        }

        function copyPassword() {
            let passwordField = document.getElementById("generatedPassword");
            passwordField.select();
            passwordField.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Password copied to clipboard!");
        }
    </script>
    @endsection
</x-admin-layout>
