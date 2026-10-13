<x-home-layout title="Community">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        a:not([href]):not([class]),
        a:not([href]):not([class]):hover {
            color: #fff !important;
            text-decoration: none !important;
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
        <!-- Page Preloder -->
        <div id="preloder">
            <div class="loader"></div>
        </div>
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
                        <!-- Breadcrumb Section Begin -->
                        <section class="breadcrumb-section set-bg" data-setbg="img/breadcrumb-bg.jpg">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-12 text-center">
                                        <div class="breadcrumb-text">
                                            <h2>{{ $topicDetails['topic'] }}</h2>
                                            <div class="bt-option">
                                                <a>by Admin</a>
                                                <a>{{ $postedDate }}</a>
                                                <span>{{ count($topicDetails['reply']) }} Comment</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- Breadcrumb Section End -->
                        <!-- Blog Details Section Begin -->
                        <section class="blog-details-section spad">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-8 p-0 m-auto">
                                        <div class="blog-details-text">
                                            <div class="blog-details-title">
                                                <p>{!! $topicDetails['content'] !!}</p>
                                            </div>
                                            <!-- <div class="blog-details-pic">
                                                <div class="blog-details-pic-item">
                                                    <img src="img/blog/details/details-1.jpg" alt="">
                                                </div>
                                                <div class="blog-details-pic-item">
                                                    <img src="img/blog/details/details-2.jpg" alt="">
                                                </div>
                                            </div>
                                            <div class="blog-details-desc">
                                                <p>Dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore
                                                    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
                                                    laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit
                                                    in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat
                                                    cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                                                </p>
                                            </div>
                                            <div class="blog-details-quote">
                                                <div class="quote-icon">
                                                    <img src="img/blog/details/quote-left.png" alt="">
                                                </div>
                                                <h5>The whole family of tiny legumes, whether red, green, yellow, or black, offers so many
                                                    possibilities to create an exciting lunch.</h5>
                                                <span>MEIKE PETERS</span>
                                            </div>
                                            <div class="blog-details-more-desc">
                                                <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea
                                                    commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
                                                    dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt
                                                    in. . Sed ut perspiciatis unde omnis iste natus error sit voluptatem.</p>
                                                <p>laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure Lorem ipsum dolor sit
                                                    amet, consectetur adipisicing elit, sed eiusmod tempor incididunt laboris nisi ut
                                                    aliquip commodo consequat. Class aptent taciti sociosqu ad litora torquent per conubia
                                                    nostra, per inceptos himenaeos. Mauris vel magna ex. Integer gravida tincidunt accumsan.
                                                    Vestibulum nulla mauris, condimentum id felis ac, volutpat volutpat mi qui dolorem.</p>
                                            </div>
                                            <div class="blog-details-tag-share">
                                                <div class="tags">
                                                    <a href="#">Body buiding</a>
                                                    <a href="#">Yoga</a>
                                                    <a href="#">Weightloss</a>
                                                    <a href="#">Streching</a>
                                                </div>
                                                <div class="share">
                                                    <span>Share</span>
                                                    <a href="#"><i class="fa fa-facebook"></i> 82</a>
                                                    <a href="#"><i class="fa fa-twitter"></i> 24</a>
                                                    <a href="#"><i class="fa fa-envelope"></i> 08</a>
                                                </div>
                                            </div>
                                            <div class="blog-details-author">
                                                <div class="ba-pic">
                                                    <img src="img/blog/details/blog-profile.jpg" alt="">
                                                </div>
                                                <div class="ba-text">
                                                    <h5>Lena Mollein.</h5>
                                                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
                                                        incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                                                        exercitation.</p>
                                                    <div class="bp-social">
                                                        <a href="#"><i class="fa fa-facebook"></i></a>
                                                        <a href="#"><i class="fa fa-twitter"></i></a>
                                                        <a href="#"><i class="fa fa-google-plus"></i></a>
                                                        <a href="#"><i class="fa fa-instagram"></i></a>
                                                        <a href="#"><i class="fa fa-youtube-play"></i></a>
                                                    </div>
                                                </div>
                                            </div> -->
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="comment-option">
                                                        <h5 class="co-title">Comment</h5>
                                                        @foreach($discussionReply as $reply)
                                                        <div class="co-item">
                                                            <!-- <div class="co-widget">
                                                                <a href="#"><i class="fa fa-heart"></i></a>
                                                                <a href="#"><i class="fa fa-share-square"></i></a>
                                                            </div> -->
                                                            <div class="co-pic">
                                                                <img src="{{ asset('webAssets/gymlifemaster/img/blog/details/comment-1.jpg') }}" alt="">
                                                                <h5>{{ $reply['user']['name'] }}</h5>
                                                            </div>
                                                            <div class="co-text">
                                                                <p>{{ $reply['discussion_reply'] }}</p>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                        <!-- <div class="co-item reply-comment">
                                                            <div class="co-widget">
                                                                <a href="#"><i class="fa fa-heart-o"></i></a>
                                                                <a href="#"><i class="fa fa-share-square-o"></i></a>
                                                            </div>
                                                            <div class="co-pic">
                                                                <img src="{{ asset('webAssets/gymlifemaster/img/blog/details/comment-2.jpg') }}" alt="">
                                                                <h5>Brandon Kelley</h5>
                                                            </div>
                                                            <div class="co-text">
                                                                <p>Neque porro quisquam est, qui dolorem ipsum dolor sit amet, consectetur,
                                                                    adipisci velit dolore.</p>
                                                            </div>
                                                        </div>
                                                        <div class="co-item">
                                                            <div class="co-widget">
                                                                <a href="#"><i class="fa fa-heart-o"></i></a>
                                                                <a href="#"><i class="fa fa-share-square-o"></i></a>
                                                            </div>
                                                            <div class="co-pic">
                                                                <img src="{{ asset('webAssets/gymlifemaster/img/blog/details/comment-3.jpg') }}" alt="">
                                                                <h5>Brandon Kelley</h5>
                                                            </div>
                                                            <div class="co-text">
                                                                <p>Neque porro quisquam est, qui dolorem ipsum dolor sit amet, consectetur,
                                                                    adipisci velit dolore.</p>
                                                            </div>
                                                        </div> -->
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="leave-comment">
                                                        <h5>Leave a comment</h5>
                                                        <form action="{{ route('comment', $topicDetails['id']) }}" method="post">
                                                            {{ csrf_field() }}
                                                            <input type="text" placeholder="Name" id="name" name="name" value="{{ Auth::guard('user')->user()->name }}" @if(is_null(Auth::guard('user')->user()->name)) required @else readonly @endif>
                                                            <input type="email" placeholder="Email" id="email" name="email" value="{{ Auth::guard('user')->user()->email }}" readonly>
                                                            <textarea placeholder="Comment" id="comment" name="comment" required></textarea>
                                                            <button type="submit">Submit</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <!-- Blog Details Section End -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Js Plugins -->
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showMethod: 'slideDown',
            timeOut: 40000
        };

        @if(Session::has('success'))
        toastr.success("{{ session('success') }}", 'Success', {
            "background-color": "#4CAF50"
        });
        @endif

        @if(Session::has('error'))
        toastr.error("{{ session('error') }}", 'Error', {
            "background-color": "#f44336"
        });
        @endif
    </script>
    @endsection
</x-home-layout>
