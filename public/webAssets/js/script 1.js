/* =============== Checkbox Checked Background =============== */

$('.details-select input[type="checkbox"]').change(function () {
    if ($(this).is(":checked")) {
        $(this).parent().addClass("bg-primary");
    } else {
        $(this).parent().removeClass("bg-primary");
    }
});

/* =============== Checkbox Checked Remove Background =============== */

$(".details-select .no-bg").change(function () {
    if ($(this).is(":checked")) {
        $(this).parent().removeClass("bg-primary");
    }
});

/* =============== Checkbox Checked Background =============== */

$(".price-box-single").click(function () {
    $(this).find('input[type="radio"]').prop("checked", true);
    $(".price-box-single").removeClass("selected");
    $(this).addClass("selected");
});

/* =============== Owl carousel =============== */

$(".weekly-plan .owl-carousel").owlCarousel({
    center: true,
    items: 2,
    loop: true,
    margin: 5,
    responsive: {
        575: {
            margin: 15,
            items: 4,
        },
        420: {
            items: 4,
        },
    },
});

$(".result-slider .owl-carousel").owlCarousel({
    center: true,
    items: 1.3,
    loop: true,
    margin: 20,
    nav: true,
    responsive: {
        575: {
            items: 1.5,
        },
    },
});

$(".easy-topic-category .owl-carousel").owlCarousel({
    center: false,
    items: 5,
    loop: true,
    margin: 5,
    responsive: {
        992: {
            margin: 15,
            items: 5,
        },
        767: {
            margin: 15,
            items: 4,
        },
        575: {
            margin: 15,
            items: 3,
        },
        420: {
            items: 2,
        },
        320: {
            items: 2,
        },
    },
});

$(".trending-category-slide .owl-carousel").owlCarousel({
    center: false,
    items: 5,
    loop: true,
    nav: true,
    margin: 5,
    responsive: {
        992: {
            margin: 15,
            items: 2,
        },
        991: {
            margin: 15,
            items: 1.5,
        },
        575: {
            margin: 15,
            items: 1.5,
        },
        480: {
            items: 1.5,
        },
        320: {
            items: 1,
        },
    },
});
