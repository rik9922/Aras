{{--
    Event Form - Validation UI (Bootstrap 5)
    Zero Blade expressions inside <script> — avoids all Blade/PHP parse
    errors when a partial is included inside @push('scripts').
    PHP state is read from data-has-errors="1" on the form element.
--}}
<style>
    label.field-required::after {
        content: ' *';
        color: #dc3545;
        font-weight: bold;
    }
    select.is-invalid ~ .select2-container .select2-selection {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
    }
    input.is-invalid ~ input.flatpickr-input.form-control {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
    }
    .is-invalid ~ .invalid-feedback,
    .is-invalid + .invalid-feedback {
        display: block;
    }
</style>
<script>
(function () {
    'use strict';

    function clearIfValid(el) {
        var val = '';
        if (el.tagName === 'SELECT') {
            if (el.multiple) {
                val = Array.from(el.options).some(function (o) {
                    return o.selected && o.value !== '';
                }) ? '1' : '';
            } else {
                val = el.value || '';
            }
        } else {
            val = (el.value || '').trim();
        }
        if (!val) return;

        el.classList.remove('is-invalid');

        var parent = el.closest('.mb-3, .col-md-4, .col-md-6, .col-md-12, .col-12');
        if (parent) {
            var fb = parent.querySelector('.invalid-feedback');
            if (fb) fb.style.display = 'none';
        }

        /* Select2 */
        var s2 = el.nextElementSibling;
        while (s2 && !s2.classList.contains('select2-container')) {
            s2 = s2.nextElementSibling;
        }
        if (!s2 && parent) s2 = parent.querySelector('.select2-container');
        if (s2) {
            var sel = s2.querySelector('.select2-selection');
            if (sel) { sel.style.borderColor = ''; sel.style.boxShadow = ''; }
        }

        /* Flatpickr */
        if (el._flatpickr && el._flatpickr.altInput) {
            el._flatpickr.altInput.classList.remove('is-invalid');
            el._flatpickr.altInput.style.borderColor = '';
            el._flatpickr.altInput.style.boxShadow = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('eventSaveForm');
        if (!form) return;

        /* Apply Select2 red borders for any .is-invalid selects on page load */
        form.querySelectorAll('select.is-invalid').forEach(function (sel) {
            var s2 = sel.nextElementSibling;
            while (s2 && !s2.classList.contains('select2-container')) {
                s2 = s2.nextElementSibling;
            }
            if (!s2) {
                var w = sel.closest('.mb-3,.col-md-4,.col-md-6,.col-md-12,.col-12');
                if (w) s2 = w.querySelector('.select2-container');
            }
            if (s2) {
                var s = s2.querySelector('.select2-selection');
                if (s) {
                    s.style.borderColor = '#dc3545';
                    s.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,.25)';
                }
            }
        });

        /* Apply Flatpickr red borders on page load */
        form.querySelectorAll('input.is-invalid').forEach(function (inp) {
            if (inp._flatpickr && inp._flatpickr.altInput) {
                inp._flatpickr.altInput.style.borderColor = '#dc3545';
                inp._flatpickr.altInput.style.boxShadow = '0 0 0 0.2rem rgba(220,53,69,.25)';
            }
        });

        /* Only attach clear-on-change if server returned errors
           (detected via data-has-errors="1" on the form — no Blade in JS) */
        if (form.dataset.hasErrors !== '1') return;

        form.querySelectorAll('input.is-invalid, textarea.is-invalid').forEach(function (el) {
            el.addEventListener('change', function () { clearIfValid(el); });
            if (el.type !== 'hidden') {
                el.addEventListener('input', function () { clearIfValid(el); });
            }
        });

        form.querySelectorAll('select.is-invalid').forEach(function (el) {
            el.addEventListener('change', function () { clearIfValid(el); });
        });

        /* Re-attach after Select2 finishes initialising */
        setTimeout(function () {
            form.querySelectorAll('select.is-invalid').forEach(function (el) {
                if (window.$ && $(el).data('select2')) {
                    $(el).off('select2:select.evClear select2:unselect.evClear')
                         .on('select2:select.evClear select2:unselect.evClear', function () {
                             clearIfValid(el);
                         });
                }
            });
        }, 600);
    });
}());
</script>
