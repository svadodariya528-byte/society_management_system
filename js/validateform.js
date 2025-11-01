$(document).ready(function () {
    $("form").on("submit", function (e) {
        let isValid = true;
        let form = $(this);

        // Reset
        form.find(".is-invalid").removeClass("is-invalid");
        form.find(".is-valid").removeClass("is-valid");
        form.find(".invalid-feedback").remove();

        form.find("input, select, textarea").each(function () {
            let input = $(this);
            let rules = input.data("validate");
            let value = input.val().trim();
            let fieldValid = true;

            if (rules) {
                let ruleList = rules.split("|");

                ruleList.forEach(function (rule) {
                    let parts = rule.split(":");
                    let ruleName = parts[0];
                    let param = parts[1] || null;

                    // Required
                    if (ruleName === "required" && value === "") {
                        fieldValid = false;
                        showError(input, "This field is required.");
                    }

                    // Email
                    if (ruleName === "email" && value !== "") {
                        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            fieldValid = false;
                            showError(input, "Invalid email address.");
                        }
                    }

                    // Number
                    if (ruleName === "number" && value !== "") {
                        if (isNaN(value)) {
                            fieldValid = false;
                            showError(input, "Must be a number.");
                        }
                    }

                    // Min length
                    if (ruleName === "minlength" && value !== "") {
                        if (value.length < parseInt(param)) {
                            fieldValid = false;
                            showError(input, "At least " + param + " characters.");
                        }
                    }

                    // Max length
                    if (ruleName === "maxlength" && value !== "") {
                        if (value.length > parseInt(param)) {
                            fieldValid = false;
                            showError(input, "At most " + param + " characters.");
                        }
                    }

                    // Min value
                    if (ruleName === "min" && value !== "") {
                        if (parseFloat(value) < parseFloat(param)) {
                            fieldValid = false;
                            showError(input, "Minimum value is " + param + ".");
                        }
                    }

                    // Max value
                    if (ruleName === "max" && value !== "") {
                        if (parseFloat(value) > parseFloat(param)) {
                            fieldValid = false;
                            showError(input, "Maximum value is " + param + ".");
                        }
                    }

                    // Date
                    if (ruleName === "date" && value !== "") {
                        let dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                        if (!dateRegex.test(value)) {
                            fieldValid = false;
                            showError(input, "Date must be YYYY-MM-DD.");
                        }
                    }
                });
            }

            // Add green/red highlight
            if (fieldValid) {
                input.addClass("is-valid");
            } else {
                isValid = false;
                input.addClass("is-invalid");
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
        return isValid;
    });

    function showError(input, message) {
        if (!input.next(".invalid-feedback").length) {
            input.after('<div class="invalid-feedback">' + message + '</div>');
        }
    }
});
