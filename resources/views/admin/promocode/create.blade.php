<x-admin-layout title="Create Promocode">
    @section('styles')
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }

        /* Switch CSS */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            margin-left: 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: #28a745;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }




        .switch-field {
            display: flex;
            margin-bottom: 8px;
            border-color: #0d6efd;
            overflow: hidden;
        }

        .switch-field input {
            position: absolute !important;
            clip: rect(0, 0, 0, 0);
            height: 1px;
            width: 1px;
            border: 0;
            overflow: hidden;
        }

        .switch-field label {
            background-color: #e4e4e4;
            color: #0d6efd;
            /* color: rgba(0, 0, 0, 0.6); */
            font-size: 14px;
            line-height: 1;
            text-align: center;
            padding: 8px 16px;
            margin-right: -1px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            /* box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px rgba(255, 255, 255, 0.1); */
            transition: all 0.1s ease-in-out;
        }

        .switch-field label:hover {
            cursor: pointer;
        }

        .switch-field input:checked+label {
            background-color: #0d6efd;
            color: whitesmoke;
            box-shadow: none;
        }

        .switch-field label:first-of-type {
            border-radius: 4px 0 0 4px;
        }

        .switch-field label:last-of-type {
            border-radius: 0 4px 4px 0;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Promocodes</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Promocodes</li>
                            <li class="breadcrumb-item active">Promocode</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form id="promocodeForm" action="{{ url('admin/create-promocode') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Add Promocode
                                </h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/promocode-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="title">Title <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}">
                                </div>
                                <div class="form-group">
                                    <label for="promocode">Promocode <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="promocode" name="promocode" class="form-control" value="{{ old('promocode') }}">
                                </div>
                                <div class="form-group">
                                    <label for="description">Description <span style="color: #ff5252;">*</span></label>
                                    <textarea id="description" name="description" class="form-control">{{ old('description') }}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="start_date">Start Date <span style="color: #ff5252;">*</span></label>
                                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="end_date">End Date <span style="color: #ff5252;">*</span></label>
                                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group row">
                                            <label for="discount_type">Discount Type <span style="color: #ff5252;">*</span></label>
                                            <div class="col-sm-9 mt-2">
                                                <div class="switch-field">
                                                    <input type="radio" name="discount_type" id="percentage" value="percentage" onclick="toggleDiscountSuffix()" />
                                                    <label for="percentage">Percentage</label>
                                                    <input type="radio" name="discount_type" id="amount" value="amount" checked onclick="toggleDiscountSuffix()" />
                                                    <label for="amount">Amount</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="discount_value">Discount Value <span style="color: #ff5252;">*</span></label>
                                        <div class="input-group w-full">
                                            <input type="number" id="discount_value" name="discount_value" class="form-control" placeholder="Enter discount" value="{{ old('discount_value') }}" step="0.01" min="0">
                                            <div class="input-group-append">
                                                <span class="input-group-text" id="discount_suffix">€</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="is_yearly_commitment" name="is_yearly_commitment" value="1" onclick="toggleYearlyDiscount()">
                                        Apply for yearly commitment
                                    </label>
                                </div>
                                <div id="yearly_discount_section" style="display: none;">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group row">
                                                <label>Yearly Discount Type</label>
                                                <div class="col-sm-9 mt-2">
                                                    <div class="switch-field">
                                                        <input type="radio" name="yc_discount_type" id="yearly_percentage" value="percentage" onclick="toggleYearlySuffix()" />
                                                        <label for="yearly_percentage">Percentage</label>

                                                        <input type="radio" name="yc_discount_type" id="yearly_amount" value="amount" checked onclick="toggleYearlySuffix()" />
                                                        <label for="yearly_amount">Amount</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <label>Yearly Discount Value</label>
                                            <div class="input-group w-full">
                                                <input type="number" id="yc_discount_value" name="yc_discount_value" class="form-control" placeholder="Enter yearly discount" value="{{ old('yc_discount_value') }}" step="0.01" min="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" id="yearly_discount_suffix">€</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <small style="color: #6c757d;">
                                        Note: Yearly commitment discount is applied per month.
                                        Example: €1 discount = €12 total discount for 12 months.
                                    </small>
                                </div>

                                <div class="form-group row">
                                    <label for="status">Status <span style="color: #ff5252;">*</span></label>
                                    <div class="col-sm-9 mt-2">
                                        <div class="switch-field">
                                            <input type="radio" name="status" id="enable" value="1" checked/>
                                            <label for="enable">Enable</label>
                                            <input type="radio" name="status" id="disable" value="0"/>
                                            <label for="disable">Disable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script>
        // Handler for form submission

        $(document).ready(function() {

            $(function() {
                $('#promocodeForm').validate({
                    rules: {
                        title: {
                            required: true,
                        },

                        promocode: {
                            required: true
                        },

                        description: {
                            required: true
                        }
                    },
                    messages: {
                        title: {
                            required: "Please enter title",
                        },

                        promocode: {
                            required: "Please enter promocode."
                        },

                        description: {
                            required: "Please enter description for this promocode."
                        }
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
        function toggleDiscountSuffix() {
            const discountSuffix = document.getElementById('discount_suffix');
            const isPercentage = document.getElementById('percentage').checked;

            if (isPercentage) {
                discountSuffix.innerText = '%'; // Show percentage sign
            } else {
                discountSuffix.innerText = '€'; // Show Euro sign
            }
        }

        function toggleYearlyDiscount() {
            const checkbox = document.getElementById('is_yearly_commitment');
            const section = document.getElementById('yearly_discount_section');

            section.style.display = checkbox.checked ? 'block' : 'none';
        }

        function toggleYearlySuffix() {
            const suffix = document.getElementById('yearly_discount_suffix');
            const isPercentage = document.getElementById('yearly_percentage').checked;

            suffix.innerText = isPercentage ? '%' : '€';
        }

    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif
    </script>
    @endsection
</x-admin-layout>