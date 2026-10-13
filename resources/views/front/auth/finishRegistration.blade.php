<x-after-paywall-layout title="Finish Registration">
    @section('styles')
    <link rel="stylesheet" href="font/sf-pro/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    @endsection

    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <div class="main-header">
                    <div class="inner-header age-header">
                        <div class="header-top">
                            <div>
                                <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="progress-top">
                    <div class="progress-bar"></div>
                    <div class="progress-step-group">
                        <div class="step step-checked">
                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.70227 2.13155C8.90567 2.33495 8.90567 2.66472 8.70227 2.86812L4.11894 7.45146C3.91554 7.65485 3.58576 7.65485 3.38237 7.45146L1.29903 5.36812C1.09563 5.16472 1.09563 4.83495 1.29903 4.63155C1.50243 4.42815 1.8322 4.42815 2.0356 4.63155L3.75065 6.3466L7.9657 2.13155C8.1691 1.92815 8.49887 1.92815 8.70227 2.13155Z" fill="white" />
                            </svg>
                            <span class="step-name">Paiement</span>
                        </div>
                        <div class="step">
                            <svg width="10" height="10" viewBox="0 0 11 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="5" cy="5" r="5" transform="matrix(1 0 0 -1 0.5 10)" fill="white" />
                            </svg>
                            <span class="step-name">Configuration</span>
                        </div>
                        <div class="step">
                            <svg width="10" height="10" viewBox="0 0 11 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="5" cy="5" r="5" transform="matrix(1 0 0 -1 0.5 10)" fill="white" />
                            </svg>
                            <span class="step-name">Téléchargement</span>
                        </div>
                    </div>
                </div>
                <!-- Progress bar -->


                <div class="question-title">
                    <h3 class="title">Veuillez finaliser votre inscription</h3>
                    <p class="description">Vous aurez besoin de ces identifiants pour accéder à l'application.</p>
                </div>

                <form id="quiz-form" class="answer-form" action="{{ url('user-finish-registration/'.$token) }}" method="post">
                    {{ csrf_field() }}
                    <ul class="answers">
                        <li class="answer input-w-icon">
                            <input type="email" id="signup-email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Entrez votre email" value="{{ $email }}" readonly>
                            <label for="email">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.02074 7.34742C2.12622 5.48088 3.67304 4 5.566 4H18.449C20.4105 4 22 5.58997 22 7.552V17C22 19.2093 20.2093 21 18 21H6C3.79072 21 2 19.2093 2 17V7.551C2 7.48124 2.00714 7.41315 2.02074 7.34742ZM4 10.7796V17C4 18.1047 4.89528 19 6 19H18C19.1047 19 20 18.1047 20 17V10.7898L15.9352 13.5457C13.5634 15.1533 10.4517 15.1534 8.07993 13.5458L4 10.7796ZM20 7.552C20 6.69403 19.3055 6 18.449 6H5.566C4.70928 6 4.015 6.69428 4.015 7.551C4.015 8.06556 4.26974 8.54635 4.69595 8.83515L9.20207 11.8902C10.8963 13.0386 13.1187 13.0386 14.8129 11.8902L19.3199 8.8346C19.7452 8.54702 20 8.06673 20 7.552Z" fill="#17191C" />
                                </svg>
                            </label>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @else
                            <div class="invalid-feedback">Veuillez entrer votre email</div>
                            @enderror
                        </li>
                        <li class="answer input-w-icon second-icon-input">
                            <input type="password" id="signup-password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Entrez le mot de passe">
                            <label for="password">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.13892 13.6854C1.01739 6.85154 6.85176 1.0162 13.6867 2.13798C17.8403 2.81948 21.1801 6.16054 21.8616 10.3139C22.9822 17.1488 17.1478 22.9823 10.3139 21.8616C6.16072 21.1802 2.81947 17.8401 2.13892 13.6854ZM13.3628 4.11157C7.87791 3.21137 3.21237 7.8778 4.11256 13.3618C4.65415 16.6689 7.33098 19.3454 10.6376 19.888C16.1217 20.7873 20.7873 16.1226 19.8879 10.6376C19.3454 7.33095 16.6691 4.65406 13.3628 4.11157ZM9.70686 11.2927C9.31634 10.9022 8.68317 10.9022 8.29265 11.2927C7.90212 11.6832 7.90212 12.3164 8.29265 12.7069C8.68317 13.0974 9.31634 13.0974 9.70686 12.7069C10.0974 12.3164 10.0974 11.6832 9.70686 11.2927ZM6.87843 9.87847C8.05001 8.7069 9.9495 8.7069 11.1211 9.87847C11.4486 10.206 11.6845 10.5903 11.8289 10.9998H16.9998C17.5521 10.9998 17.9998 11.4475 17.9998 11.9998V13.8598C17.9998 14.4121 17.5521 14.8598 16.9998 14.8598C16.4475 14.8598 15.9998 14.4121 15.9998 13.8598V12.9998H14.9998V13.8598C14.9998 14.4121 14.5521 14.8598 13.9998 14.8598C13.4475 14.8598 12.9998 14.4121 12.9998 13.8598V12.9998H11.8289C11.6845 13.4092 11.4486 13.7936 11.1211 14.1211C9.9495 15.2927 8.05001 15.2927 6.87843 14.1211C5.70686 12.9495 5.70686 11.05 6.87843 9.87847Z" fill="#17191C" />
                                </svg>
                            </label>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @else
                            <div class="invalid-feedback">Entrez le mot de passe</div>
                            @enderror
                            <div class="second-icon" onclick="togglePasswordVisibility('signup-password', this)">
                                <div class="show-pw" style="display: none">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.122 9.88C15.293 11.051 15.293 12.952 14.122 14.125C12.951 15.296 11.05 15.296 9.877 14.125C8.706 12.954 8.706 11.053 9.877 9.88C11.05 8.707 12.95 8.707 14.122 9.88Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 11.341 3.152 10.689 3.446 10.088C4.961 6.991 8.309 5 12 5C15.691 5 19.039 6.991 20.554 10.088C20.848 10.689 21 11.341 21 12C21 12.659 20.848 13.311 20.554 13.912C19.039 17.009 15.691 19 12 19C8.309 19 4.961 17.009 3.446 13.912C3.152 13.311 3 12.659 3 12Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="hide-pw" style="display: block">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.34634 6.16278C8.00745 4.87893 9.96464 4 12 4C13.7495 4 15.4421 4.64962 16.9378 5.64801L18.2929 4.29289C18.6834 3.90237 19.3166 3.90237 19.7071 4.29289C20.0976 4.68342 20.0976 5.31658 19.7071 5.70711L17.7606 7.6536C17.7538 7.66065 17.7468 7.66758 17.7398 7.67439L7.6903 17.7239C7.6719 17.7439 7.65283 17.7629 7.63316 17.781L5.7071 19.7071C5.31658 20.0976 4.68341 20.0976 4.29289 19.7071C3.90236 19.3166 3.90236 18.6834 4.29289 18.2929L5.47855 17.1072C4.18238 15.924 3.06606 14.4739 2.23841 12.9437L2.23791 12.9428C1.92102 12.3555 1.92102 11.6455 2.23791 11.0582C3.24465 9.19582 4.67976 7.45085 6.34634 6.16278ZM6.89447 15.6913L8.44456 14.1412C7.48204 12.5457 7.68914 10.4416 9.06589 9.06489C10.4426 7.68815 12.5467 7.48104 14.1422 8.44357L15.4901 7.09572C14.3377 6.39219 13.1445 6 12 6C10.5403 6 9.00254 6.63757 7.5694 7.74522C6.14406 8.84684 4.88707 10.3659 4.00204 12.0005C4.74473 13.3713 5.74888 14.6608 6.89447 15.6913ZM12.6396 9.94618C11.9015 9.71725 11.0643 9.89489 10.4801 10.4791C9.89588 11.0633 9.71824 11.9005 9.94718 12.6386L12.6396 9.94618ZM18.4295 8.16062C18.8495 7.80192 19.4807 7.85158 19.8394 8.27153C20.5727 9.13008 21.2277 10.0699 21.7616 11.0574C22.0785 11.6447 22.079 12.3555 21.7621 12.9428C20.7553 14.8047 19.3202 16.5495 17.6536 17.8374C15.9925 19.1211 14.0353 20 12 20C11.0165 20 10.0511 19.7921 9.13503 19.4376C8.61998 19.2382 8.36406 18.6591 8.56341 18.144C8.76277 17.629 9.34191 17.3731 9.85696 17.5724C10.5789 17.8519 11.2994 18 12 18C13.4596 18 14.9975 17.3624 16.4306 16.2549C17.856 15.1534 19.1129 13.6346 19.9979 12.0005C19.5367 11.1489 18.9652 10.3275 18.3186 9.57048C17.9599 9.15053 18.0096 8.51931 18.4295 8.16062Z" fill="#17191C" fill-opacity="0.6"/>
                                    </svg>
                                </div>
                            </div>
                        </li>
                        <li class="answer input-w-icon second-icon-input">
                            <input type="password" id="signup-confirm-password" name="cnf_password" class="form-control @error('cnf_password') is-invalid @enderror" placeholder="Confirmez le mot de passe">
                            <label for="password">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2.13892 13.6854C1.01739 6.85154 6.85176 1.0162 13.6867 2.13798C17.8403 2.81948 21.1801 6.16054 21.8616 10.3139C22.9822 17.1488 17.1478 22.9823 10.3139 21.8616C6.16072 21.1802 2.81947 17.8401 2.13892 13.6854ZM13.3628 4.11157C7.87791 3.21137 3.21237 7.8778 4.11256 13.3618C4.65415 16.6689 7.33098 19.3454 10.6376 19.888C16.1217 20.7873 20.7873 16.1226 19.8879 10.6376C19.3454 7.33095 16.6691 4.65406 13.3628 4.11157ZM9.70686 11.2927C9.31634 10.9022 8.68317 10.9022 8.29265 11.2927C7.90212 11.6832 7.90212 12.3164 8.29265 12.7069C8.68317 13.0974 9.31634 13.0974 9.70686 12.7069C10.0974 12.3164 10.0974 11.6832 9.70686 11.2927ZM6.87843 9.87847C8.05001 8.7069 9.9495 8.7069 11.1211 9.87847C11.4486 10.206 11.6845 10.5903 11.8289 10.9998H16.9998C17.5521 10.9998 17.9998 11.4475 17.9998 11.9998V13.8598C17.9998 14.4121 17.5521 14.8598 16.9998 14.8598C16.4475 14.8598 15.9998 14.4121 15.9998 13.8598V12.9998H14.9998V13.8598C14.9998 14.4121 14.5521 14.8598 13.9998 14.8598C13.4475 14.8598 12.9998 14.4121 12.9998 13.8598V12.9998H11.8289C11.6845 13.4092 11.4486 13.7936 11.1211 14.1211C9.9495 15.2927 8.05001 15.2927 6.87843 14.1211C5.70686 12.9495 5.70686 11.05 6.87843 9.87847Z" fill="#17191C" />
                                </svg>
                            </label>
                            @error('cnf_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @else
                            <div class="invalid-feedback">Confirmer le mot de passe</div>
                            @enderror
                            <div class="second-icon" onclick="togglePasswordVisibility('signup-confirm-password', this)">
                                <div class="show-pw" style="display: none">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.122 9.88C15.293 11.051 15.293 12.952 14.122 14.125C12.951 15.296 11.05 15.296 9.877 14.125C8.706 12.954 8.706 11.053 9.877 9.88C11.05 8.707 12.95 8.707 14.122 9.88Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 11.341 3.152 10.689 3.446 10.088C4.961 6.991 8.309 5 12 5C15.691 5 19.039 6.991 20.554 10.088C20.848 10.689 21 11.341 21 12C21 12.659 20.848 13.311 20.554 13.912C19.039 17.009 15.691 19 12 19C8.309 19 4.961 17.009 3.446 13.912C3.152 13.311 3 12.659 3 12Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div class="hide-pw" style="display: block">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.34634 6.16278C8.00745 4.87893 9.96464 4 12 4C13.7495 4 15.4421 4.64962 16.9378 5.64801L18.2929 4.29289C18.6834 3.90237 19.3166 3.90237 19.7071 4.29289C20.0976 4.68342 20.0976 5.31658 19.7071 5.70711L17.7606 7.6536C17.7538 7.66065 17.7468 7.66758 17.7398 7.67439L7.6903 17.7239C7.6719 17.7439 7.65283 17.7629 7.63316 17.781L5.7071 19.7071C5.31658 20.0976 4.68341 20.0976 4.29289 19.7071C3.90236 19.3166 3.90236 18.6834 4.29289 18.2929L5.47855 17.1072C4.18238 15.924 3.06606 14.4739 2.23841 12.9437L2.23791 12.9428C1.92102 12.3555 1.92102 11.6455 2.23791 11.0582C3.24465 9.19582 4.67976 7.45085 6.34634 6.16278ZM6.89447 15.6913L8.44456 14.1412C7.48204 12.5457 7.68914 10.4416 9.06589 9.06489C10.4426 7.68815 12.5467 7.48104 14.1422 8.44357L15.4901 7.09572C14.3377 6.39219 13.1445 6 12 6C10.5403 6 9.00254 6.63757 7.5694 7.74522C6.14406 8.84684 4.88707 10.3659 4.00204 12.0005C4.74473 13.3713 5.74888 14.6608 6.89447 15.6913ZM12.6396 9.94618C11.9015 9.71725 11.0643 9.89489 10.4801 10.4791C9.89588 11.0633 9.71824 11.9005 9.94718 12.6386L12.6396 9.94618ZM18.4295 8.16062C18.8495 7.80192 19.4807 7.85158 19.8394 8.27153C20.5727 9.13008 21.2277 10.0699 21.7616 11.0574C22.0785 11.6447 22.079 12.3555 21.7621 12.9428C20.7553 14.8047 19.3202 16.5495 17.6536 17.8374C15.9925 19.1211 14.0353 20 12 20C11.0165 20 10.0511 19.7921 9.13503 19.4376C8.61998 19.2382 8.36406 18.6591 8.56341 18.144C8.76277 17.629 9.34191 17.3731 9.85696 17.5724C10.5789 17.8519 11.2994 18 12 18C13.4596 18 14.9975 17.3624 16.4306 16.2549C17.856 15.1534 19.1129 13.6346 19.9979 12.0005C19.5367 11.1489 18.9652 10.3275 18.3186 9.57048C17.9599 9.15053 18.0096 8.51931 18.4295 8.16062Z" fill="#17191C" fill-opacity="0.6"/>
                                    </svg>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <button type="submit" class="form-submit">Continuer</button>
                    <!-- <a href="{{ route('google-login') }}" class="google-login mt-3">Sign Up With Google</a> -->
                </form>
            </div>
            <!-- <div class="main-btn">
                <a class="btn-link" href="#">Continue</a>
            </div> -->
        </div>
    </section>

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        $('.sign-up-form').validate({
            rules: {
                email: {
                    required: true,
                },
                password: {
                    required: true
                },
                cnf_password: {
                    required: true
                }
            },
            messages: {
                email: {
                    required: "Veuillez entrer votre email",
                },
                Password: {
                    required: "Veuillez entrer le mot de passe",
                },
                cnf_password: {
                    required: "Veuillez saisir le mot de passe comme ci-dessus"
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
    </script>
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
            "background-color": "black"
        });
        @endif
    </script>
    <script>
        function togglePasswordVisibility(passwordFieldId, iconElement) {
            const passwordField = document.getElementById(passwordFieldId);
            const showIcon = iconElement.querySelector('.show-pw');
            const hideIcon = iconElement.querySelector('.hide-pw');

            // Toggle the type of password field
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                showIcon.style.display = 'block';
                hideIcon.style.display = 'none';
            } else {
                passwordField.type = 'password';
                showIcon.style.display = 'none';
                hideIcon.style.display = 'block';
            }
        }
    </script>
    <script src="https://cdn.amplitude.com/script/58b25352c5f050c3039f226c757dc9ba.js"></script>
    <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
    <script>
        amplitude.track('TInscription');
    </script>
    @endsection
</x-after-paywall-layout>
