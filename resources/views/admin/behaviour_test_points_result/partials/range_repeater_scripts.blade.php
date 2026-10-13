<script>
    $(document).ready(function() {
        const $form = $('#pointsResultForm');
        const $repeaterRoot = $('.repeater');

        function clampRangeValue(value) {
            if (value === '' || value === null || typeof value === 'undefined') {
                return '';
            }

            const parsed = parseInt(value, 10);
            if (isNaN(parsed)) {
                return '';
            }

            return Math.max(0, Math.min(100, parsed));
        }

        function getRangeItems() {
            return $repeaterRoot.find('[data-repeater-item]');
        }

        function clearRangeErrors() {
            $('.range-item-error, #rangesGlobalError').text('');
        }

        function setFirstRowStartReadonly() {
            const $items = getRangeItems();
            $items.each(function(index) {
                const $startInput = $(this).find('.range-start-input');
                if (index === 0) {
                    $startInput.val(0).prop('readonly', true);
                } else {
                    $startInput.prop('readonly', false);
                }
            });
        }

        function syncNextStartFromPrevious($currentItem) {
            const $items = getRangeItems();
            const currentIndex = $items.index($currentItem);

            if (currentIndex < 0 || currentIndex >= $items.length - 1) {
                return;
            }

            const endValue = clampRangeValue($currentItem.find('.range-end-input').val());
            const $nextItem = $items.eq(currentIndex + 1);
            const $nextStart = $nextItem.find('.range-start-input');

            if (endValue === '') {
                return;
            }

            $nextStart.val(endValue + 1);
        }

        function syncAllRangeStarts() {
            const $items = getRangeItems();
            $items.each(function(index) {
                if (index === 0) {
                    $(this).find('.range-start-input').val(0);
                    return;
                }

                const previousEnd = clampRangeValue($items.eq(index - 1).find('.range-end-input').val());
                if (previousEnd !== '') {
                    $(this).find('.range-start-input').val(previousEnd + 1);
                }
            });
        }

        function validateRanges(showAlerts) {
            clearRangeErrors();
            const $items = getRangeItems();
            let isValid = true;
            const ranges = [];

            if ($items.length === 0) {
                $('#rangesGlobalError').text('Please add at least one result range.');
                return false;
            }

            $items.each(function(index) {
                const $item = $(this);
                const start = clampRangeValue($item.find('.range-start-input').val());
                const end = clampRangeValue($item.find('.range-end-input').val());
                const resultText = $.trim($item.find('.result-text-input').val());
                const row = index + 1;
                let rowError = '';
                console.log(start , end, resultText);
                if (start === '' || end === '' || resultText === '') {
                    rowError = 'Please complete all fields in this row.';
                    isValid = false;
                } else if (start > end) {
                    rowError = 'End range must be greater than or equal to start range.';
                    isValid = false;
                }

                if (!rowError && index === 0 && start !== 0) {
                    rowError = 'The first range must start at 0%.';
                    isValid = false;
                }

                if (!rowError && index > 0) {
                    const expectedStart = ranges[index - 1].end + 1;
                    if (start !== expectedStart) {
                        rowError = 'Start range must be ' + expectedStart + '% (previous end + 1).';
                        isValid = false;
                    }
                }

                if (rowError) {
                    $item.find('.range-item-error').text(rowError);
                }

                ranges.push({
                    start: start,
                    end: end,
                    resultText: resultText
                });
            });

            if (isValid && ranges.length > 0) {
                const lastRange = ranges[ranges.length - 1];
                if (lastRange.end !== 100) {
                    $('#rangesGlobalError').text('The last range must end at 100%.');
                    isValid = false;
                }
            }

            if (!isValid && showAlerts) {
                const firstError = $('.range-item-error').filter(function() {
                    return $.trim($(this).text()) !== '';
                }).first().text() || $('#rangesGlobalError').text();

                if (firstError) {
                    alert(firstError);
                }
            }

            return isValid;
        }

        function initializeRangeItem($item) {
            $item.find('.range-start-input, .range-end-input').attr({
                min: 0,
                max: 100,
                step: 1
            });
        }

        $repeaterRoot.repeater({
            initEmpty: false,
            isFirstItemUndeletable: true,
            show: function() {
                const $item = $(this);
                $item.slideDown(function() {
                    initializeRangeItem($item);
                    syncAllRangeStarts();
                    setFirstRowStartReadonly();
                });
            },
            hide: function(deleteElement) {
                if (getRangeItems().length <= 1) {
                    alert('At least one range is required.');
                    return;
                }

                if (confirm('Are you sure you want to delete this range?')) {
                    $(this).slideUp(deleteElement, function() {
                        syncAllRangeStarts();
                        setFirstRowStartReadonly();
                        validateRanges(false);
                    });
                }
            }
        });

        getRangeItems().each(function() {
            initializeRangeItem($(this));
        });

        setFirstRowStartReadonly();
        syncAllRangeStarts();

        $repeaterRoot.on('input change', '.range-end-input', function() {
            const $item = $(this).closest('[data-repeater-item]');
            const clamped = clampRangeValue($(this).val());
            $(this).val(clamped);
            syncNextStartFromPrevious($item);
            validateRanges(false);
        });

        $repeaterRoot.on('input change', '.range-start-input', function() {
            const $input = $(this);
            const $item = $input.closest('[data-repeater-item]');
            const index = getRangeItems().index($item);

            if (index === 0) {
                $input.val(0);
                return;
            }

            $input.val(clampRangeValue($input.val()));
            validateRanges(false);
        });

        $form.on('submit', function(e) {
            syncAllRangeStarts();
            setFirstRowStartReadonly();

            if (!validateRanges(true)) {
                e.preventDefault();
            }
        });
    });
</script>
