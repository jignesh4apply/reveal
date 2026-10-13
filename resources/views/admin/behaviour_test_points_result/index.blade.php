<x-admin-layout title="Points Result">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Points Result</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Points Result</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Points Result</h3>
                                <div class="card-tools">
                                    <a type="button" href="{{ route('create-behaviour-test-points-result') }}" class="btn btn-secondary">
                                        <i class="nav-icon fas fa-plus"></i>
                                        Add Points Result
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="customTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Quiz Group</th>
                                            <th>Result Ranges</th>
                                            <th>Is Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pointsResults as $key => $pointsResult)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $pointsResult['group']['title'] ?? 'N/A' }}</td>
                                            <td>
                                                @if(!empty($pointsResult['ranges']))
                                                    @foreach($pointsResult['ranges'] as $range)
                                                        <div>{{ $range['range_start'] }}% – {{ $range['range_end'] }}% → {{ $range['result_text'] }}</div>
                                                    @endforeach
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($pointsResult['is_active'])
                                                <span class="badge badge-success">Active</span>
                                                @else
                                                <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ url('admin/update-behaviour-test-points-result/'.$pointsResult['slug'].'/'.$pointsResult['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="behaviour-test-points-result" slug="{{ $pointsResult['slug'] }}" moduleId="{{ $pointsResult['id'] }}" message="Points Result Deleted Successfully"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Quiz Group</th>
                                            <th>Result Ranges</th>
                                            <th>Is Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
