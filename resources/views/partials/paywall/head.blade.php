<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.ico') }}">

<title>{{env('APP_NAME')}} @if ($title)| {{ $title }} @endif</title>

<!-- Fonts CSS -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('paywall/css/fontawesome/all.min.css') }}">
<!-- Owl Caraosel -->
<link rel="stylesheet" href="{{ asset('paywall/css/owl/owl.carousel.min.css') }}">
<!-- Custom CSS -->
<link rel="stylesheet" href="{{ asset('paywall/css/style.css') }}">
<!-- Responsive CSS -->
<!-- <link rel="stylesheet" href="{{ asset('paywall/css/responsive.css') }}"> -->

<!-- Fonts  -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link href="css/fontawesome/all.min.css">
<!-- Owl-carousel -->
<link rel="stylesheet" href="css/owl/owl.carousel.min.css">
<!-- Style CSS -->
<link rel="stylesheet" href="css/style.css">
<!-- SF Pro -->
<link rel="stylesheet" href="{{ asset('sf/rounded/stylesheet.css') }}">
<link rel="stylesheet" href="{{ asset('sf/text/stylesheet.css') }}">
@yield('styles')