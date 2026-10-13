<x-home-layout title="Community">
    @section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        /* theme-classes */
        .back-black {
            background-color: rgb(11, 35, 53);
        }

        .white-b {
            background-color: rgb(158, 186, 208);
        }

        .bg-1 {
            background-color: #24303F;
        }

        .i-bg {
            background-color: #122741c0;
        }

        .wht {
            color: white;
        }

        .gry {
            color: gray;
        }

        /* main-box */
        .flx-box {
            min-width: 100%;
            height: 100vh;
        }

        /* main-navbar */
        .nbar {
            height: 10%;

            ::placeholder {
                color: gray;
            }

            i {
                height: 45px;
                width: 45px;
                background-color: #152e4ea9;
                border-radius: 50%;
            }
        }

        .profile-pic {
            height: 45px;
            width: 45px;
            border-radius: 50%;
        }

        .dropdown-menu li a {
            color: white;
        }

        .dropdown-menu li a:hover {
            background-color: #1c3554;
            color: rgb(255, 255, 255);

        }
    </style>
    @endsection
    <!-- Breadcrumb Section Begin -->
    <div class="nbar w-100 d-flex align-items-center bg-1 header-top-bar">
        <div class="nbar-l  w-25 h-75 d-flex align-items-center">
            <i class="fas fa-bars cancle-btn wht  text-center align-content-center"></i>
            <input type="hidden" class="form-control bg-transparent text-light h-100 border-0" placeholder="Search here something..." value="search here..">
        </div>
        <div class="nbar-r h-75 w-75 wht d-flex justify-content-end align-items-center gap-3 ">
            <div class="profile mw-25 h-75 d-flex align-items-center">
                <div class="profile-namer d-none d-md-flex gap-2 me-2">
                    <span class="d-block fs-6">{{ Auth::guard('user')->user()->email }} <br> </span>
                    <!-- <span class="text-secondary">WEB <br> DEVELOPER</span> -->
                </div>
                <div class="profile-pic rounded-circle p-0 me-2">
                    <img src="https://i.pinimg.com/736x/5c/91/7a/5c917a46cdce453fe8c3d11b0d5618db.jpg" alt="" class="h-100 w-100 img-fluid rounded-circle m-0 p-0">
                </div>
                <div class="dropdown">
                    <button class="btn text-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: transparent; border: none; box-shadow: none; width: 10%; height:10%"> </button>
                    <ul class="dropdown-menu bg-dark text-primary">
                        <li><a class="dropdown-item" href="{{ route('user-profile') }}">Profile</a></li>
                        <li><a class="dropdown-item" href="{{ route('community') }}">Community</a></li>
                        <!-- <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#exampleModal">Log-Out</a></li> -->
                        <li><a href="{{ route('user-logout') }}" class="dropdown-item">Log-Out</a></li>
                    </ul>
                </div>
            </div>
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-dark">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Log Out</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-dark d-flex flex-column gap-3">
                            <input type="text" class="form-control bg-dark border text-light" value="nayan@gmail.com">
                            <input type="password" class="form-control bg-dark text-light" value="123456543">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                <label class="form-check-label" for="flexCheckDefault">
                                    Remember Login Info
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer bg-dark">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancle</button>
                            <button type="button" class="btn btn-primary">Log-Out</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="easy-topic space-ptb">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-title">
                        <h2 class="title">Easy Topic Search</h2>
                    </div>
                    <div class="easy-topic-category">
                        <div class="owl-carousel owl-theme owl-loaded">
                            <div class="owl-stage-outer">
                                <div class="owl-stage">
                                    @foreach($topics as $topic)
                                    <div class="owl-item">
                                        <a href="#" class="topic-category-item">
                                            <div class="category-img">
                                                <img src="{{ asset('webAssets/images/topic-category/excercise.png') }}" alt="#">
                                            </div>
                                            <h5 class="title">{{ $topic['topic'] }}</h5>
                                        </a>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trending-category-topic space-pb">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="section-title">
                        <h2 class="title">The top trending topics</h2>
                    </div>
                    <div class="trending-category-slide">
                        <div class="owl-carousel owl-theme owl-loaded">
                            <div class="owl-stage-outer">
                                <div class="owl-stage">
                                    <div class="owl-item">
                                        <div class="trending-category-item">
                                            <img src="{{ asset('webAssets/images/trending-topics/01.jpg') }}" alt="#">
                                            <div class="category-info">
                                                <div class="category-content">
                                                    <a class="title">Gradient Mobile Store</a>
                                                    <div class="category-btn">
                                                        <a class="btn btn-white" href="#">See More</a>
                                                        <a class="btn btn-primary" href="#">See More</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="owl-item">
                                        <div class="trending-category-item">
                                            <img src="{{ asset('webAssets/images/trending-topics/01.jpg') }}" alt="#">
                                            <div class="category-info">
                                                <div class="category-content">
                                                    <a class="title">Gradient Mobile Store</a>
                                                    <div class="category-btn">
                                                        <a class="btn btn-white" href="#">See More</a>
                                                        <a class="btn btn-primary" href="#">See More</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="owl-item">
                                        <div class="trending-category-item">
                                            <img src="{{ asset('webAssets/images/trending-topics/01.jpg') }}" alt="#">
                                            <div class="category-info">
                                                <div class="category-content">
                                                    <a class="title">Gradient Mobile Store</a>
                                                    <div class="category-btn">
                                                        <a class="btn btn-white" href="#">See More</a>
                                                        <a class="btn btn-primary" href="#">See More</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trending-discussions space-pb">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="section-title">
                        <h2 class="title">Top trending discussions</h2>
                    </div>
                    @foreach ($trending as $topicData)
                    <div class="discussions-iteam">
                        <h3 class="title">{{ $topicData['topic'] }} </h3>
                        <p>Total Coments : {{ $topicData['totalReply'] }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    <div class="section-title">
                        <h2 class="title">Historic of User Discussion</h2>
                    </div>
                    @foreach ($userCommentList as $userComments)
                    @foreach($userComments['userComment'] as $userReply)
                    <div class="discussions-iteam">
                        <h3 class="title">{{ $userComments['topic'] }} </h3>
                        <p>{{ $userReply['discussion_reply'] }}</p>
                    </div>
                    @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <!-- Breadcrumb Section End -->

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    @endsection
</x-home-layout>
