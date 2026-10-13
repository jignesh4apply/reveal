(function($) {
"use strict";

/*************************
      Chart Wave
*************************/

var chartwave = jQuery('#chart-wave')
  if (chartwave.length > 0) {
    var options = {
      series: [{
      name: 'series1',
      data: [77, 60, 50, 40, 30, 20, 5]
    }, {


    }],
      chart: {
      height: 400,
      type: 'area',
        toolbar: {
          show: false
      },
    },
     fill: {
          opacity: 0.3,
          gradient: {
            enabled: true,
            shadeIntensity: 0.1,
            inverseColors: false,
            opacityFrom: 0.7,
            opacityTo: 0.5,
            stops: [20, 100, 100, 100]
          },
        },
    dataLabels: {
      enabled: false
    },
    colors: ['#FF7172', '#000'],
    stroke: {
      curve: 'smooth'
    },
    xaxis: {
      type: 'datetime',
      categories: ["2018-09-19T00:00:00.000Z", "2018-09-19T01:30:00.000Z", "2018-09-19T02:30:00.000Z", "2018-09-19T03:30:00.000Z", "2018-09-19T04:30:00.000Z", "2018-09-19T05:30:00.000Z", "2018-09-19T06:30:00.000Z"]
    },
    tooltip: {
      x: {
        format: 'dd/MM/yy HH:mm'
      },
    },
    };

  var chart = new ApexCharts(document.querySelector("#chart-wave"), options);
  chart.render();
}


})(jQuery);
