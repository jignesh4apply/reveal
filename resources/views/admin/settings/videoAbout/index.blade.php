<x-admin-layout title="List of Video About">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Settings</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item active">Settings</li>
                            <li class="breadcrumb-item active">Video About</li>
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
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">List of Video About</h3>
                            <div class="card-tools">
                                <a type="button" href="{{ url('admin/create-video-about') }}" class="btn btn-secondary">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Video About
                                </a>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="customTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Screen Name</th>
                                        <th>Slug</th>
                                        <th>Video Link</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($videoAboutData as $key => $videoAbout)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $videoAbout['screen_name'] }}</td>
                                        <td>{{ $videoAbout['slug'] }}</td>
                                        <td>{{ $videoAbout['video_link'] }}</td>
                                        <td>
                                            <!-- <a href="" class="btn btn-success"><i class="fas fa-eye"></i></a> -->
                                            <a href="{{ url('admin/update-video-about/'.$videoAbout['slug'] .'/'. $videoAbout['id']) }}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                            <!-- <a href="{{ url('admin/delete/'.$videoAbout['slug'] .'/'. $videoAbout['id']) }}" class="btn btn-danger"><i class="fas fa-trash"></i></a> -->
                                            <a href="javascript:void(0);" class="btn btn-danger confirmDelete" module="video-about" slug="{{ $videoAbout['slug'] }}" moduleId="{{ $videoAbout['id'] }}" message="Deleted Successfully"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Screen Name</th>
                                        <th>Slug</th>
                                        <th>Video Link</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endsection
</x-admin-layout>
