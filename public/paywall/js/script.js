/*============= Countdown =============*/

function makeTimer() {

	//		var endTime = new Date("29 April 2018 9:56:00 GMT+01:00");	
		var endTime = new Date("29 october 2024 9:56:00 GMT+01:00");			
			endTime = (Date.parse(endTime) / 1000);

			var now = new Date();
			now = (Date.parse(now) / 1000);

			var timeLeft = endTime - now;
			var hours = Math.floor((timeLeft / 3600));
			var minutes = Math.floor((timeLeft - (hours * 3600 )) / 60);
  
			if (hours < "10") { hours = "0" + hours; }
			if (minutes < "10") { minutes = "0" + minutes; }


			$("#hours").html(hours);
			$("#minutes").html(minutes);

	}

	setInterval(function() { makeTimer(); }, 1000);





/*============= Accordian =============*/

$(function () {
	$("#accordion .price-box:not(:first-of-type) .accordion-content").css("display", "none");
	$("#accordion .price-box:first-of-type .js-accordion-title").addClass("open");
   
	$("#accordion .js-accordion-title").click(function () {
	  $(".open").not(this).removeClass("open").next().slideUp(300);
	  $(this).toggleClass("open").next().slideToggle(300);
	});
});

$(function () {
	$("#accordion-02 .price-box:not(:first-of-type) .accordion-content").css("display", "none");
	$("#accordion-02 .price-box:first-of-type .js-accordion-title").addClass("open");
   
	$("#accordion-02 .js-accordion-title").click(function () {
	  $(".open").not(this).removeClass("open").next().slideUp(300);
	  $(this).toggleClass("open").next().slideToggle(300);
	});
});


/*============= Owl carousel =============*/

	$('#success-stories .owl-carousel').owlCarousel({
		center: true,
		loop:true,
		margin:20,
		items:1.3
	});

	$('#diet-program .owl-carousel').owlCarousel({
		center: true,
		loop:true,
		margin:20,
		items:1.3
	});