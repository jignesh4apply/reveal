<x-after-paywall-layout title="Congrats">
    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <div class="main-header">
                    <div class="inner-header age-header">
                        <div class="header-top">
                            <div>
                                <img class="logo" src="{{ asset('afterPaywall/images/logo.svg') }}">
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

                <div class="content-box">
                    <img class="content-main-img" src="{{ asset('afterPaywall/images/bg-15.png') }}" alt="">
                    <div class="section-title">
                        <h2 class="title">Paiement réussi ! Configurons votre compte</h2>
                        <p class="description">Bienvenue dans votre programme Reveal.</p>
                    </div>

                    <div class="infobox-wrapper">
                        <div class="info-box style-02">
                            <div class="info-box-icon" style="background-color: rgba(118, 98, 241, 0.2);">📝</div>
                            <span class="info-box-description">Conseils quotidiens de gras</span>
                        </div>
                        <div class="info-box style-02">
                            <div class="info-box-icon" style="background-color: rgba(224, 244, 245, 1);">🏃</div>
                            <span class="info-box-description">Programme sportif personnalisé</span>
                        </div>
                    </div>

                    <p class="bottom-note">*Un email a été envoyé. Vérifiez votre dossier SPAM si vous ne le trouvez pas.</p>

                </div>
                <div type="submit" class="main-btn">
                    @if(!empty(Session::get('isWebView')) && Session::get('isWebView') == 1)
                    <?php
                    $userId = session()->get('userId');
                    ?>
                    <a class="btn-link" href="{{ url('/payment-success?id=' . $userId) }}">Configure mon compte !</a>
                    @else
                    <a href="{{ route('user-finish-registration', $token) }}" class="btn-link">Configure mon compte !</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @section('scripts')
    <script src="https://cdn.amplitude.com/script/58b25352c5f050c3039f226c757dc9ba.js"></script>
    <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
    <script>
        amplitude.track('TConfiguration');
    </script>
    @endsection
</x-after-paywall-layout>
