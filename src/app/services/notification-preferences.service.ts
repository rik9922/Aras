import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from './api.service';
import { AppUser } from './device-sync.service';

export interface NotificationPreferences {
  push_enabled: boolean;
  email_enabled: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class NotificationPreferencesService {
  private readonly pushEnabledSubject = new BehaviorSubject<boolean>(true);

  readonly pushEnabled$ = this.pushEnabledSubject.asObservable();

  constructor(private readonly apiService: ApiService) {}

  get pushEnabled(): boolean {
    return this.pushEnabledSubject.value;
  }

  load(user: AppUser | null): Promise<NotificationPreferences | null> {
    if (!user?.token) {
      return Promise.resolve(null);
    }

    return new Promise((resolve) => {
      this.apiService.getAuthData('notification_preferences', user.token).subscribe({
        next: (res: any) => {
          const prefs: NotificationPreferences = {
            push_enabled: !!res?.data?.push_enabled,
            email_enabled: !!res?.data?.email_enabled,
          };
          this.pushEnabledSubject.next(prefs.push_enabled);
          resolve(prefs);
        },
        error: () => resolve(null),
      });
    });
  }

  updatePushEnabled(user: AppUser | null, enabled: boolean): Promise<boolean> {
    if (!user?.token) {
      return Promise.resolve(false);
    }

    return new Promise((resolve) => {
      this.apiService
        .sendAuthData('notification_preferences', { push_enabled: enabled ? 1 : 0 }, user.token)
        .subscribe({
          next: (res: any) => {
            const pushEnabled = !!res?.data?.push_enabled;
            this.pushEnabledSubject.next(pushEnabled);
            resolve(pushEnabled);
          },
          error: () => resolve(false),
        });
    });
  }
}
