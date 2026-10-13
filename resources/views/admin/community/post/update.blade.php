<x-admin-layout title="Community Post">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        /* Optional: improves nested reply indentation */
        .list-group .list-group {
            margin-left: 1.5rem;
            border-left: 2px solid #dee2e6;
            padding-left: 1rem;
        }

        /* Smaller buttons neatly aligned */
        .btn-sm {
            margin-left: 0.25rem;
        }

        /* Show pointer for reply toggle */
        .btn-link {
            padding: 0;
            font-size: 0.9rem;
        }

        .no-link-style {
            color: inherit !important;
            text-decoration: none !important;
            cursor: pointer;
        }
        .no-link-style:hover {
            text-decoration: underline !important;
            color: #007bff;
        }
        .card-title button {
            font-size: 0.875rem;
        }
        .list-group-item {
            background-color: #f9f9f9;
        }
    </style>
    @endsection
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Community Post</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item">Community</li>
                            <li class="breadcrumb-item">Community Post</li>
                            <li class="breadcrumb-item active">Community Post Edit</li>
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
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Community Post</h3>
                            <div>
                                @if ($post->reports->count())
                                    <span class="badge badge-warning ml-2">Reports: {{ $post->reports->count() }}</span>
                                @endif
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <form action="{{ route('admin.community-post-update', $post['id']) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="content">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="3">{{ old('content', $post->content) }}</textarea> 
                                    @error('content')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="d-flex flex-row">
                                    <div class="form-group mr-3" style="flex:1;">
                                        <label for="content_type">Content Type</label>
                                        <select class="form-control" id="content_type" name="content_type">
                                            <option value="text_image" {{ old('content_type', $post->content_type ?? '') == 'text_image' ? 'selected' : '' }}>Text & Image</option>
                                            <option value="text" {{ old('content_type', $post->content_type ?? '') == 'text' ? 'selected' : '' }}>Text</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="image-group" style="{{ old('content_type', $post->content_type ?? '') == 'text_image' ? '' : 'display:none;' }}; flex:1;">
                                        <label for="image">Image</label>
                                        <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                        @if(!empty($post->image))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $post->image) }}" alt="Post Image" style="max-width: 200px; max-height: 200px;">
                                            </div>
                                        @endif
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <label for="topic">Topic</label>
                                        <select class="form-control" id="topic" name="topic_id">
                                            @foreach($topics as $topic)
                                            <option value="{{ $topic->id }}" {{ (old('topic_id', $post->topic_id) == $topic->id) ? 'selected' : '' }}>{{ $topic->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('topic_id')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="colour_theme">Colour Theme</label>
                                        <select class="form-control" id="colour_theme" name="colour_theme">
                                            <option value="blue" {{ (old('colour_theme', $post->colour_theme ?? '') == 'blue') ? 'selected' : '' }}>Blue</option>
                                            <option value="green" {{ (old('colour_theme', $post->colour_theme ?? '') == 'green') ? 'selected' : '' }}>Green</option>
                                            <option value="purple" {{ (old('colour_theme', $post->colour_theme ?? '') == 'purple') ? 'selected' : '' }}>Purple</option>
                                            <option value="turquoise" {{ (old('colour_theme', $post->colour_theme ?? '') == 'turquoise') ? 'selected' : '' }}>Trquoise</option>
                                            <option value="yellow" {{ (old('colour_theme', $post->colour_theme ?? '') == 'yellow') ? 'selected' : '' }}>Yellow</option>
                                            <option value="pink" {{ (old('colour_theme', $post->colour_theme ?? '') == 'pink') ? 'selected' : '' }}>Pink</option>
                                        </select>
                                        @error('colour_theme')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-group">
                                    Posted by 
                                    <span class="posted-by-tooltip" data-toggle="tooltip" data-placement="top" title="On click, view the user">
                                        @if($post->user)
                                            <a href="{{ url('admin/user-profile/'.$post->user->id) }}" class="no-link-style">
                                                <strong>{{ $post->user->name }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ optional($post->user)->name ?? 'Deleted user' }}</strong>
                                        @endif
                                    </span>
                                </div>
                                <div class="mt-4 card">
                                    <div class="card-body">
                                        <h4 class="card-title d-flex justify-content-between align-items-center">
                                            <span>Comments ({{ $post->comments->count() }})</span>

                                            @if($post->comments->count())
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#commentsCollapse">
                                                    Show/Hide Comments
                                                </button>
                                            @endif
                                        </h4>
                                    </br>
                                        @if($post->comments->count())
                                            <div class="collapse mt-3" id="commentsCollapse">
                                                <ul class="list-group list-group-flush">
                                                    @foreach($post->comments as $index => $comment)
                                                        <li class="list-group-item bg-light border rounded mb-2" id="comment-item-{{ $comment->id }}">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <span class="posted-by-tooltip" data-toggle="tooltip" title="View user profile">
                                                                        @if($comment->user)
                                                                            <a href="{{ url('admin/user-profile/'.$comment->user->id) }}" class="no-link-style">
                                                                                <strong>{{ $comment->user->name }}</strong>
                                                                            </a>
                                                                        @else
                                                                            <strong>Deleted user</strong>
                                                                        @endif
                                                                    </span>: <span id="comment-text-{{ $comment->id }}">{{ $comment->comment }}</span>

                                                                    @if ($comment->reports->count())
                                                                        <span class="badge badge-warning ml-2">Reports: {{ $comment->reports->count() }}</span>
                                                                    @endif
                                                                </div>
                                                                <div class="ml-2">
                                                                    <button type="button" class="btn btn-sm btn-primary edit-comment-btn" 
                                                                        data-id="{{ $comment->id }}" 
                                                                        data-comment="{{ $comment->comment }}">
                                                                        Edit
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger delete-comment-btn" 
                                                                        data-id="{{ $comment->id }}">
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            @if($comment->commentReplies->count())
                                                                <div class="comment-replies-wrapper" id="comment-replies-wrapper-{{ $comment->id }}">
                                                                    <div class="mt-2">
                                                                        @php
                                                                            // Flatten all replies (including nested ones)
                                                                            $allReplies = collect();

                                                                            $comment->commentReplies->each(function($reply) use ($allReplies) {
                                                                                $allReplies->push($reply);

                                                                                // Add nested replies if exist
                                                                                if ($reply->children && $reply->children->count()) {
                                                                                    foreach ($reply->children as $child) {
                                                                                        $allReplies->push($child);
                                                                                    }
                                                                                }
                                                                            });
                                                                        @endphp

                                                                        @if($allReplies->count())
                                                                            <div class="comment-replies-wrapper mt-2" id="comment-replies-wrapper-{{ $comment->id }}">
                                                                                <button class="btn btn-sm btn-link p-0" type="button" data-toggle="collapse" data-target="#replies-{{ $index }}">
                                                                                    Show/Hide Replies (<span class="reply-count" id="reply-count-{{ $comment->id }}">{{ $allReplies->count() }}</span>)
                                                                                </button>
                                                                                <div class="collapse" id="replies-{{ $index }}">
                                                                                    <ul class="list-group mt-2 ml-3">
                                                                                        @foreach($allReplies as $reply)
                                                                                            <li class="list-group-item border rounded mb-1 bg-light" id="reply-item-{{ $reply->id }}">
                                                                                                <div class="d-flex justify-content-between align-items-start">
                                                                                                    <div>
                                                                                                        <strong>
                                                                                                            @if($reply->user)
                                                                                                                <a href="{{ url('admin/user-profile/'.$reply->user->id) }}" class="no-link-style">
                                                                                                                    {{ optional($reply->user)->name ?? 'Deleted user' }}
                                                                                                                </a>
                                                                                                            @else
                                                                                                                <span>
                                                                                                                    {{ optional($reply->user)->name ?? 'Deleted user' }}
                                                                                                                </span>
                                                                                                            @endif
                                                                                                        </strong>:
                                                                                                        <span id="reply-text-{{ $reply->id }}">{{ $reply->reply }}</span>

                                                                                                        @if ($reply->reports->count())
                                                                                                            <span class="badge badge-warning ml-2">Reports: {{ $reply->reports->count() }}</span>
                                                                                                        @endif

                                                                                                        {{-- Display parent info --}}
                                                                                                        <div class="text-muted small mt-1">
                                                                                                            @if ($reply->parent_id)
                                                                                                                @php
                                                                                                                    $parentUser = optional($reply->parent)->user;
                                                                                                                @endphp
                                                                                                                <em>↳ in reply to 
                                                                                                                    <strong>
                                                                                                                        {{ $parentUser ? $parentUser->name : 'deleted user' }}
                                                                                                                    </strong>
                                                                                                                </em>
                                                                                                            @else
                                                                                                                <em>↳ reply to original comment</em>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>

                                                                                                    <div class="ml-2">
                                                                                                        <a href="javascript:void(0);" class="btn btn-sm btn-primary edit-reply-btn" 
                                                                                                        data-id="{{ $reply->id }}" data-reply="{{ $reply->reply }}" title="Edit">
                                                                                                            <i class="fas fa-edit"></i>
                                                                                                        </a>
                                                                                                        <button type="button" class="btn btn-sm btn-danger delete-reply-btn" 
                                                                                                                data-id="{{ $reply->id }}" title="Delete" data-comment-id="{{ $comment->id }}">
                                                                                                            <i class="fas fa-trash-alt"></i>
                                                                                                        </button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </li>
                                                                                        @endforeach
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        @endif

                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <p class="text-muted mt-3">No comments available.</p>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <input type="submit" @if(!isset($lesson)) value="Save" @else value="Update" @endif class="btn btn-success float-right">
                                    </div>
                                </div>
                            </form>
                            <!-- Edit Reply Modal -->
                            <div class="modal fade" id="editReplyModal" tabindex="-1" role="dialog" aria-labelledby="editReplyModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form id="editReplyForm">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="editReplyId">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                        <h5 class="modal-title">Edit Reply</h5>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                        <div class="form-group">
                                            <label for="replyContent">Reply</label>
                                            <textarea name="content" id="replyContent" class="form-control" rows="4" required></textarea>
                                        </div>
                                        </div>
                                        <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Edit Comment Modal -->
                            <div class="modal fade" id="editCommentModal" tabindex="-1" role="dialog" aria-labelledby="editCommentModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form id="editCommentForm">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" id="editCommentId">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Comment</h5>
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="commentContent">Comment</label>
                                                    <textarea name="comment" id="commentContent" class="form-control" rows="4" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Toast Notification for Reply Update -->
                            <div class="toast-container position-fixed fixed-bottom p-3" style="right: 0; bottom: 0; z-index: 1080;">
                                <div id="replyUpdatedToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div class="toast-body">
                                        Reply updated successfully!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
                <!-- Toast -->
                <!-- /.col -->
            </div>
            <!-- /.row -->
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

    @section('scripts')
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var contentType = document.getElementById('content_type');
            var imageGroup = document.getElementById('image-group');
            contentType.addEventListener('change', function () {
                if (this.value === 'text_image') {
                    imageGroup.style.display = '';
                } else {
                    imageGroup.style.display = 'none';
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            var editForm = $('#editReplyForm');

            $('.edit-reply-btn').on('click', function() {
                var replyId = $(this).data('id');
                var replyContent = $(this).data('reply');

                $('#editReplyId').val(replyId);
                $('#replyContent').val(replyContent);
                $('#editReplyModal').modal('show');
            });

            editForm.on('submit', function(e) {
                e.preventDefault();

                var replyId = $('#editReplyId').val();
                var content = $('#replyContent').val();
                var token = $('input[name="_token"]').val();

                $.ajax({
                    url: '/admin/comment-reply-edit/' + replyId,
                    type: 'POST',
                    data: {
                        _token: token,
                        _method: 'PUT',
                        content: content
                    },
                    success: function(response) {
                        $('#editReplyModal').modal('hide');
                        $('#replyUpdatedToast').toast({ delay: 2000 });
                        $('#replyUpdatedToast').toast('show');

                        $('#reply-text-' + replyId).text(content);

                        // Optionally, update the data-reply attribute for future edits
                        $('.edit-reply-btn[data-id="' + replyId + '"]').data('reply', content);
                    },
                    error: function(xhr) {
                        alert('Something went wrong. Please try again.');
                    }
                });
            });

            $('.delete-reply-btn').on('click', function () {
                var replyId = $(this).data('id');
                const commentId = $(this).data('comment-id');
                var token = $('input[name="_token"]').val();

                if (confirm('Are you sure you want to delete this reply?')) {
                    $.ajax({
                        url: '/admin/comment-reply-delete/' + replyId,
                        type: 'POST',
                        data: {
                            _token: token,
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            const replyList = $('#reply-item-' + replyId).closest('ul');
    
                            // Remove the reply from the DOM
                            $('#reply-item-' + replyId).remove();

                            // Count remaining replies
                            const remainingReplies = replyList.children('li').length;
                            
                            if (remainingReplies === 0) {
                                // If no replies left, hide the whole replies section
                                $('#comment-replies-wrapper-' + commentId).remove();
                            } else {
                                // Update reply count in the button text
                                $('#reply-count-' + commentId).text(remainingReplies);
                            }

                            // Update and show the toast
                            $('#replyUpdatedToast .toast-body').text('Reply deleted successfully!');
                            $('#replyUpdatedToast').toast({ delay: 2000 }).toast('show');
                        },
                        error: function () {
                            alert('Something went wrong while deleting the reply.');
                        }
                    });
                }
            });

            // Comment Edit
            $('.edit-comment-btn').on('click', function () {
                var commentId = $(this).data('id');
                var commentContent = $(this).data('comment');

                $('#editCommentId').val(commentId);
                $('#commentContent').val(commentContent);
                $('#editCommentModal').modal('show');
            });

            $('#editCommentForm').on('submit', function (e) {
                e.preventDefault();

                var commentId = $('#editCommentId').val();
                var content = $('#commentContent').val();
                var token = $('input[name="_token"]').val();

                $.ajax({
                    url: '/admin/comment-edit/' + commentId,
                    type: 'POST',
                    data: {
                        _token: token,
                        _method: 'PUT',
                        comment: content
                    },
                    success: function (response) {
                        $('#editCommentModal').modal('hide');
                        $('#comment-text-' + commentId).text(content);
                        $('.edit-comment-btn[data-id="' + commentId + '"]').data('comment', content);

                        $('#replyUpdatedToast .toast-body').text('Comment updated successfully!');
                        $('#replyUpdatedToast').toast({ delay: 2000 }).toast('show');
                    },
                    error: function () {
                        alert('Something went wrong while updating the comment.');
                    }
                });
            });

            // Comment Delete
            $('.delete-comment-btn').on('click', function () {
                var commentId = $(this).data('id');
                var token = $('input[name="_token"]').val();

                if (confirm('Are you sure you want to delete this comment?')) {
                    $.ajax({
                        url: '/admin/comment-delete/' + commentId,
                        type: 'POST',
                        data: {
                            _token: token,
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            $('#comment-item-' + commentId).remove();

                            // Update comment count
                            const newCount = $('.list-group-flush > li').length;
                            $('h4.card-title span').text('Comments (' + newCount + ')');

                            // Optionally, hide the whole comment block if empty
                            if (newCount === 0) {
                                $('#commentsCollapse').remove();
                                $('.card-title button').remove(); // remove toggle
                            }

                            $('#replyUpdatedToast .toast-body').text('Comment deleted successfully!');
                            $('#replyUpdatedToast').toast({ delay: 2000 }).toast('show');
                        },
                        error: function () {
                            alert('Something went wrong while deleting the comment.');
                        }
                    });
                }
            });

        });
    </script>

    @endsection
</x-admin-layout>
