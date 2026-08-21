// Load bundled third-party libs (jQuery, DataTables, select2) onto the global
// scope before Angular bootstraps, so every page's global usage is available
// without depending on runtime CDN availability.
import './vendor';

import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';

import { AppModule } from './app/app.module';

platformBrowserDynamic().bootstrapModule(AppModule)
  .catch(err => console.log(err));
