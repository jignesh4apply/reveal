// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
    'use strict';
    window.addEventListener('load', function () {
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.getElementsByClassName('needs-validation');
        // Loop over them and prevent submission
        var validation = Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Update User Status
$(document).on("click", ".updateUserStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var user_id = $(this).attr("user_id");
    // alert(status);
    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-user-status",
        data: { status: status, user_id: user_id },
        success: function (resp) {
            console.log(resp);
            if (resp["status"] == 0) {
                $("#user-" + user_id).html(
                    "<span class='badge badge-warning' status='Blocked'>Blocked</span>"
                );

                var message = "User status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#user-" + user_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "User status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Category Status
$(document).on("click", ".updateCategoryStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var category_id = $(this).attr("category_id");
    // alert(status);
    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-category-status",
        data: { status: status, category_id: category_id },
        success: function (resp) {
            console.log(resp);
            if (resp["status"] == 0) {
                $("#category-" + category_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Category status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#category-" + category_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Category status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Program Status
$(document).on("click", ".updateProgramStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var program_id = $(this).attr("program_id");
    // alert(status);
    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-program-status",
        data: { status: status, program_id: program_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#program-" + program_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Program status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#program-" + program_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Program status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Session Status
$(document).on("click", ".updateSessionStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var session_id = $(this).attr("session_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-session-status",
        data: { status: status, session_id: session_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#session-" + session_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Session status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#session-" + session_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Session status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Recipe Category Status
$(document).on("click", ".updateRecipeNutritionStatus", function () {
    var status = $(this).children("span").attr("status");
    var nutrition_id = $(this).attr("nutrition_id");
    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-recipe-nutrition-status",
        data: { status: status, nutrition_id: nutrition_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#nutrition-" + nutrition_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Recipe Nutrition status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#nutrition-" + nutrition_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Recipe Nutrition status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Recipe Status
$(document).on("click", ".updateRecipeStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var recipe_id = $(this).attr("recipe_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-recipe-status",
        data: { status: status, recipe_id: recipe_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#recipe-" + recipe_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Recipe status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#recipe-" + recipe_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Recipe status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Lesson Status
$(document).on("click", ".updateLessonStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var lesson_id = $(this).attr("lesson_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-lesson-status",
        data: { status: status, lesson_id: lesson_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#lesson-" + lesson_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Lesson has been blocked.";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#lesson-" + lesson_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Lesson has been active now.";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Exercise Status
$(document).on("click", ".updateExerciseStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var exercise_id = $(this).attr("exercise_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-exercise-status",
        data: { status: status, exercise_id: exercise_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#exercise-" + exercise_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Exercise status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#exercise-" + exercise_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Exercise status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

$(document).on("click", ".updateChallengeExerciseStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var exercise_id = $(this).attr("exercise_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/challenge-update-exercise-status",
        data: { status: status, exercise_id: exercise_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#exercise-" + exercise_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Exercise status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#exercise-" + exercise_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Exercise status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Cardio Status
$(document).on("click", ".updateCardioStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var cardio_id = $(this).attr("cardio_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-cardio-status",
        data: { status: status, cardio_id: cardio_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#cardio-" + cardio_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Cardio status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#cardio-" + cardio_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Cardio status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Muscle Strengthening Status
$(document).on("click", ".updateMuscleStrngthStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var muscle_id = $(this).attr("muscle_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-muscles-strength-status",
        data: { status: status, muscle_id: muscle_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#muscle-" + muscle_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Muscle Strengthening status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#muscle-" + muscle_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Muscle Strengthening status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

// Update Meal Type Status
$(document).on("click", ".updateMealTypeStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var meal_type_id = $(this).attr("meal_type_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-meal-type-status",
        data: { status: status, meal_type_id: meal_type_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#meal_type-" + meal_type_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Meal Type status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#meal_type-" + meal_type_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Meal Type status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});
// Update Meal Type Status
$(document).on("click", ".updateChallengeLevelStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var level_id = $(this).attr("level_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-challengeLevel-status",
        data: { status: status, level_id: level_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#level-" + level_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Challenge Level status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#level-" + level_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Challenge Level status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

$(document).on("click", ".updateChallengeStatus", function () {
    // alert("test");
    var status = $(this).children("span").attr("status");
    var challenge_id = $(this).attr("challenge_id");

    $.ajax({
        headers: {
            "X-CSRF-TOKEN": $('meta[name = "csrf-token"]').attr("content"),
        },
        type: "post",
        url: "/admin/update-challenge-status",
        data: { status: status, challenge_id: challenge_id },
        success: function (resp) {
            if (resp["status"] == 0) {
                $("#challenge-" + challenge_id).html(
                    "<span class='badge badge-warning' status='Inactive'>Inactive</span>"
                );

                var message = "Challenge status is blocked";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "error",
                    title: message,
                    background: 'grey',
                });
            } else if (resp["status"] == 1) {
                $("#challenge-" + challenge_id).html(
                    "<span class='badge badge-success' status='Active'>Active</span>"
                );

                var message = "Challenge status is active";

                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener("mouseenter", Swal.stopTimer);
                        toast.addEventListener("mouseleave", Swal.resumeTimer);
                    },
                });

                Toast.fire({
                    icon: "success",
                    title: message,
                    background: 'grey',
                });
            }
        },
        error: function () {
            alert("Error");
        },
    });
});

$(document).on("click", ".confirmDelete", function () {

    var slug = $(this).attr("slug");
    var module = $(this).attr("module");
    var moduleId = $(this).attr("moduleId");
    var message = $(this).attr("message");

    Swal.fire({
        title: "Are you sure, you want to delete this record ? ",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener("mouseenter", Swal.stopTimer);
                    toast.addEventListener("mouseleave", Swal.resumeTimer);
                },
            });

            Toast.fire({
                icon: "success",
                title: message,
                background: 'grey',
            });
            if(module == 'user' || module == 'userLevel' || module == 'taskMilestone'){
                window.location = "/admin/delete-" + module + "/" + moduleId;
            } else {
                window.location = "/admin/delete-" + module + "/" + slug + "/" + moduleId;
            }
        }
    });
});
