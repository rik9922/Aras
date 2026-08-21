// Exposes jQuery on the global scope BEFORE any jQuery plugin (DataTables,
// select2) is imported. This file is imported first by vendor.ts so its
// top-level code runs before the plugin modules evaluate. The whole codebase
// uses the global `jQuery`/`$` (see the many `declare var jQuery: any;`),
// which previously came from a CDN <script> in index.html. Bundling it here
// removes that runtime CDN dependency.
import * as jqueryImport from 'jquery';

// Depending on the bundler's CommonJS interop, the callable jQuery function may
// be the module namespace itself or live on `.default`. Resolve to whichever is
// callable so `window.jQuery(...)` always works.
const jquery: any = (jqueryImport as any).default ?? jqueryImport;

(window as any).jQuery = jquery;
(window as any).$ = jquery;
