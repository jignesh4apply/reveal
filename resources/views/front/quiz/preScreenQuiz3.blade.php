<x-home-layout title="Questions">
    @section('styles')
    <style type="text/css">
        @keyframes spin {
            0% {
                transform: rotate(0);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        body {
            margin: 0;
            font-family: 'SF Pro Text', Arial, sans-serif;
            background: #F7F8F7;
            color: #0B1D16;
        }

        .main-section {
            min-height: 100vh;
        }

        .container {
            max-width: 375px;
            margin: auto;
            background: #fff;
        }

        .inner-section {
            display: block;
        }

        .main-header {
            display: none;
        }

        .img-fluid.logo {
            height: 32px;
            max-width: 100%;
        }

        .content-wrapper {
            text-align: left;
        }

        .start-box .box-bg {
            margin-bottom: 20px;
            max-width: 100%;
            height: auto;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .description {
            font-size: 16px;
            line-height: 22px;
            font-weight: 400;
            color: #00120B;
            margin: 0 0 12px;
        }

        .time-list {
            background-color: #ffffff;
            border-radius: 50px;
            padding: 4px 8px 4px 4px;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .time-list i {
            font-size: 16px;
            color: #18B45C;
            padding-right: 4px;
        }

        .time-list span {
            font-weight: 500;
            color: #18B45C;
            line-height: 14px;
            font-size: 12px;
        }

        .loader-box {
            display: none;
            margin: 30px 0 25px;
            text-align: center
        }

        .loader {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .loader div {
            line-height: 0;
        }

        .loader div svg {
            animation: spin 2s linear infinite;
        }

        .loader>span {
            font-family: 'SFRounded';
            font-size: 16px;
            line-height: 20px;
            letter-spacing: -0.32px;
            font-weight: 600;
        }

        .skill-wrapper {
            padding: 0;
            width: 100%;
        }

        .progress {
            width: 100%;
            height: 24px;
            background: #f2f3f1;
            border: 2px solid #f2f3f1;
            border-radius: 50px;
            overflow: hidden;
        }

        .bar {
            width: 0%;
            height: 100%;
            background: #1BC666;
            border-radius: 50px;
            position: relative;
        }

        .percent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
        }

        .fixed-button {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
            padding-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            background-color: #1BC666;
            color: #fff;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 20px;
        }

        .desc {
            font-size: 14px;
            color: #6B7280;
            margin-bottom: 16px;
        }

        .hero {
            position: relative;
        }

        .hero img {
            width: 100%;
            height: 320px;
            object-fit: cover;
        }

        .hero-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 16px 6px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
            color: #fff;
        }

        .hero-overlay h1 {
            font-size: 22px;
            font-weight: bold;
        }

        .hero-overlay p {
            margin: 8px 0 0;
            font-size: 14px;
            line-height: 20px;
            color: rgba(255, 255, 255, 0.92);
        }

        .section {
            padding: 16px;
        }

        .btn {
            width: 100%;
            background: #6C4CF1;
            color: #fff;
            padding: 12px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0;
        }

        .review-box {
            background: #fff;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 14px;
        }

        .review-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .review-stars {
            font-weight: 700;
            font-size: 14px;
        }

        .review-box small {
            display: block;
            color: #6B7280;
            font-size: 12px;
            margin-top: 4px;
        }

        .review-text {
            font-size: 13px;
            color: #111827;
            line-height: 18px;
        }

        .transformation-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 14px;
        }

        .transformation-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .transformation-meta {
            padding: 12px 14px;
        }

        .transformation-meta .pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #EAF7EF;
            color: #0F7A3B;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .transformation-meta p {
            margin: 0;
            font-size: 13px;
            color: #111827;
            line-height: 18px;
        }

        .how h3 {
            margin-bottom: 12px;
            font-size: 18px;
        }

        .how .how-sub {
            margin: 0 0 14px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.92);
            line-height: 18px;
        }

        .checklist {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .checklist li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 18px;
        }

        .checklist li i {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-style: normal;
            font-weight: 800;
            font-size: 12px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .card-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card img {
            width: 50px;
        }

        .card small {
            color: #6B7280;
            font-size: 12px;
        }

        .info-box {
            background: #E6F7ED;
            border: 1px solid #B6EACD;
            border-radius: 16px;
            padding: 14px;
            font-size: 13px;
        }

        .feature {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .feature img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .feature p {
            padding: 10px;
            font-weight: 600;
        }

        .transformation img {
            width: 100%;
            border-radius: 12px;
        }

        .transformation p {
            margin-top: 8px;
            font-weight: 600;
        }

        .testimonial {
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 14px;
            font-size: 13px;
        }

        .how {
            background: linear-gradient(#2BCB73, #1DAE5A);
            color: white;
            padding: 20px;
        }

        .how h3 {
            margin-bottom: 12px;
        }

        .how p {
            margin-bottom: 6px;
        }

        .main-section.prequiz .main-header{
            display: block;
            position: absolute;
            z-index: 1;
            text-align: center;
            left: 0;
            right: 0;
            top: 15px;
        }

       .main-section.prequiz .container{
         background:transparent;
         padding: 0px !important;
       }
       .main-section.prequiz .container.main-banner{
         background:#ffffff;
       }

        .main-section .container.main-banner{
           padding: 0px !important;
        }
        .main-section .main-banner .hero img{
            height: 400px;
        }
        .main-section .main-banner .hero .hero-overlay{
           bottom: 10px;
           border-radius: 0 0 30px 30px;
        }
       .main-section .main-banner .hero .hero-overlay h1{
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 0px;
       }
       .main-section.prequiz .container.main-banner .btn.start-button{
            margin-top: 10px;
       }
       .section h2.section-title{
         font-size: 24px;
        line-height: 120%;
         font-weight: bold;
       }
       .main-section.prequiz .container.main-banner .section{
         padding: 16px 16px 30px;
       }
       .main-banner .desc{
        color: #00120B;
         font-size: 16px;
         line-height: 140%;
       }
       .main-section.prequiz .container .section.space-ptb{
         padding: 30px 15px;
       }

       .main-section.prequiz .card.card-flex{
         padding: 0px;
       }
       .main-section.prequiz .card.card-flex .content{
         padding: 16px;
       }
        .main-section.prequiz .card.card-flex.mb-0{
          margin-bottom: 0px;
        }
       .main-section.prequiz .card.card-flex img{
        width: 130px;
       }
        .main-section.prequiz .card.card-flex .app-icon img{
           width: 24px;
        }

       .prequiz .card.card-flex .content h3{
           margin: 0px;
           font-size: 26px;
           line-height: 32px;
           font-weight: bold;
       }
      .prequiz .card.card-flex .content h3>span{
           color: #66716D;
           font-size: 16px;
           line-height: 22px;
           font-weight: bold;
           padding-left: 4px;
       }
       .prequiz .card.card-flex .content small{
          font-size: 12px;
          line-height: 120%;
          font-weight: 600;
       }
       .prequiz .section-wrapper{
          background: #ffffff;
       }
       .prequiz .m-0{
          margin: 0px;
        }
       .info-box {
            background-image: url('/prequiz-screen/images/info-box-bg.png');
            padding: 2px 2px 25px;
            background-repeat: no-repeat;
            background-size: cover;
       }
       .info-box .content{
         padding: 15px;
         border-radius: 15px;
         background-color: #ffffff;
       }
       .center-box{
          text-align: center;
       }
       .center-box h2{ margin-bottom:10px; }
       .center-box .desc {
        font-size: 16px;
        line-height: 140%;
        color: #6B7280;
       }

.video-box.card {
  background: #fff;
  border-radius: 20px;
  padding: 0px;
  margin-bottom: 0px;
  padding: 5px;
  overflow: hidden;
  border: 1px solid rgb(0 0 0 / 10%);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.video-box .card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 15px 8px 0px;
}

.video-box .card-header h2 {
  font-size: 20px;
  margin: 0;
  font-weight: 700;
}

.video-box .card-header p {
  font-size: 14px;
  line-height: 20px;
  color: #666;
  margin-top: 6px;
  margin-bottom: 0px;
}

.video-box .badge {
    background: #eee;
    padding: 6px 15px;
    font-weight: 600;
    right: -8px;
    top: -15px;
    border-radius: 0px 14px;
    position: relative;
}

.video-box .video-section {
  position: relative;
  margin-top: 12px;
}

.video-box .video-section img {
  width: 100%;
  border-radius: 16px;
}

.video-box .play-btn {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0,0,0,0.6);
  color: #fff;
  padding: 12px 16px;
  border-radius: 50%;
  font-size: 18px;
}

.video-box .card-footer {
  background: #fff;
   border: 1px solid rgb(0 0 0 / 10%);
  margin-top: -100px;
  padding: 12px;
  border-radius: 16px;
  position: relative;
}

.video-box .task {
  background: #2f3b3b;
  color: #fff;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  line-height: 16px;
  display: inline-block;
  margin-bottom: 10px;
}

.video-box .card-footer h3 {
  margin: 0;
  font-size: 18px;
}

.video-box .footer-bottom {
  display: flex;
  justify-content: space-between;
  margin-top: 0px;
  font-size: 14px;
  color: #555;
}

.video-box .footer-bottom p{
  margin: 0px;
}


.video-box .time {
  display: flex;
  align-items: center;
  gap: 2px;
}
.video-box .time img{
 width: 14px;
}

.video-box.images-box.card{
    overflow: hidden;
}
.video-box.images-box.card .images {
    position: relative;
    padding-left: 20px;
}
.video-box.images-box.card .images .img2{
    bottom: 23px;
    z-index: 1;
    width: 49px;
    right: -12px;
    position: relative;
}
.video-box.images-box.card .images .img1{
    width: 134px;
    rotate: -13deg;
    margin-bottom: -51px;
    margin-right: -25px;
}
.video-box.images-box.card .images .img3{
    width: 134px;
    rotate: 20deg;
    margin-bottom: -60px;
}

.video-box .list-icon img{
   width: 100%;
   margin: 14px 0px -5px;
}
.video-box .list-title{
    margin: 0px 2px;
}
.video-box .list-img img{
    width: 100%;
    margin: 5px 0 -44px 8px;
}

.text-center{
    text-align: center;
}

.footer-section .header-section{
    background: linear-gradient(180deg, #4ade80 0%, #a7f3d0 100%);
    padding: 50px 0px 0px;
    position: relative;
    z-index: 1;
}
.footer-section .header-section .info-content{
  padding: 0px 20px;
}
.footer-section .top-badge{
        background: #2A3539;
        color: white;
        display: inline-flex;
        align-items: center;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 13px;
        line-height: 16px;
        margin-bottom: 20px;
}
.footer-section .green-dot {
    width: 8px;
    height: 8px;
    background: #4ade80;
    border-radius: 50%;
    margin-right: 8px;
}
.footer-section .header-section h1{
  color: #fff;
  margin: 0;
  font-size: 24px;
  line-height: 120%;
  font-weight: 700;
}
.footer-section .steps-section {
    background: #fff;
    padding: 50px 25px 10px 25px;
    border-radius: 24px 24px 0 0;
    margin-top: -30px;
    position: relative;
    z-index: 3;
    margin-top: -50px;
}
.footer-section .timeline-stick {
    position: absolute;
    left: 24px;
    top: 48px;
    overflow: hidden;
    bottom: 0px;
    width: 25px;
    background: linear-gradient(180deg, #4ade80 0%, #86efac 50%, #c4b5fd 100%);
    border-radius: 20px 20px 0 0;
    z-index: 0;
}
.footer-section .timeline-stick:before{
    content: "";
    position: absolute;
    background: linear-gradient(180deg, #1BC666 0, #FCEF7D 100%);
    left: 0;
    right: 0;
    bottom: 78px;
    width: 25px;
    z-index: 1;
    border-radius: 20px;
    height: 100%;
}
.footer-section .steps-container {
    position: relative;
    z-index: 1;
}
.footer-section .step-item {
    display: flex;
    margin-bottom: 20px;
    align-items: flex-start;
}
.footer-section .icon-circle {
    border-radius: 12px;
    display: flex;
        width: 23px;
    justify-content: center;
    align-items: center;
    margin-right: 15px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}
.footer-section .step-text h2 {
    font-size: 18px;
    color: #111;
    margin: 0 0 4px;
}
.footer-section .step-text p {
    font-size: 14px;
    color: #666;
    margin: 0;
    line-height: 1.4;
}
.footer-section .avtar-img{
  position: relative;
  z-index: 999;
}


.before-title{
    margin-bottom: 35px;
    margin-top: 40px;
}
.before-after-card {
    width: 343px;
    border-radius: 24px;
    padding: 4px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    background-image: url('/prequiz-screen/images/bfaf-card-bg.png');
    background-size: cover;
    background-position: center top;
    background-repeat: no-repeat;
}
.before-after-card.avant-girl{
    background-image: url('/prequiz-screen/images/review-bg.png');
}
.before-after-card.avant-girl .image-wrapper{
    margin-top: 8px;
}
.before-after-card.avant-girl .img-section.before-img{
  transform: rotate(-5.54deg);
}
.before-after-card.avant-girl .img-section.after-img{
    transform: rotate(5.37deg);
}

/* IMAGE SECTION */
.before-after-card .image-wrapper {
    display: flex;
    align-items: flex-start;
    position: relative;
    margin-bottom: 17px;
    margin-top: -11px;
}

.before-after-card .image-wrapper .img-section {
    border-radius: 20px;
    overflow: hidden;
    border: 3.11px solid #ffffff;
    position: relative;
    line-height: 1;
    height: 254px;
}

.before-after-card .image-wrapper .before-img {
    max-width: 174px;
    transform: rotate(5.37deg);
    margin-top: 20px;
    margin-left: -1px;
}

.before-after-card .image-wrapper .after-img {
    margin-left: -11px;
    max-width: 182px;
    transform: rotate(-5.54deg);
}

.before-after-card .image-wrapper img {
    max-width: 100%;
    object-fit: cover;
    height: 100%;
    width: 100%;
}

.before-after-card .label {
    position: absolute;
    bottom: 11px;
    padding: 5px 10px;
    font-size: 14px;
    border-radius: 20px;
    font-weight: 500;
    background: #ffffff;
}

.before-after-card .label.before {
    left: 12px;
    color: #FF483C;
}

.before-after-card .label.after {
    right: 12px;
    color: #18B45C;
}

.before-after-card .card-details {
    background-color: #ffffff;
    padding: 16px 12px;
    border-radius: 20px;
}

/* BADGE */
.before-after-card .badge {
    display: inline-block;
    background: #2d3436;
    color: #fff;
    font-size: 12px;
    line-height: 18px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 20px;
    margin-bottom: 12px;
}

/* TITLE */
.before-after-card .title {
    font-size: 20px;
    line-height: 24px;
    font-weight: bold;
    margin-bottom: 8px;
}

/* META */
.before-after-card .meta {
    font-size: 14px;
    line-height: 16px;
    color: #323D4B;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(0, 11, 18, 0.1);
}

.before-after-card .meta-left {
    display: flex;
    gap: 6px;
}

.before-after-card .meta-right {
    display: flex;
    align-items: center;
    gap: 5px;
}

/* RATING */
.before-after-card .rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.before-after-card .rating-text {
    font-size: 16px;
    line-height: 20px;
    font-weight: bold;
}

.before-after-card .stars {
    display: flex;
}

.before-after-card .stars img {
    width: 18px;
}

/* DESCRIPTION */
.before-after-card .description p {
    font-size: 14px;
    color: #323D4B;
    line-height: 16px;
    margin-bottom: 8px;
    margin-top: 0;
}

.before-after-card .description p:last-child {
    margin-bottom: 0;
}

.fixed-main-btn{
    position: sticky;
    left: inherit;
    transform: inherit;
    bottom: 0;
    margin: 0 auto;
    padding: 20px 16px;
    max-width: 375px;
    z-index: 999;
    background-color: white;
}
.fixed-main-btn .btn.start-button {
   margin-top: 0;
}

    </style>
    @endsection
   <section class="main-section prequiz">
   <div class="container main-banner">
      <header class="main-header">
         <img class="img-fluid logo" src="{{ asset('prequiz-screen/images/logo.png') }}">
      </header>
      <div class="inner-section">
         <div class="hero">
            <img src="{{ asset('prequiz-screen/images/main-banner01.png') }}">
            <div class="hero-overlay">
               <h1>8,000+ personnes ont déjà commencé</h1>
            </div>
         </div>
      </div>
      <div class="section">
         <h2 class="title section-title">Perd du gras. Installe de nouvelles habitudes.</h2>
         <div class="desc">
            Découvre en 2 minutes ton programme personnalisé et la date à laquelle tu perdras tes 5 premiers kilos.
         </div>
         <div><button class="btn start-button" id="startButton" type="button">Je veux mon programme personnalisé</button></div>

      </div>
   </div>
   <div class="container">
      <div class="inner-section">
         <div class="content-wrapper">
            <div class="section space-ptb">
               <div class="card card-flex">
                  <div class="content">
                     <h3>⭐ 4.6/5<span>étoiles</span></h3>
                     <small>Sur + de 2000 avis</small>
                     <div class="app-icon">
                        <img src="{{ asset('prequiz-screen/images/appstor.png') }}">
                        <img src="{{ asset('prequiz-screen/images/playstor.png') }}">
                     </div>
                  </div>
                  <img src="{{ asset('prequiz-screen/images/avtar-img1.png') }}">
               </div>
               <div class="card card-flex">
                  <div class="content">
                     <h3>15,000<span>kg</span></h3>
                     <small>Perdus par notre communauté</small>
                  </div>
                  <img src="{{ asset('prequiz-screen/images/Vectors-2.png') }}">
               </div>
               <div class="card card-flex mb-0">
                  <div class="content">
                     <h3>8,000<span>+</span></h3>
                     <small>Utilisateurs actifs</small>
                  </div>
                  <img src="{{ asset('prequiz-screen/images/avtar-img2.png') }}">
               </div>
            </div>
            <div class="section-wrapper">
               <div class="section">
                  <div class="info-box">
                     <div class="content">
                        <h2 class="m-0">Nos utilisateurs les plus engagés perdent entre 7 et 10 kg de gras en 4 mois</h2>
                        <p class="desc m-0">Programme + leçons + challenge 30 jours</p>
                     </div>
                  </div>
               </div>
               <div class="">

               <div class="section center-box">
                  <h2>Ce qui rend Reveal différent</h2>
                  <p class="desc m-0">Un programme 100 % personnalisé, crée en fonction de ton âge, de ta condition physique</p>
               </div>
               <div class="section">
                  <div class="video-box card">
                     <div class="card-header">
                        <div>
                           <h2>Plus de 100 vidéos éducatives</h2>
                           <p>Pour réellement comprendre ce que tu fais et devenir ton propre coach</p>
                        </div>
                        <span class="badge">1</span>
                     </div>
                     <div class="video-section">
                        <img src="{{ asset('prequiz-screen/images/video-bg1.png') }}">
                     </div>
                     <div class="card-footer">
                        <span class="task">Tâche 1</span>
                        <h3>Découvrir la leçon du jour</h3>
                        <div class="footer-bottom">
                           <p>Comprendre les macros nutriments</p>
                           <span class="time"><img src="{{ asset('prequiz-screen/images/clock.svg') }}"> 2 mins</span>
                        </div>
                     </div>
                  </div>
               </div>
               <div class="section">
                  <div class="video-box images-box card">
                     <div class="card-header">
                        <div>
                           <h2>Un programme adapté à toi</h2>
                           <p>à ton niveau, tes envies, ton emploi du temps</p>
                        </div>
                        <span class="badge">2</span>
                     </div>
                     <div class="images">
                        <img class="img1" src="{{ asset('prequiz-screen/images/card-img02.png') }}">
                        <img class="img2" src="{{ asset('prequiz-screen/images/right-errow1.png') }}">
                        <img class="img3" src="{{ asset('prequiz-screen/images/card-img001.png') }}">
                     </div>
                  </div>
               </div>
               <div class="section">
                  <div class="video-box card">
                     <div class="card-header">
                        <div>
                           <h2>Une évolution sous forme de jeu</h2>
                           <p>pour gagner des habitudes sans t’en rendre compte</p>
                        </div>
                        <span class="badge">3</span>
                     </div>
                     <div class="list-icon">
                        <img src="{{ asset('prequiz-screen/images/list-number1.png') }}">
                     </div>
                     <h5 class="list-title">Termine tes tâches pour passer au niveau supérieur !</h5>
                     <div class="list-img">
                        <img src="{{ asset('prequiz-screen/images/list-img1.png') }}">
                     </div>
                  </div>
               </div>
               <div class="section">

                  <h2 class="text-center before-title">Ils ont commencé comme toi</h2>

                  <div class="before-after-card avant-girl">
                     <div class="image-wrapper">
                        <div class="img-section before-img">
                           <img src="{{ asset('prequiz-screen/images/avant-girl.png') }}">
                           <div class="label before">Avant</div>
                        </div>
                        <div class="img-section after-img">
                           <img src="{{ asset('prequiz-screen/images/après-girl.png') }}">
                           <div class="label after">Après</div>
                        </div>
                     </div>
                     <div class="card-details">
                        <div class="badge">Transformation incroyable !</div>
                        <div class="title">30.8 kg perdu en 9 mois</div>
                        <div class="meta">
                           <div class="meta-left">
                              <span>Julie</span>
                              <span>•</span>
                              <span>34 ans</span>
                           </div>
                           <div class="meta-right">
                              <img src="{{ asset('prequiz-screen/images/location-icon.svg') }}" width="16">
                              <span>Plessis saint Jean</span>
                           </div>
                        </div>
                        <div class="rating">
                           <div class="rating-text">Bonjour à tous !</div>
                           <div class="stars">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                           </div>
                        </div>
                        <div class="description">
                           <p>Je suis à -30,8 kg ! 😳💪 Tout à commencé pour moi le 13 avril 2025 à 115,5 kg. Après 9 mois de programme 84,7 kg 🤩</p>
                           <p>Je ne suis plus en obésité selon l'IMC. Je me sens tellement mieux en tous points et je commence à prendre plaisir à aller courrir moi qui n'avait pas couru depuis 20 ans ah ah 🏃‍♀️</p>
                           <p>Je souhaite à tout le monde beaucoup de force, de la patience et de croire en vous ! Ça viendra j'en suis la preuve ! 🍀</p>
                        </div>
                     </div>
                  </div>



                  <div class="before-after-card">
                     <div class="image-wrapper">
                        <div class="img-section before-img">
                           <img src="{{ asset('prequiz-screen/images/first-img.png') }}">
                           <div class="label before">Avant</div>
                        </div>
                        <div class="img-section after-img">
                           <img src="{{ asset('prequiz-screen/images/second-img.png') }}">
                           <div class="label after">Après</div>
                        </div>
                     </div>
                     <div class="card-details">
                        <div class="badge">Inspirant !</div>
                        <div class="title">20.2 kg perdu en 8,5 mois</div>
                        <div class="meta">
                           <div class="meta-left">
                              <span>Romain</span>
                              <span>•</span>
                              <span>30 ans</span>
                           </div>
                           <div class="meta-right">
                              <img src="{{ asset('prequiz-screen/images/location-icon.svg') }}" width="16">
                              <span>Larre</span>
                           </div>
                        </div>
                        <div class="rating">
                           <div class="rating-text">Tout est possible !</div>
                           <div class="stars">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                              <img src="{{ asset('prequiz-screen/images/star-icon.svg') }}" width="16">
                           </div>
                        </div>
                        <div class="description">
                           <p>Je fais un petit retour d'expérience on va dire même si j'en est pas énormément 😂.</p>
                           <p>Moi c'est Romain 30 ans j'ai toujours été en surpoids depuis l'âge de 13 14 ans. J'ai commencé ma transformation physique il y a un an. Aujourd'hui ça fait trois mois environ que j'ai atteint mon objectif de poids voir même en dessous.</p>
                           <p>C'est vraiment un marathon pas un sprint et je peux même dire que c'est pas juste une transformation physique mon état d'esprit ma relation avec la nourriture tout a changer. Croyez en vous c'est pas juste dans l'assiette ou juste dans les séances de sport que ça ce joue. </p>
                           <p>Enfin voilà j'ai pas trop l'habitude de partager sur les groupes mais plutôt de lire ce que disent les autres. Force à tous tout le monde peut y arriver 💪💪</p>
                        </div>
                     </div>
                  </div>


               </div>
            </div>


            <div class="footer-section">
               <div class="header-section">
                <div class="info-content">
                  <div class="top-badge">
                     <span class="green-dot"></span> Comment ça marche
                  </div>
                  <h1>En deux minutes, Reveal sait exactement ce dont ton corps a besoin.</h1>
                  </div>
                  <img class="avtar-img" src="{{ asset('prequiz-screen/images/avtar-img.png') }}">
                 <div class="steps-section">
                  <div class="timeline-stick"></div>
                  <div class="steps-container">
                     <div class="step-item">
                        <div class="icon-circle bg-dark-green">
                           <img class="" src="{{ asset('prequiz-screen/images/tag-ico01.png') }}">
                        </div>
                        <div class="step-text">
                           <h2>Répond au quiz</h2>
                           <p>Ta condition physique, ton niveau, le temps dont tu disposes</p>
                        </div>
                     </div>
                     <div class="step-item">
                        <div class="icon-circle bg-deep-green">
                           <img class="" src="{{ asset('prequiz-screen/images/tag-ico02.png') }}">
                        </div>
                        <div class="step-text">
                           <h2>Nous créons ton programme</h2>
                           <p>100 % personnalisé, rien de standard</p>
                        </div>
                     </div>
                     <div class="step-item">
                        <div class="icon-circle bg-yellow-green">
                           <img class="" src="{{ asset('prequiz-screen/images/tag-ico03.png') }}">
                        </div>
                        <div class="step-text">
                           <h2>Il évolue avec toi</h2>
                           <p>semaine après semaine, au fur et à mesure que vous progressez</p>
                        </div>
                     </div>
                  </div>
               </div>

               </div>
               

            </div>
            <div class="fixed-main-btn">
                    <button class="btn start-button " type="button">Je veux mon programme personnalisé</button>
                </div>
               </div>

         </div>
      </div>
   </div>
</section>

    @section('scripts')
    <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
	<script>
        amplitude.track('TLP');
	</script>
    <script>
        $(document).ready(function() {
            $('.start-button').on('click', function() {
                window.location.href = "{{ route('questions') }}";
            });
        });
    </script>
    @endsection
</x-home-layout>
