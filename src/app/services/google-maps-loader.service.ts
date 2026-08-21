import { Injectable } from '@angular/core';
import { importLibrary, setOptions } from '@googlemaps/js-api-loader';
import { environment } from 'src/environments/environment';

declare const google: any;

/**
 * Loads the Google Maps JavaScript API on demand.
 *
 * The Maps API cannot be bundled (it must be fetched from Google), so instead
 * of the old synchronous <script> tag in index.html (which crashed pages when
 * it failed to load), we load it lazily via the official loader and cache the
 * promise. Callers MUST await load() before touching the `google.maps.*`
 * global, and should handle rejection so the page still renders when Maps is
 * unavailable (e.g. offline).
 */
@Injectable({ providedIn: 'root' })
export class GoogleMapsLoaderService {
  private ready?: Promise<void>;

  load(): Promise<void> {
    if (!this.ready) {
      setOptions({ key: environment.googleMapsApiKey, v: 'weekly' });
      this.ready = Promise.all([
        importLibrary('maps'),
        importLibrary('places'),
        importLibrary('marker'),
        importLibrary('geocoding'),
      ])
        .then(() => undefined)
        .catch((err) => {
          // Clear the cached promise so a later call can retry once
          // connectivity is restored.
          this.ready = undefined;
          throw err;
        });
    }
    return this.ready;
  }

  /** True once the global `google.maps` namespace is available. */
  get isLoaded(): boolean {
    return typeof google !== 'undefined' && !!google?.maps;
  }
}
