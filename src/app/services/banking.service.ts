import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import { StorageService } from './storage.service';
import { from, switchMap } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class BankingService {
  private baseUrl = environment.auth_url + 'ambassador/';

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
