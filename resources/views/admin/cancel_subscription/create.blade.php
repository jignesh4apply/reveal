<x-admin-layout title="Create Cancel-Subscription Screen">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
        #subs-update-link {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
        }
        .repeater .card-header {
            background-color: #6e7883;
        }
        
        .repeater .card-body {
            padding: 1rem;
            border: 1px solid #6e7883;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Cancel Subscription</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Cancel Subscription</li>
                            <li class="breadcrumb-item active">New</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

            @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <form id="unsubsflowForm" action="{{ url('admin/create-unsubsflow') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Unsubscription-Flow</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group d-flex">
                                    <div class="col-lg-6">
                                        <label for="unsubs_title">Title</label>
                                        <input type="text" class="form-control" id="unsubs_title" name="unsubs_title" value="{{ old('unsubs_title') }}">
                                    </div>
                                    <div class="col-lg-6" style="align-self: end;display: flex;flex-wrap: wrap;">
                                        <label for="show_title">Show title on Screen?</label> &nbsp;
                                        <input type="checkbox" id="show_title" name="show_title" style="width: 20px; height:20px;">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group row">
                                            <div class="col-lg-6">
                                                <label for="is_unsubs_image">Do you want to add Image for this step of flow ?</label>
                                            </div>
                                            <div class="col-lg-1">
                                                <input type="checkbox" id="is_unsubs_image" name="is_unsubs_image" style="width: 20px; height:20px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 unsubsFlowImage">
                                        <div class="form-group">
                                            <label for="unsubs_image">Image</label>
                                            <input type="file" class="form-control" id="unsubs_image" name="unsubs_image" accept="image/*">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="unsubs_description">Description</label>
                                    <textarea class="form-control" id="unsubs_description" name="unsubs_description"></textarea>
                                </div>
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">The flow screen is ?</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="col-lg-3">
                                            <input type="radio" id="flow_as_transition" style="width: 20px; height:20px; margin-top: 5px;" name="is_transition" value="1" checked onchange="toggleFeedbackScreen()">&nbsp; Use as transition flow page &nbsp;
                                        </div>
                                        <div class="col-lg-3">
                                            <input type="radio" id="flow_as_feedback" style="width: 20px; height:20px; margin-top: 5px;" name="is_transition" value="0" onchange="toggleFeedbackScreen()">&nbsp; Use as Feedback flow Page &nbsp;
                                        </div>                                        
                                        <div class="col-lg-3">
                                            <input type="radio" id="flow_as_offerPage" style="width: 20px; height:20px; margin-top: 5px;" name="is_transition" value="2" onchange="toggleFeedbackScreen()">&nbsp; Use as Offer flow Page &nbsp;
                                        </div>                                        
                                    </div>
                                </div>
                                    
                                <div class="card card-primary feedback-screen" style="display: none;">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            Feedback Screen
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group">
                                                    <label>Feedback Type</label>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="row">
                                                        <div class="col-lg-3 singleRadio">
                                                            <input type="radio" id="singleFeedType" style="width: 20px; height:20px; margin-top: 5px;" name="feedback_type" value="single">&nbsp;
                                                            Single answer &nbsp;
                                                        </div>
                                                        <div class="col-lg-3 multipleRadio">
                                                            <input type="radio" id="multipleFeedType" style="width: 20px; height:20px; margin-top: 5px;" name="feedback_type" value="multiple">&nbsp; Multiple Answer
                                                            &nbsp;
                                                        </div>
                                                        <div class="col-lg-3 inputRadio">
                                                            <input type="radio" id="inputFeedType" style="width: 20px; height:20px; margin-top: 5px;" name="feedback_type" value="userInput">&nbsp; Input Answer
                                                            &nbsp;
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-primary mt-2 feedSingle">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    Single Answers Type Feedback
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="repeater">
                                                    <div data-repeater-list="singleAnswers">
                                                        <label for="single_Answer">Add Answers</label>
                                                        <div data-repeater-item>
                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <input type="text" title="Please enter answer." id="single_Answer" name="single_Answer" class="form-control" placeholder="Type Answer" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-2 mt-1">
                                                                    <div class="form-group">
                                                                        <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add
                                                        More</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-primary mt-2 feedMultiple">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    Multiple Type Answers
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="repeater">
                                                    <div data-repeater-list="multipleAnswers">
                                                        <label for="multiple_Answer">Add Answers</label>
                                                        <div data-repeater-item>
                                                            <div class="row">
                                                                <div class="col-lg-10">
                                                                    <div class="form-group">
                                                                        <input type="text" title="Please enter answer." id="multiple_Answer" name="multiple_Answer" class="form-control" placeholder="Type Answer" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-2 mt-1">
                                                                    <div class="form-group">
                                                                        <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add
                                                        More</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card card-primary mt-2 feedInput">
                                            <div class="card-header">
                                                <h3 class="card-title">
                                                    Input Type Answers
                                                </h3>
                                            </div>
                                            <div class="card-body">
                                                <label for="input_Answer">Add Answers</label>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-group">
                                                            <input type="text" title="Please enter Label you want to take input from user." id="input_Answer" name="input_Answer" class="form-control" placeholder="Enter label you want to take input from user" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card card-primary offer-screen" style="display: none;">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            Offer Screen
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-3 discRadio">
                                                <input type="radio" id="discPage" style="width: 20px; height:20px; margin-top: 5px;" name="have_offer" value="1">&nbsp;
                                                Discount Page &nbsp;
                                            </div>
                                            <div class="col-lg-3 invoiceRadio">
                                                <input type="radio" id="invoicePage" style="width: 20px; height:20px; margin-top: 5px;" name="have_offer" value="2">&nbsp; 
                                                Invoice Page &nbsp;
                                            </div>
                                        </div>
                                        <div class="col-lg-6 align-self-center">
                                            <label for="offer_in_per">Please provide the Discount in Percentage</label>
                                            <input type="number" class="form-control" min="1" max="100" id="offer_in_per" name="offer_in_per">
                                        </div>
                                        <div class="col-lg-6 align-self-center update-offer-link" style="display: none;"> 
                                            <a href="#" class="generate_link" name="generate_link" id="generate_link">Generate Link</a>
                                            <br>
                                            <label> Use this Link On Update Offer button : &nbsp;</label>
                                            <input type="text" id="subs-update-link" readonly value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="button_text">Insert Text to display on Button</label>
                                    <input type="text" class="form-control" id="button_text" name="button_text">
                                    <div class="col-lg-6 align-self-center">
                                        <label for="show_default_button">Show Default Button on Screen?</label> &nbsp;
                                        <input type="checkbox" id="show_default_button" name="show_default_button" style="width: 20px; height:20px;">
                                    </div>
                                    <div>
                                        <label for="is_multiple_buttons">Have Multiple Buttons?</label> &nbsp;
                                        <input type="checkbox" id="is_multiple_buttons" name="is_multiple_buttons" style="width: 20px; height:20px;">
                                    </div>
                                </div>
                                <div class="card card-primary multiButtonFeed">
                                    <div class="card-header">
                                        <h3 class="card-title">Multiple Buttons Configuration</h3>
                                    </div>
                                    <div class="card-body">

                                        <div class="repeater">
                                            <div data-repeater-list="multipleButtons">
                                                <label for="single_Answer">Add Multiple Buttons to Screen</label>
                                                <div data-repeater-item>
                                                    <div class="card mb-3">
                                                        <div class="card-header">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <h5 class="mb-0">New Button</h5>
                                                                </div>
                                                                <div class="col-auto">
                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete>X</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <input type="text" title="Please enter Text to Display on Button" id="btn_text" name="btn_text" class="form-control" placeholder="Type Text" required>
                                                            </div>
                                                            <div class="form-group row">
                                                                <label>Button Use as </label>
                                                                <div class="col-lg-3 linkRadio">
                                                                    <input type="radio" id="btnWillLink" style="width: 20px; height:20px; margin-top: 5px;" name="btn_type" value="link">&nbsp;
                                                                    Use as Link &nbsp;
                                                                </div>
                                                                <div class="col-lg-3 btnRadio">
                                                                    <input type="radio" id="btnWillButton" style="width: 20px; height:20px; margin-top: 5px;" name="btn_type" value="btn">&nbsp;
                                                                    Use as Button &nbsp;
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="redirect_to">Choose Where to redirect</label>
                                                                <select name="redirect_to" id="redirect_to" class="form-control">
                                                                    <option value="0">Choose</option>
                                                                    <option value="1">Enter Link Manually</option>
                                                                    <option value="2">Choose Existing Screen</option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group redirectLink">
                                                                <input type="text" title="Please enter Link to redirect on" id="manual_link" name="manual_link" class="form-control" placeholder="Enter Link" required>
                                                            </div>
                                                            <div class="col-lg-4 redirectScreen">
                                                                <div class="form-group">
                                                                    <label for="redirect_screen_id">Select Screen to be redirect</label>
                                                                    <select name="redirect_screen_id" id="redirect_screen_id" class="form-control">
                                                                        <option value="">Select Screen</option>
                                                                        @foreach ($screens as $screen)
                                                                        <option value="{{ $screen['id'] }}">{{ $screen['screen_title'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm mt-2 float-right" data-repeater-create>Add More</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>

    <script>
        $(document).ready(function() {
            $(function() {
                $('#unsubsflowForm').validate({
                    rules: {
                        unsubs_title: {
                            required: true,
                        },
                        unsubs_description: {
                            required: true,
                        }
                    },
                    messages: {
                        unsubs_title: {
                            required: "Please enter transition title",
                        },
                        unsubs_description: {
                            required: "Please write something in short about this transition"
                        },
                    },
                    errorElement: 'div',
                    errorPlacement: function(error, element) {
                        error.addClass('invalid-feedback');
                        element.closest('.form-group').append(error);
                    },
                    highlight: function(element, errorClass, validClass) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function(element, errorClass, validClass) {
                        $(element).removeClass('is-invalid');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Question Image
            $(".unsubsFlowImage").hide();
            $('#is_unsubs_image').change(function() {
                if (!this.checked)
                    //  ^
                    $('.unsubsFlowImage').hide();
                else
                    $('.unsubsFlowImage').show();
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Question Image
            $(".multiButtonFeed").hide();
            $('#is_multiple_buttons').change(function() {
                if (!this.checked)
                    //  ^
                    $('.multiButtonFeed').hide();
                else
                    $('.multiButtonFeed').show();
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('input[name="have_offer"]').on('change', function() {
                if ($('#discPage').is(':checked')) {
                    $('.update-offer-link').hide();
                } else if ($('#invoicePage').is(':checked')) {
                    $('.update-offer-link').show();
                }
            });
        });
    </script>
    <script>
         $(document).ready(function() {
            $('.repeater').repeater({
                initEmpty: false,
                isFirstItemUndeletable: true,
                show: function() {
                    let repeaterItem = $(this);
                    repeaterItem.slideDown();

                    // Initially hide the redirect elements
                    repeaterItem.find('.redirectScreen').hide();
                    repeaterItem.find('.redirectLink').hide();

                    // Scoped event listener
                    repeaterItem.find('#redirect_to').change(function() {
                        let parent = $(this).closest('[data-repeater-item]');
                        let redirectToValue = $(this).val();

                        parent.find('.redirectScreen').hide();
                        parent.find('.redirectLink').hide();

                        if (redirectToValue == "1") {
                            parent.find('.redirectScreen').hide();
                            parent.find('.redirectLink').show();
                        } else if (redirectToValue == "2") {
                            parent.find('.redirectScreen').show();
                            parent.find('.redirectLink').hide();
                        } else if (redirectToValue == "0") {
                            parent.find('.redirectScreen').hide();
                            parent.find('.redirectLink').hide();
                        }
                    });
                },
                hide: function(deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

            // Hide the redirect elements for existing items on page load
            $('[data-repeater-item]').each(function() {
                let parent = $(this);

                parent.find('.redirectScreen').hide();
                parent.find('.redirectLink').hide();

                // Attach the change event listener
                parent.find('#redirect_to').change(function() {
                    let redirectToValue = $(this).val();

                    parent.find('.redirectScreen').hide();
                    parent.find('.redirectLink').hide();

                    if (redirectToValue == "1") {
                        parent.find('.redirectScreen').hide();
                        parent.find('.redirectLink').show();
                    } else if (redirectToValue == "2") {
                        parent.find('.redirectScreen').show();
                        parent.find('.redirectLink').hide();
                    } else if (redirectToValue == "0") {
                        parent.find('.redirectScreen').hide();
                        parent.find('.redirectLink').hide();
                    }
                });
            });
        });    
    </script>

    <script>
        $(function() {
            // Summernote
            $('#unsubs_description').summernote();
        })
    </script>

    <script>
        function toggleFeedbackScreen() {
            var is_transition = $('input[name="is_transition"]:checked').val();
            if (is_transition == '1') {
                $(".feedback-screen").hide();
                $(".offer-screen").hide();
            } else if (is_transition == '0') {
                $(".offer-screen").hide();
                $(".feedback-screen").show();
                $("#singleFeedType").prop("checked", true).trigger("click");
                $(".feedSingle").show();
                $(".feedMultiple").hide();
                $(".feedInput").hide();
                $("input[name$='feedback_type']").click(function() {
                    var feedback_type = $(this).val();
                    if (feedback_type == "single") {
                        $(".feedSingle").show();
                        $(".feedMultiple").hide();
                        $(".feedInput").hide();
                    } else if (feedback_type == "multiple") {
                        $(".feedSingle").hide();
                        $(".feedMultiple").show();
                        $(".feedInput").hide();
                    } else if (feedback_type == "userInput"){
                        $(".feedSingle").hide();
                        $(".feedMultiple").hide();
                        $(".feedInput").show();
                    }
                });
            } else if (is_transition == '2'){
                $(".feedback-screen").hide();
                $(".offer-screen").show();
            }
        }
    </script>

    <script>
        $(document).on('click', '.generate_link', function(){
            event.preventDefault();
            var percentage = $('#offer_in_per').val();
            var min = $('#offer_in_per').attr('min');
            var max = $('#offer_in_per').attr('max');

            min = parseFloat(min);
            max = parseFloat(max);

            if (percentage) {
                percentage = parseFloat(percentage);

                if (percentage >= min && percentage <= max) {
                    var baseUrl = "{{ url('update/subs') }}";
                    var url = baseUrl + '/' + percentage;
                    $('#subs-update-link').val(url);
                } else {
                    alert("Please provide a discount percentage between " + min + " and " + max + ".");
                }
            } else {
                alert("Please provide a discount percentage.");
            }
        });
    </script>
    @endsection
</x-admin-layout>
