// Bundles the third-party browser libraries that were previously loaded from
// CDNs in index.html. Loading them locally makes the app work regardless of
// network/CDN availability (fixes the device-specific "Can't find variable:
// jQuery / google" crashes).
//
// Order matters: jQuery must be on the global scope before its plugins load.
// './jquery-global' is a separate module so its body is fully evaluated before
// the plugin modules below (ES module dependencies evaluate depth-first, in
// import order).
import './jquery-global';

// DataTables core + responsive extension (versions pinned to match the former
// CDN: datatables.net 2.3.5, responsive 3.0.3). Their module bodies run
// `$.fn.dataTable = $.fn.DataTable = ...` on import.
import 'datatables.net';
import 'datatables.net-responsive';

// select2 (4.1.0). Under the Webpack build the UMD factory runs at import time
// (webpack invokes it with the bundled jQuery), which registers $.fn.select2.
// So a plain side-effect import is exactly right here — do NOT call the module
// export, because webpack returns the Select2 *constructor*, and invoking it
// with no element throws "Cannot read properties of undefined (reading '0')".
import 'select2';
