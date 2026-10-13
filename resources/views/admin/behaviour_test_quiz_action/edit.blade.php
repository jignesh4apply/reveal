<x-admin-layout title="Update Action">
    @section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.css">
    <style>
        .dark-mode input:-webkit-autofill {
            -webkit-background-clip: text;
            -webkit-text-fill-color: #ffffff;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px #23232329;
        }
        .field-container {
            margin-bottom: 15px;
            padding: 12px;
            background-color: #676a6c;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            position: relative;
        }
        .remove-field {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
    </style>
    @endsection

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Actions</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="">Home</a></li>
                            <li class="breadcrumb-item">Behaviour Test</li>
                            <li class="breadcrumb-item active">Update Action</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

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

            <form id="actionForm" action="{{ url('admin/update-behaviour-test-quiz-action/'.$actionData['slug'].'/'.$actionData['id']) }}" method="post">
                {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Action Details</h3>
                            </div>
                            <div class="card-body">
                                <!-- Title Field -->
                                <div class="form-group">
                                    <label for="title">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ $actionData['title'] }}" required>
                                </div>

                                <hr>

                                <!-- Field Selector -->
                                <div class="row">
                                    <div class="col-lg-9">
                                        <label for="fieldSelector">Add Fields</label>
                                        <select class="form-control" id="fieldSelector">
                                            <option value="">-- Select a field to add --</option>
                                            <option value="description">Description</option>
                                            <option value="tag">Tag</option>
                                            <option value="video_link">Video Link</option>
                                            <option value="extra_link">Extra Link</option>
                                            <option value="action_button_text">Action Button Text</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary form-control" id="addFieldBtn">Add Field</button>
                                    </div>
                                </div>

                                <hr>

                                <!-- Dynamic Fields Container -->
                                <div id="fieldsContainer"></div>

                                <hr>

                                <!-- Checkboxes -->
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="trigger_email" name="trigger_email" {{ $actionData['trigger_email'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="trigger_email">
                                            Trigger Email
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ $actionData['is_active'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            Is Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-2">
                        <input type="submit" value="Save" class="btn btn-success float-right">
                    </div>
                </div>
            </form>
        </section>
    </div>

    @section('scripts')
    <script>
        const fieldLabels = {
            'description': 'Description',
            'tag': 'Tag',
            'video_link': 'Video Link',
            'extra_link': 'Extra Link',
            'action_button_text': 'Action Button Text'
        };

        const fieldTypes = {
            'description': 'textarea',
            'tag': 'text',
            'video_link': 'url',
            'extra_link': 'url',
            'action_button_text': 'text'
        };

        const existingData = {
            'description': '{{ $actionData['description'] ?? '' }}',
            'tag': '{{ $actionData['tag'] ?? '' }}',
            'video_link': '{{ $actionData['video_link'] ?? '' }}',
            'extra_link': '{{ $actionData['extra_link'] ?? '' }}',
            'action_button_text': '{{ $actionData['action_button_text'] ?? '' }}'
        };

        let addedFields = [];

        $(document).ready(function() {
            // Load existing fields
            Object.keys(fieldLabels).forEach(fieldName => {
                if (existingData[fieldName]) {
                    addedFields.push(fieldName);
                    addFieldToContainer(fieldName, existingData[fieldName]);
                }
            });

            $('#addFieldBtn').on('click', function(e) {
                e.preventDefault();
                const fieldValue = $('#fieldSelector').val();

                if (!fieldValue) {
                    alert('Please select a field');
                    return;
                }

                if (addedFields.includes(fieldValue)) {
                    alert('This field has already been added');
                    return;
                }

                addedFields.push(fieldValue);
                addFieldToContainer(fieldValue);
                $('#fieldSelector').val('');
            });
        });

        function addFieldToContainer(fieldName, value = '') {
            const fieldLabel = fieldLabels[fieldName];
            const fieldType = fieldTypes[fieldName];
            const fieldHtml = fieldType === 'textarea'
                ? `<textarea class="form-control" name="${fieldName}" rows="3">${value}</textarea>`
                : `<input type="${fieldType}" class="form-control" name="${fieldName}" value="${value}">`;

            const container = `
                <div class="field-container" data-field="${fieldName}">
                    <label>${fieldLabel}</label>
                    ${fieldHtml}
                    <span class="remove-field" onclick="removeField('${fieldName}')">
                        <i class="fas fa-times-circle text-danger"></i>
                    </span>
                </div>
            `;

            $('#fieldsContainer').append(container);
        }

        function removeField(fieldName) {
            $(`[data-field="${fieldName}"]`).remove();
            addedFields = addedFields.filter(f => f !== fieldName);
        }
    </script>
    @endsection
</x-admin-layout>
