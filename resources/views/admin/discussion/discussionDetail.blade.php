<x-admin-layout title="Create Discussion">
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Community</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Community</li>
                            <li class="breadcrumb-item active">Discussion</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <!-- Default box -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Discussion</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <h3>{{ $discussionDetails->topic }}</h3>
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-10 order-2 order-md-1">
                            <div class="row">
                                <div class="col-12">
                                    <h4>Recent Activity</h4>
                                    @foreach($discussionDetails['reply'] as $reply)
                                    @if(empty($reply))
                                    <div class="post">
                                        <p>
                                            No Reply.
                                        </p>
                                    </div>
                                    @else
                                    <div class="post">
                                        @foreach($reply['user'] as $user)
                                        <div class="user-block">
                                            @if(!empty($reply['user']['avatar']))
                                            <img class="img-circle img-bordered-sm" src="{{ url('/storage/'.$user['avatar']) }}" alt="user image">
                                            @else
                                            <img class="img-circle img-bordered-sm" src="{{ asset('adminAssets/dist/img/user1-128x128.jpg') }}" alt="user image">
                                            @endif
                                            <span class="username">
                                                <a href="#">{{ $user['name'] }}.</a>
                                            </span>
                                            <!-- <span class="description">Shared publicly - 7:45 PM today</span> -->
                                        </div>
                                        @endforeach
                                        <!-- /.user-block -->
                                        <p>
                                            {{ $reply['discussion_reply'] }}
                                        </p>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

        </section>
        <!-- /.content -->
    </div>
</x-admin-layout>
