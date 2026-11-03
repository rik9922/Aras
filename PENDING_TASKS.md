# Pending Tasks

## Invoice Module

### Customer Monthly Summary (Pending)

**Status:** Pending until Invoice Creation is completed.

### Reason

The client requested these statistics under the **Invoice Tab**, not the
**Bill To** module.

Requested metrics:

-   Submitted
-   Not Recapped
-   No Shows
-   Late
-   Cancelled

### Current Decision

Do **not** implement this in the Bill To module.

Complete the following first:

1.  Event Invoicing Queue
2.  Invoice Review Screen
3.  Manual Invoice Creation
4.  QuickBooks Invoice Generation

After the invoice workflow is complete:

-   Revisit the Invoice Tab.
-   Compare the current implementation with the client's expectations.
-   Decide whether the monthly customer summary belongs in the Invoice
    module.
-   If needed, implement the summary there instead of the Bill To
    module.

------------------------------------------------------------------------

**Reason for postponing**

The exact placement of the monthly summary depends on the final Invoice
workflow. Implementing it now may require unnecessary rework.
