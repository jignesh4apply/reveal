<x-admin-layout title="Create Category">
    @section('styles')
    <style>
        .ck.ck-editor__editable_inline {
            color: #000;
            min-height: 200px;
        }

        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Nutritions</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Nutritions</li>
                            <li class="breadcrumb-item active">Nutritions Ingredient Category</li>
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
            <form id="quickForm" action="{{ url('admin/nutrition-ingredient-category-create') }}" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4 class="card-title">Add Nutritions Ingredient Category</h3>
                                <div class="card-tools">
                                    <a type="button" class="btn btn-secondary" href="{{ url('admin/nutrition-ingredient-category-index') }}">
                                        <i class="fas fa-arrow-left"></i>
                                        Return to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Category Name <span style="color: #ff5252;">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}">
                                </div>

                                <div class="form-group">
                                    <label for="image_url">Image <span style="color: #ff5252;">*</span></label>
                                    <input type="file" id="image_url" name="image_url" accept="image/*" class="form-control">
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Predefined Portion Sizes</h5>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-success" id="add-portion-size">Add Portion Size</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="portion-sizes-container">
                                    <div class="portion-size-item mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="portion_sizes[0][name]" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" class="form-control" name="portion_sizes[0][quantity]" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Units <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="portion_sizes[0][units]" required>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-portion-size" disabled>Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                       
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="status" name="status">
                            <label for="status">Enable</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mb-2">
                        <input type="submit"  value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
    <script src="{{ asset('adminAssets/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('adminAssets/plugins/jquery-validation/additional-methods.min.js') }}"></script>

    <script>
        $(function() {
            $('#quickForm').validate({
                rules: {
                    name: {
                        required: true,
                    },
                    image_url: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter category name.",
                    },
                    image_url: {
                        required: "Please select Ingredient Category Image."
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
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('portion-sizes-container');
            const addButton = document.getElementById('add-portion-size');
            let counter = 1;

            addButton.addEventListener('click', function() {
                const newItem = document.createElement('div');
                newItem.className = 'portion-size-item mb-3 p-3 border rounded';
                newItem.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="portion_sizes[${counter}][name]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="portion_sizes[${counter}][quantity]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Units <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="portion_sizes[${counter}][units]" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-danger remove-portion-size">Remove</button>
                        </div>
                    </div>
                `;
                container.appendChild(newItem);
                counter++;

                // Update remove button states
                updateRemoveButtons();
            });

            // Event delegation for remove buttons
            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-portion-size')) {
                    e.target.closest('.portion-size-item').remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                const items = container.querySelectorAll('.portion-size-item');
                if (items.length === 1) {
                    items[0].querySelector('.remove-portion-size').disabled = true;
                } else {
                    items.forEach(item => {
                        item.querySelector('.remove-portion-size').disabled = false;
                    });
                }
            }
        });
    </script>
    @endsection
</x-admin-layout>
