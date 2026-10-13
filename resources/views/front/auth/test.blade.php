<x-home-layout title="Finish Registration">


    <?php
    // Function to get month names from current month
    function getMonthsFromCurrent()
    {
        // Array of month names
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        // Get the current month index (0-based, where Jan is 0)
        $currentMonthIndex = (int)date('n') - 1;

        // Slice the array to start from the current month and include the next months
        $result = array_merge(
            array_slice($months, $currentMonthIndex), // Months from current month to December
            array_slice($months, 0, $currentMonthIndex) // Months from January to the month before current
        );

        return $result;
    }

    // Get the list of months
    $months = getMonthsFromCurrent();

    // Print the months
    foreach ($months as $month) {
        $nextMonths[] =  $month . "\n";
    }
    ?>


    <!-- <section class="register-block space-tb">
        <div class="container">
            <div class="inner-section">
                <div class="section-top">
                    <div class="section-title-2">
                        <h2 class="main-title-2 text-align-center"><span class="primary">Please finish</span> your registration</h2>
                    </div>
                    <div class="login-info">
                        <div class="login-info-title">
                            <h6 class="login-info-main-title">Login Information</h6>
                            <p class="login-description">You will need these credentials to access the app</p>
                        </div>
                        <form class="sign-up-form" action="">
                            <div class="form-input-block email-input">
                                <input type="email" id="signup-email" name="email" placeholder="Enter Your Email">
                                <label class="input-icon" for="signup-email">
                                    <i class="fa-solid fa-envelope"></i>
                                </label>
                            </div>
                            <div class="form-input-block password-input">
                                <input type="password" id="signup-password" name="password" placeholder="Create Password">
                                <label class="input-icon" for="signup-password">
                                    <i class="fa-solid fa-lock"></i>
                                </label>
                            </div>
                            <div class="form-input-block password-input-2">
                                <input type="password" id="signup-confirm-password" name="password" placeholder="Create Password">
                                <label class="input-icon" for="signup-confirm-password">
                                    <i class="fa-solid fa-lock"></i>
                                </label>
                            </div>
                            <button type="submit" class="form-submit">Continue</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section> -->



    @section('styles')
    <style>
        html {
            height: 100%;
            width: 70%;
            background-color: #f5f6fa;
        }
    </style>
    @endsection

    <div class="h-full bg-[#f5f6fa]">
        <div class="w-full mx-auto p-5">
            <div class="bg-white rounded-md shadow p-3 sm:p-5">
                <p class="text-base font-semibold text-[#351b5c] mb-3">Line Chart with Gradient Background Fill</p>

                <canvas id="line-chart" class="w-full"></canvas>
            </div>
        </div>
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
    <script>
        // bebgin line chart display
        var lineChart = document.getElementById("line-chart").getContext('2d')

        // line chart options
        var options = {
            borderWidth: 2,
            cubicInterpolationMode: 'monotone', // make the line curvy over zigzag
            pointRadius: 2,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderWidth: 4
        }

        // create linear gradients for line chart
        var gradientOne = lineChart.createLinearGradient(0, 0, 0, lineChart.canvas.clientHeight)
        gradientOne.addColorStop(0, 'rgba(51, 169, 247, 0.3)')
        gradientOne.addColorStop(1, 'rgba(0, 0, 0, 0)')

        var gradientTwo = lineChart.createLinearGradient(0, 0, 0, lineChart.canvas.clientHeight)
        gradientTwo.addColorStop(0, 'rgba(195, 113, 239, 0.15)')
        gradientTwo.addColorStop(1, 'rgba(0, 0, 0, 0)')

        fetch('/chart-data').then(response => response.json()).then(data => {
            new Chart(
                lineChart, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                                label: 'Estimated',
                                data: data.data,
                                ...options,
                                borderColor: '#c371ef',
                                fill: 'start',
                                backgroundColor: gradientTwo
                            },
                            // {
                            //     label: 'Emergency',
                            //     data: [150, 230, 195, 260, 220, 300, 320, 490],
                            //     ...options,
                            //     borderColor: '#33a9f7',
                            //     fill: 'start',
                            //     backgroundColor: gradientOne
                            // }
                        ]
                    },
                    options: {
                        plugins: {
                            legend: {
                                display: false, // hide display data about the dataset
                            },
                            tooltip: { // modify graph tooltip
                                backgroundColor: 'rgba(53, 27, 92, 0.8)',
                                caretPadding: 5,
                                boxWidth: 5,
                                usePointStyle: 'triangle',
                                boxPadding: 3
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false // set display to false to hide the x-axis grid
                                },
                                beginAtZero: true
                            },
                            y: {
                                ticks: {
                                    callback: function(value, index, values) {
                                        return '$ ' + value // prefix '$' to the dataset values
                                    },
                                    stepSize: 100
                                }
                            }
                        }
                    }
                }
            )

        });
    </script>
    @endsection
</x-home-layout>
