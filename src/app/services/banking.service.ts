import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { environment } from 'src/environments/environment';
import { StorageService } from './storage.service';
import { from, switchMap, map, catchError } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class BankingService {
  private baseUrl = environment.auth_url + 'ambassador/';

  // In-memory cache so the "Payment Setup Required" banner (rendered on every
  // page via the shared header) does not hit banking/status on every navigation.
  // null = unknown/not fetched yet. Reset via invalidateSetup().
  private setupComplete: boolean | null = null;

  constructor(private http: HttpClient, private storage: StorageService) {}

  private headers(): Observable<HttpHeaders> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        return new Observable<HttpHeaders>(obs => {
          obs.next(new HttpHeaders({ Authorization: 'Bearer ' + token }));
          obs.complete();
        });
      })
    );
  }

  getStatus(): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.get(this.baseUrl + 'banking/status', { headers });
      })
    );
  }

  /**
   * Returns whether banking setup is complete (verified), used to decide if the
   * "Payment Setup Required" banner should show. Result is cached for the
   * session; on any error we return true (complete) so we never nag on a
   * transient failure. Call invalidateSetup()/markSetupStatus() to refresh.
   */
  isSetupComplete(): Observable<boolean> {
    if (this.setupComplete !== null) {
      return of(this.setupComplete);
    }
    return this.getStatus().pipe(
      map((res: any) => {
        const data = res?.data ?? res?.payload ?? {};
        this.setupComplete = data.bank_setup_status === 'verified';
        return this.setupComplete;
      }),
      catchError(() => {
        this.setupComplete = true;
        return of(true);
      })
    );
  }

  /** Update the cached completion flag from a known status string. */
  markSetupStatus(status: string): void {
    this.setupComplete = status === 'verified';
  }

  /** Force the next isSetupComplete() call to re-fetch. */
  invalidateSetup(): void {
    this.setupComplete = null;
  }

  setup(payload: any): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.post(this.baseUrl + 'banking/setup', payload, { headers });
      })
    );
  }

  addBank(payload: any): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.post(this.baseUrl + 'banking/add', payload, { headers });
      })
    );
  }

  verifyBank(payload: any): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.post(this.baseUrl + 'banking/verify', payload, { headers });
      })
    );
  }

  setDefault(id: number): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.post(this.baseUrl + `banking/set-default/${id}`, {}, { headers });
      })
    );
  }

  removeBank(id: number): Observable<any> {
    return from(this.storage.get('apro_user')).pipe(
      switchMap((user: any) => {
        const token = user?.token ?? '';
        const headers = new HttpHeaders({ Authorization: 'Bearer ' + token });
        return this.http.delete(this.baseUrl + `banking/remove/${id}`, { headers });
      })
    );
  }
}
