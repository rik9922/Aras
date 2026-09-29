import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, map } from 'rxjs';
import { ApiService } from './api.service';

export interface AmbassadorNotification {
  id: number;
  title: string;
  message: string;
  type?: string;
  booking_id?: number | null;
  is_seen: boolean;
  created_at?: string;
}

export interface AmbassadorNotificationListResponse {
  notifications: AmbassadorNotification[];
  unreadCount: number;
  pagination: {
    total: number;
    totalPage: number;
    currentPage: number;
    perPage: number;
    hasMore: boolean;
  };
}

@Injectable({
  providedIn: 'root',
})
export class AmbassadorNotificationsService {
  private readonly unreadCountSubject = new BehaviorSubject<number>(0);
  readonly unreadCount$ = this.unreadCountSubject.asObservable();

  constructor(private readonly apiService: ApiService) {}

  setUnreadCount(count: number): void {
    this.unreadCountSubject.next(Math.max(0, Number(count) || 0));
  }

  loadUnreadCount(token: string): Observable<number> {
    return this.apiService.getAuthData('notifications/unread-count', token).pipe(
      map((res: any) => {
        const unreadCount = Number(res?.data?.unread_count ?? 0);
        this.setUnreadCount(unreadCount);
        return unreadCount;
      })
    );
  }

  loadNotifications(token: string, page = 1, perPage = 15): Observable<AmbassadorNotificationListResponse> {
    return this.apiService.getAuthData(`notifications?page=${page}&per_page=${perPage}`, token).pipe(
      map((res: any) => {
        const rawNotifications = this.extractNotifications(res);
        const notifications = rawNotifications.map((item: any) => this.normalizeNotification(item));
        const unreadCount = Number(
          res?.data?.unread_count ??
          notifications.filter((item) => !item.is_seen).length
        );
        const pagination = this.extractPagination(res);

        this.setUnreadCount(unreadCount);
        return { notifications, unreadCount, pagination };
      })
    );
  }

  refreshUnreadCount(token: string): void {
    this.loadUnreadCount(token).subscribe({ error: () => undefined });
  }

  markAsRead(token: string, notificationId: number): Observable<any> {
    return this.apiService.sendAuthData(`notifications/${notificationId}/read`, {}, token).pipe(
      map((res: any) => {
        this.updateUnreadCountFromResponse(res);
        return res;
      })
    );
  }

  markAllAsRead(token: string): Observable<any> {
    return this.apiService.sendAuthData('notifications/mark-all-read', {}, token).pipe(
      map((res: any) => {
        this.updateUnreadCountFromResponse(res, 0);
        return res;
      })
    );
  }

  deleteNotification(token: string, notificationId: number): Observable<any> {
    return this.apiService.sendAuthData(`notifications/${notificationId}/delete`, {}, token).pipe(
      map((res: any) => {
        this.updateUnreadCountFromResponse(res);
        return res;
      })
    );
  }

  deleteAll(token: string): Observable<any> {
    return this.apiService.sendAuthData('notifications/delete-all', {}, token).pipe(
      map((res: any) => {
        this.updateUnreadCountFromResponse(res, 0);
        return res;
      })
    );
  }

  private extractNotifications(res: any): any[] {
    const data = res?.data;
    const candidates = [
      data?.notifications,
    ];

    return candidates.find((candidate) => Array.isArray(candidate)) ?? [];
  }

  private extractPagination(res: any): AmbassadorNotificationListResponse['pagination'] {
    const pagination = res?.data?.pagination ?? {};

    return {
      total: Number(pagination?.total ?? 0),
      totalPage: Number(pagination?.total_page ?? pagination?.totalPage ?? 1),
      currentPage: Number(pagination?.current_page ?? pagination?.currentPage ?? 1),
      perPage: Number(pagination?.per_page ?? pagination?.perPage ?? 15),
      hasMore: !!pagination?.has_more,
    };
  }

  private normalizeNotification(item: any): AmbassadorNotification {
    return {
      id: Number(item?.id),
      title: item?.title || 'Notification',
      message: item?.message || '',
      type: item?.type,
      booking_id: item?.booking_id ?? null,
      is_seen: !!Number(item?.is_seen ?? item?.read ?? item?.is_read ?? 0),
      created_at: item?.created_at,
    };
  }

  private updateUnreadCountFromResponse(res: any, fallback?: number): void {
    const count = res?.unread_count ?? res?.unreadCount ?? res?.data?.unread_count ?? res?.data?.unreadCount ?? fallback;
    if (count !== undefined) {
      this.setUnreadCount(Number(count));
    }
  }
}
