<!-- Harmin's Design -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{env('APP_NAME')}} @if ($title)| {{ $title }} @endif</title>

<!-- Fonts CSS -->
<link rel="stylesheet" href="{{ asset('webAssets/font/Sohne/stylesheet.css') }}">
<link rel="stylesheet" href="{{ asset('webAssets/font/lato/stylesheet.css') }}">
<link rel="stylesheet" href="{{ asset('webAssets/css/fontawesome/all.min.css') }}">
<!-- Owl Caraosel -->
<link rel="stylesheet" href="{{ asset('webAssets/css/owl/owl.carousel.min.css') }}">
<!-- Custom CSS -->
<link rel="stylesheet" href="{{ asset('webAssets/css/style.css') }}">
<!-- Responsive CSS -->
<link rel="stylesheet" href="{{ asset('webAssets/css/responsive.css') }}">
<!-- SF Pro -->
<link rel="stylesheet" href="{{ asset('sf/rounded/stylesheet.css') }}">
<link rel="stylesheet" href="{{ asset('sf/text/stylesheet.css') }}">

<!-- <link rel="stylesheet" href="{{ asset('webAssets/utilities/css/fontawesome/all.min.css') }}"> -->
<!-- Style CSS -->
<!-- <link rel="stylesheet" href="{{ asset('webAssets/utilities/css/style.css') }}"> -->
<!-- Responsive CSS -->
<!-- <link rel="stylesheet" href="{{ asset('webAssets/utilities/css/responsive.css') }}"> -->
<!-- Harmin's Design -->
<!-- <link rel="stylesheet" href="{{ asset('webAssets/css/app.css') }}"> -->
<script>
    (function(w,d,s,l,i){
        w[l]=w[l]||[];
        w[l].push(
            {'gtm.start': new Date().getTime(),event:'gtm.js'}
        );
        var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),
            dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;
            j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
            f.parentNode.insertBefore(j,f);
    })
    (window,document,'script','dataLayer','GTM-5C7FW684');
</script>
