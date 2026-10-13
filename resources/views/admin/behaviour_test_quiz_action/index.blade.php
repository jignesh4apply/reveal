<x-admin-layout title="Actions">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Actions</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Actions</li>
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
                                <h3 class="card-title">Actions</h3>
                                <div class="card-tools">
                                    <a type="button" href="{{ route('create-behaviour-test-quiz-action') }}" class="btn btn-secondary">
                                        <i class="nav-icon fas fa-plus"></i>
                                        Add Action
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="customTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Description</th>
                                            <th>Tag</th>
                                            <th>Trigger Email</th>
                                            <th>Is Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($actions as $key => $action)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $action['title'] }}</td>
                                            <td>{{ $action['slug'] }}</td>
                                            <td>{{ Str::limit($action['description'], 50) ?? 'N/A' }}</td>
                                            <td>{{ $action['tag'] ?? 'N/A' }}</td>
                                            <td>
                                                @if($action['trigger_email'])
                                                <span class="badge badge-success">Yes</span>
                                                @else
                                                <span class="badge badge-danger">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($action['is_active'])
                                                <span class="badge badge-success">Active</span>
                                                @else
                                                <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ url('admin/update-behaviour-test-quiz-action/'.$action['slug'] .'/'. $action['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                                <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="behaviour-test-quiz-action" slug="{{ $action['slug'] }}" moduleId="{{ $action['id'] }}" message="Action Deleted Successfully"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Title</th>
                                            <th>Slug</th>
                                            <th>Description</th>
                                            <th>Tag</th>
                                            <th>Trigger Email</th>
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
