{{--
    Expedited Pay — Phase 1 UI + SweetAlert confirmation.
    Include just before the submit button(s) in every recap form.

    Variables (optional):
      $existingValue  — boolean, pre-fill checkbox for edit forms (default false)
      $submitBtnId    — id to give the primary submit button (default 'recapSubmitBtn')
--}}
@php
    $existingValue = $existingValue ?? false;
    $submitBtnId   = $submitBtnId   ?? 'recapSubmitBtn';
@endphp

<div class="mb-3 mt-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox"
               name="is_expedited_requested"
               id="is_expedited_requested"
               value="1"
               {{ old('is_expedited_requested', $existingValue) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_expedited_requested">
            Request Expedited Pay <strong>(+$2.50 fee)</strong>
        </label>
    </div>
    <small class="text-muted">
        A $2.50 processing fee will be deducted from your payout if expedited pay is requested.
    </small>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
    var btn = document.getElementById('{{ $submitBtnId }}');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        var checkbox = document.getElementById('is_expedited_requested');
        if (!checkbox || !checkbox.checked) return; // no confirmation needed
        e.preventDefault();
        Swal.fire({
            title: 'Confirm Expedited Pay',
            text: 'A $2.50 processing fee will be deducted from your payout. Do you want to continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, request it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then(function (result) {
            if (result.isConfirmed) {
                var form = btn.form || btn.closest('form');
                if (form) form.submit();
            }
        });
    });
})();
</script>
@endpush
