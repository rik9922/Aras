(function ($) {
    'use strict';

    const cfg = window.AmbassadorStaffingConfig || {};
    let maxAmbassadors = 0;
    let removedAmbassadorIds = [];

    function eventPinataEnabled() {
        return $('#pinata_pay').is(':checked');
    }

    function syncMaxAmbassadorsFromPosition() {
        const v = $(cfg.positionSelector || '#selectPosition').val();
        maxAmbassadors = /^\d+$/.test(String(v ?? '')) ? parseInt(v, 10) : 0;
    }

    function rowPayload($row) {
        return {
            assign_id: parseInt($row.data('assign-id'), 10) || null,
            ambassador_id: parseInt($row.data('ambassador-id'), 10),
            status: String($row.data('assigned')) === '1' ? '1' : '0',
            is_no_show: $row.find('.staffing-no-show').is(':checked'),
            has_pinata: $row.find('.staffing-pinata').is(':checked'),
            override_pay: parseInt($row.data('override-pay'), 10) || 0,
            additional_pay_reason: String($row.data('additional-pay-reason') || ''),
            additional_pay_note: String($row.data('additional-pay-note') || ''),
        };
    }

    function syncHiddenFields() {
        const rows = [];
        const selectedIds = [];

        $('#ambassadorStaffingBody .ambassador-staffing-row').each(function () {
            const $row = $(this);
            const ambassadorId = parseInt($row.data('ambassador-id'), 10);
            if (!ambassadorId) {
                return;
            }
            rows.push(rowPayload($row));
            if (String($row.data('assigned')) === '1') {
                selectedIds.push(ambassadorId);
            }
        });

        $('#ambassadorStaffingData').val(JSON.stringify(rows));

        const overridePayMap = {};
        const reasonMap = {};
        const noteMap = {};
        rows.forEach((r) => {
            overridePayMap[r.ambassador_id] = r.override_pay;
            reasonMap[r.ambassador_id] = r.additional_pay_reason;
            noteMap[r.ambassador_id] = r.additional_pay_note;
        });

        $('#selectedAmbassadorId').val(selectedIds.join(','));
        $('#overridePayData').val(JSON.stringify(overridePayMap));
        $('#additionalPayReasonData').val(JSON.stringify(reasonMap));
        $('#additionalPayNoteData').val(JSON.stringify(noteMap));
        $('#removeAmbassadorIds').val(removedAmbassadorIds.join(','));
    }

    function buildRowHtml(data) {
        const assigned = !!data.assigned;
        const overbooked = !!data.overbooked;
        const profileImage = data.profile_image || '';
        const assignId = data.assign_id ? ` data-assign-id="${data.assign_id}"` : '';
        const assignBtn = assigned
            ? ''
            : '<button type="button" class="btn btn-sm btn-primary staffing-assign-btn">Assign</button>';

        return `<tr class="ambassador-staffing-row"
            ${assignId}
            data-ambassador-id="${data.ambassador_id}"
            data-override-pay="${data.override_pay || 0}"
            data-additional-pay-reason="${data.additional_pay_reason || ''}"
            data-additional-pay-note="${data.additional_pay_note || ''}"
            data-assigned="${assigned ? '1' : '0'}"
            data-overbooked="${overbooked ? '1' : '0'}"
            data-wage="${data.wage || '0.00'}"
            data-name="${data.name || ''}"
            data-email="${data.email || ''}"
            data-phone="${data.phone || ''}"
            data-profile-image="${profileImage}">
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${profileImage}" alt="" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                    <div class="small">
                        <div class="fw-semibold">${data.name || '-'}</div>
                        <div class="text-muted">${data.email || ''}</div>
                        <div class="text-muted">${data.phone || ''}</div>
                    </div>
                </div>
            </td>
            <td class="staffing-assigned-label">${assigned ? 'Yes' : 'No'}</td>
            <td>${data.wage || '0.00'}</td>
            <td class="staffing-overbooked-label ${overbooked ? 'text-danger fw-semibold' : ''}">${overbooked ? 'Yes' : 'No'}</td>
            <td>
                <div class="d-flex flex-column gap-1 small">
                    <label class="form-check mb-0">
                        <input type="checkbox" class="form-check-input staffing-no-show" ${data.is_no_show ? 'checked' : ''}>
                        <span class="form-check-label text-uppercase">No Show</span>
                    </label>
                    <label class="form-check mb-0">
                        <input type="checkbox" class="form-check-input staffing-pinata" ${data.has_pinata ? 'checked' : ''}>
                        <span class="form-check-label text-uppercase">Pinata</span>
                    </label>
                </div>
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    ${assignBtn}
                    <button type="button" class="btn btn-sm btn-danger staffing-remove-btn">Remove</button>
                </div>
            </td>
        </tr>`;
    }

    function markRowAssigned($row) {
        $row.attr('data-assigned', '1');
        $row.find('.staffing-assigned-label').text('Yes');
        $row.find('.staffing-assign-btn').remove();
        if (eventPinataEnabled() && !$row.find('.staffing-pinata').is(':checked')) {
            $row.find('.staffing-pinata').prop('checked', true);
        }
        syncHiddenFields();
    }

    function initSearch() {
        $('#searchAmbassador').on('keyup', function () {
            const searchValue = $(this).val();
            if (String(searchValue).length <= 1) {
                $('#searchResults').addClass('d-none').html('');
                return;
            }

            $.ajax({
                url: cfg.searchUrl,
                type: 'POST',
                data: {
                    search: searchValue,
                    _token: cfg.csrfToken,
                    age_restricted: $(cfg.ageRestrictedSelector || '#ageRestrictedCheckbox').is(':checked'),
                },
                success: function (response) {
                    let resultHtml = '<ul class="list-group w-100 mb-0 position-absolute shadow-sm">';
                    if (response.data && response.data.length > 0) {
                        response.data.forEach((ambassador) => {
                            const name = `${ambassador.first_name || ''} ${ambassador.last_name || ''}`.trim();
                            resultHtml += `<li style="cursor:pointer;" class="list-group-item ambassador-item"
                                data-id="${ambassador.id}"
                                data-name="${name}"
                                data-email="${ambassador.email || ''}"
                                data-phone="${ambassador.phone || ''}"
                                data-wage="${ambassador.wage || '0.00'}"
                                data-profile="${ambassador.profile_image || ''}">
                                ${name} - ${ambassador.email || ''}
                            </li>`;
                        });
                    } else {
                        resultHtml += '<li class="list-group-item">No results found</li>';
                    }
                    resultHtml += '</ul>';
                    $('#searchResults').html(resultHtml).removeClass('d-none');
                },
            });
        });

        $(document).on('click', '.ambassador-item', function () {
            const $el = $(this);
            $('#searchAmbassador').val($el.data('name'));
            $('#searchResults').addClass('d-none').html('');
            $('#searchAmbassador').data('selectedAmbassador', {
                id: $el.data('id'),
                name: $el.data('name'),
                email: $el.data('email'),
                phone: $el.data('phone'),
                wage: $el.data('wage'),
                profile_image: $el.data('profile'),
            });
        });
    }

    $('#addAmbassador').on('click', function () {
        syncMaxAmbassadorsFromPosition();
        const selected = $('#searchAmbassador').data('selectedAmbassador');
        if (!selected || !selected.id) {
            alert('Please select an ambassador first.');
            return;
        }

        const currentCount = $('#ambassadorStaffingBody tr').length;
        if (maxAmbassadors === 0) {
            alert('Please set positions (how many ambassadors) first.');
            $(cfg.positionSelector || '#selectPosition').trigger('focus');
            return;
        }
        if (currentCount >= maxAmbassadors) {
            alert(`You can only add up to ${maxAmbassadors} ambassador(s).`);
            return;
        }

        const selId = String(selected.id);
        let exists = false;
        $('#ambassadorStaffingBody tr').each(function () {
            if (String($(this).data('ambassador-id')) === selId) {
                exists = true;
            }
        });
        if (exists) {
            alert('This ambassador is already on the list.');
            return;
        }

        const overridePay = $('#overridePay').val() || 0;
        const reason = $('#additionalPayReason').val() || '';
        const note = $('#additionalPayNote').val() || '';
        const hasPinata = eventPinataEnabled();

        const rowData = {
            ambassador_id: selected.id,
            name: selected.name,
            email: selected.email,
            phone: selected.phone,
            wage: parseFloat(selected.wage || 0).toFixed(2),
            profile_image: selected.profile_image || '',
            assigned: true,
            overbooked: false,
            is_no_show: false,
            has_pinata: hasPinata,
            override_pay: overridePay,
            additional_pay_reason: reason,
            additional_pay_note: note,
        };

        $('#ambassadorStaffingBody').append(buildRowHtml(rowData));
        $('#overridePay').val('');
        $('#additionalPayReason').val('');
        $('#additionalPayNote').val('');
        $('#searchAmbassador').val('').removeData('selectedAmbassador');
        syncHiddenFields();
    });

    $(document).on('click', '.staffing-assign-btn', function () {
        const $row = $(this).closest('tr');
        const assignId = parseInt($row.data('assign-id'), 10);

        if (!assignId || !cfg.assignUrl) {
            markRowAssigned($row);
            return;
        }

        $.ajax({
            url: cfg.assignUrl,
            method: 'POST',
            data: {
                _token: cfg.csrfToken,
                event_id: assignId,
            },
            success: function () {
                markRowAssigned($row);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Could not assign ambassador.');
            },
        });
    });

    $(document).on('click', '.staffing-remove-btn', function () {
        const $row = $(this).closest('tr');
        const assignId = parseInt($row.data('assign-id'), 10);
        const ambassadorId = parseInt($row.data('ambassador-id'), 10);

        const removeRow = function () {
            if (ambassadorId) {
                removedAmbassadorIds.push(ambassadorId);
            }
            $row.remove();
            syncHiddenFields();
        };

        if (assignId && cfg.removeUrl && cfg.eventId) {
            $.ajax({
                url: cfg.removeUrl,
                method: 'POST',
                data: {
                    _token: cfg.csrfToken,
                    assignment_id: assignId,
                },
                success: removeRow,
                error: function (xhr) {
                    alert(xhr.responseJSON?.message || 'Could not remove ambassador.');
                },
            });
            return;
        }

        removeRow();
    });

    $('#pinata_pay').on('change', function () {
        const checked = $(this).is(':checked');
        $('#ambassadorStaffingBody .staffing-pinata').prop('checked', checked);
        syncHiddenFields();
    });

    $(document).on('change', '.staffing-no-show, .staffing-pinata', syncHiddenFields);

    $('form').on('submit', function () {
        syncHiddenFields();
    });

    function hydrateOldRows() {
        const oldRows = cfg.oldStaffingRows || [];
        if (!oldRows.length) {
            return;
        }
        oldRows.forEach((row) => {
            $('#ambassadorStaffingBody').append(buildRowHtml({
                ambassador_id: row.ambassador_id,
                name: row.name || '',
                email: row.email || '',
                phone: row.phone || '',
                wage: row.wage || '0.00',
                profile_image: row.profile_image || '',
                assigned: row.status === '1' || row.status === 1 || row.assigned,
                overbooked: !!row.overbooked,
                is_no_show: !!row.is_no_show,
                has_pinata: !!row.has_pinata,
                override_pay: row.override_pay || 0,
                additional_pay_reason: row.additional_pay_reason || '',
                additional_pay_note: row.additional_pay_note || '',
            }));
        });
    }

    $(document).ready(function () {
        syncMaxAmbassadorsFromPosition();
        $(cfg.positionSelector || '#selectPosition').on('input change', syncMaxAmbassadorsFromPosition);
        initSearch();
        if ($('#ambassadorStaffingBody tr').length === 0) {
            hydrateOldRows();
        }
        syncHiddenFields();
    });
})(jQuery);
