<x-after-paywall-layout title="Finish Registration">
    @section('styles')
    <link rel="stylesheet" href="font/sf-pro/stylesheet.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                    <h3 class="title">Où avez-vous entendu parler de Reveal.club ?</h3>
                </div>

                <form id="quiz-form" class="answer-form">
                    <ul class="answers single">
                        <li class="answer">
                            <input class="form-control" id="instagram" type="radio" name="Source" value="Instagram">
                            <label for="instagram">Instagram</label>
                        </li>
                        <li class="answer">
                            <input class="form-control" id="facebook" type="radio" name="Source" value="Facebook">
                            <label for="facebook">Facebook</label>
                        </li>
                        <li class="answer">
                            <input class="form-control" id="tikTok" type="radio" name="Source" value="TikTok">
                            <label for="tikTok">TikTok</label>
                        </li>
                        <li class="answer">
                            <input class="form-control" id="youtube" type="radio" name="Source" value="YouTube">
                            <label for="youtube">YouTube</label>
                        </li>
                        <li class="answer">
                            <input class="form-control" id="television" type="radio" name="Source" value="Television">
                            <label for="television">Télévision</label>
                        </li>
                        <li class="answer">
                            <input class="form-control" id="friend" type="radio" name="Source" value="Friend">
                            <label for="friend">Un ami m'en a parlé</label>
                        </li>
                    </ul>
                </form>
            </div>
        </div>
    </section>

    @section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('ul.answers li').on('click', function() {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var selectedValue = $(this).find('input[type="radio"]').val();

                $.ajax({
                    url: '/referral-source',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        source: selectedValue
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '{{ route("download") }}';
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });
            });
        });
    </script>
    <script src="https://cdn.amplitude.com/script/58b25352c5f050c3039f226c757dc9ba.js"></script>
    <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
    <script>
        amplitude.track('TEntendu');
    </script>
    @endsection

</x-after-paywall-layout>
