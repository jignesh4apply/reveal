<x-home-layout title="Community">
    @section('styles')
    <style>
        .community {
            /* height: -webkit-fill-available; */
            height: auto;
            background-color: rgb(11, 35, 53);
        }

        .topic-search {
            padding: 12px;
            margin-left: 5%;
        }

        .easy-topics, .trendings, .comments {
            margin-left: 5%;
            margin-right: 5%;
        }

        .topic p {
            font-size: 36px;
            color: #fff;
            font-weight: 600;
        }

        .card {
            align-items: center;
            box-shadow: 1px 5px 0px grey;
            margin-bottom: 20px;
            margin-left: 10px;
            height: inherit;
        }

        .topicimg,
        .img-circle {
            border-radius: 100px;
            background-color: darkgrey;
            height: 80px;
            width: 80px;
        }

        .topic-title p {
            /* margin-left: 10px; */
            font-size: medium;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        /* p {
            font-size: 36px;
            color: #fff;
            font-weight: 600;
        } */

        .topic-details {
            border-radius: 10px;
            height: 20rem;
            box-shadow: inset 0 0px 10px #f8a100;
        }

        .trending-title {
            padding: 10px;
            margin-left: 15px;
            margin-top: 17rem;
        }

        .trending-btn{
            padding: 10px;
            margin-left: 60px;
            margin-top: 17rem;
        }

        .discussion {
            border-radius: 5px;
            background-color: #fff;
            box-shadow: inset 0 0px 10px #f8a100;
            padding: 5px;
        }

        .discussion-topic p {
            padding: 5px;
            color: #000;
            font-size: 36px;
            font-weight: 500;
        }

        .comment p {
            font-size: 15px;
            font-weight: 500;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        ::-webkit-scrollbar {
            width: 0px;
        }

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
    <meta charset="UTF-8">
    <meta name="description" content="Gym Template">
    <meta name="keywords" content="Gym, unica, creative, html">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gym | Template</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/bootstrap.min.css') }}" type="text/css"> -->
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/font-awesome.min.css" type="text/css') }}">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/flaticon.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/barfiller.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/magnific-popup.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/slicknav.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('webAssets/gymlifemaster/css/style.css') }}" type="text/css">
    @endsection

    <!-- Breadcrumb Section Begin -->
    <div class="back-back">
        <div class="flx-box container">
            <div class="row h-100 position-relative">
                <div class="col h-100 p-0">
                    <div class="nbar w-100 d-flex align-items-center bg-1">
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
                                    <button class="btn text-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: transparent; border: none; box-shadow: none; width: 10%; height:10%">                                    </button>
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
                    <div class="pages tab-content p-3" id="nav-tabContent" style="background-color: rgb(11, 35, 53);">
                        <div class="topic-search">
                            <div class="topic">
                                <p>Easy Topic Search</p>
                            </div>
                        </div>
                        <div class="easy-topics">
                            <div class="row">
                                @foreach($topics as $topic)
                                <div class="col-lg-2 card">
                                    <a href="{{ url('topic-details/'. $topic['id']) }}" style="text-decoration: none; color:#fff">
                                        <div class="card-body">
                                            <div class="topicimg">
                                                <img class="img-circle" src="{{ asset('adminAssets/dist/img/user2.png') }}" alt="" srcset="">
                                            </div>
                                        </div>
                                        <div class="topic-title">
                                            <p>{{ $topic['topic'] }}</p>
                                        </div>
                                    </a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="trendings mt-4">
                            <div class="trending-topic">
                                <p style="color:#fff; font-size:36px; font-weight:600;">Top Trending Topics</p>
                            </div>
                            <div class="row mt-4">
                                <div class="col-lg-6">
                                    <div class="topic-details set-bg" data-setbg="{{ asset('webAssets/img/topicdetailbg.png') }}">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="trending-title">
                                                    <span style="color:#fff; font-size:medium; letter-spacing: 1.5px;">Trending Title</span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="trending-btn">
                                                    <a href="" class="btn btn-sm btn-secondary">See More</a href="">
                                                    <a href="" class="btn btn-sm btn-primary">See More</a href="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="topic-details set-bg" data-setbg="{{ asset('webAssets/img/topicdetailbg.png') }}">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="trending-title">
                                                    <span style="color:#fff; font-size:medium; letter-spacing: 1.5px;">Trending Title</span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="trending-btn ml-4">
                                                    <a href="" class="btn btn-sm btn-secondary">See More</a href="">
                                                    <a href="" class="btn btn-sm btn-primary">See More</a href="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="comments">
                            <div class="row">
                                <div class="col-lg-6 mt-4 mb-4">
                                    <div class="trending-topic mb-4">
                                        <p style="color:#fff; font-size:36px; font-weight:600;">Top Trending Topics</p>
                                    </div>
                                    <div class="row">
                                        @foreach ($trending as $topicData)
                                        <div class="col-lg-12 mb-2">
                                            <div class="discussion">
                                                <div class="discussion-topic">
                                                    <p>{{ $topicData['topic'] }}</p>
                                                </div>

                                                <div class="comment p-2">
                                                    <p>Total Coments : {{ $topicData['totalReply'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-6 mt-4 mb-4">
                                    <div class="trending-topic mb-4">
                                        <p style="color:#fff; font-size:36px; font-weight:600;">Historic of user discussions</p>
                                    </div>
                                    <div class="row">
                                        @foreach ($userCommentList as $userComments)
                                        @foreach($userComments['userComment'] as $userReply)
                                        <div class="col-lg-12 mb-2">
                                            <div class="discussion">
                                                <div class="discussion-topic">
                                                    <p>{{ $userComments['topic'] }}</p>
                                                </div>
                                                <div class="comment p-2">
                                                    <p>{{ $userReply['discussion_reply'] }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Breadcrumb Section End -->

    @section('scripts')
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/masonry.pkgd.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.barfiller.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/main.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    @endsection

</x-home-layout>
