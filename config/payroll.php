<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payroll Mode
    |--------------------------------------------------------------------------
    |
    | When expedited_mode is false (the default), recap approvals create the
    | AmbassadorPayout record but do NOT automatically trigger a Dwolla
    | transfer. The payout sits in 'approved' status and is picked up by
    | the Batch Payroll Generate + Process flow instead.
    |
    | Set PAYROLL_EXPEDITED_MODE=true to re-enable the automatic per-recap
    | Dwolla release — this is the path that will be used for Expedited
    | Recap payments in a future phase.
    |
    | Reimbursements are unaffected by this flag; they always follow their
    | own expedited flow.
    |
    */

    'expedited_mode' => env('PAYROLL_EXPEDITED_MODE', false),

];
