<x-home-layout title="Payment">
    @section('styles')

    <style>
        .btn-link {
            font-size: 16px;
            line-height: 22px;
            text-align: center;
            display: inline-block;
            width: 100%;
            height: 50px;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--heading-color);
            border-radius: 12px;
        }
        .alert-danger{
            color: #fff;
            background-color: red;
            border-radius: 5px;
            text-align: left;
        }

        .alert-danger p{
            padding: 5px;
            font-size: 20px;
            font-weight: 600;
        }
    </style>

    @endsection
    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <div class="main-header">
                    <div class="inner-header">
                        <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}" alt="logo">
                        <form id="checkout-payment" action="{{ route('new-subscription-payment',$id) }}" method="POST">
                            @csrf
                            <div class="flex flex-wrap -mx-4">
                                 <div class="flex-initial w-full md:w-7/12 lg:w-8/12 px-4">
                                    <div class="checkout-details-form">

                                        <div class="cf-back">
                                            <h4>Payment</h4>
                                        </div>

                                        <div class="cf-alerts"></div>
                                        <div class="details-body" style="text-align: justify;">
                                            <div class="cf-inside space-y-3 md:space-y-6">
                                                <div class="cf-fieldset">
                                                    <div class="cf-label">
                                                        <h5>Enter Your Card Details</h5>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-3 md:gap-6">
                                                        <div class="col-span-2">
                                                            <div class="cf-field">
                                                                <label>Card Number</label>
                                                                <div id="card_number" class="form-control"></div>
                                                            </div>
                                                        </div>
                                                        <div class="cf-field">
                                                            <label>Expiry Date</label>
                                                            <div id="card_expiry" class="form-control"></div>
                                                        </div>
                                                        <div class="cf-field">
                                                            <label>CVC</label>
                                                            <div id="card_cvc" class="form-control"></div>
                                                        </div>
                                                    </div>
                                                    <div class="cf-credit mt-4 md:mt-8">
                                                        <div class="flex flex-wrap justify-between items-center">
                                                            <div class="cf-credit-icons">
                                                                <img class="h-5 md:h-8" src="{{ asset('webAssets/images/payment.png') }}">
                                                            </div>
                                                            <div class="cf-credit-text">
                                                                <span class="text-xs md:text-sm font-semibold text-gray-70 inline-block align-middle">Secured
                                                                    by Stripe</span>
                                                                <img class="inline-block" src="{{ asset('webAssets/images/lock.svg') }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="total mb-8">
                                            <div class="bg-shade rounded-xl px-6 py-4">
                                                <div class="total-button mb-2 md:mb-4">
                                                    <button type="submit" id="nextButton" class="btn-link">Next</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div id="stripeKey" data-value="{{ config('services.stripe.key') }}"></div>
    @section('scripts')
    <script src="https://js.stripe.com/v3/"></script>

    <script>
        if ($("#stripeKey").length) {
            var key = $("#stripeKey").data("value");
            var stripe = Stripe(key);
            var elements = stripe.elements();

            var style = {
                base: {
                    color: "#32325D",
                    fontWeight: 500,
                    fontFamily: "Inter, Open Sans, Segoe UI, sans-serif",
                    fontSize: "16px",
                    fontSmoothing: "antialiased",

                    "::placeholder": {
                        color: "#CFD7DF"
                    }
                },
                invalid: {
                    color: "#E25950"
                }
            };

            var cardElement = elements.create('cardNumber', {
                style: style
            });
            var exp = elements.create('cardExpiry', {
                style: style
            });
            var cvc = elements.create('cardCvc', {
                style: style
            });
        }

        var alerts = $(".cf-alerts");
        if ($(".total-button button").length) {
            var checkBTN = $(".total-button button");


            cardElement.mount('#card_number');
            exp.mount('#card_expiry');
            cvc.mount('#card_cvc');

            cardElement.on("change", function(event) {
                if (event.error) {
                    checkBTN.prop("disabled", true);
                    alerts.html('<div class="alert-danger"><p>' + event.error.message + '</p></div>');
                } else {
                    checkBTN.prop("disabled", false);
                    alerts.html('');
                }
            })
        }

        var form = $("form");
        form.on('submit', function(e) {
            e.preventDefault();
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    $(".cf-alerts").html('<div class="alert-danger"><p>' + result.error.message +
                        '</p></div>');
                } else {
                    $(".cf-alerts").html('');

                    // append stripe token
                    form.append('<input type="hidden" name="stripeToken" value="' + result.token.id + '">');

                    // submit the form
                    form[0].submit();
                }
            });
        })
    </script>

    @endsection
</x-home-layout>
