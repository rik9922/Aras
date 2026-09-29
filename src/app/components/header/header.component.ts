import { ChangeDetectorRef, Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController, NavController, MenuController, Platform } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/services/api.service';
import { AmbassadorNotificationsService } from 'src/app/services/ambassador-notifications.service';
import { BankingService } from 'src/app/services/banking.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'appHeader',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
})
export class HeaderComponent implements OnInit {
  @Input() params: any;
  // Controls the banking setup banner (mirrors the web portal).
  showBankBanner = false;
  showBankVerificationBanner = false;
  showNotificationBell = false;
  unreadNotificationCount = 0;
  private menuReady = false;
  private pendingOpen = false;
  private subscriptions: Subscription[] = [];
  constructor(
    public nav: NavController,
    public menuCtrl: MenuController,
    private platform: Platform,
    private storage: StorageService,
    private banking: BankingService,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private notifications: AmbassadorNotificationsService,
    private events: EventsService,
  ) {
    platform.ready().then(() => {
      this.menuReady = true;
      if (this.pendingOpen) {
        this.pendingOpen = false;
        this.menuCtrl.open('main-menu');
      }
    });
  }

  ngOnInit() {
    this.evaluateBankBanner();
    this.evaluateNotificationBell();
    this.subscriptions.push(
      this.notifications.unreadCount$.subscribe((count) => {
        this.unreadNotificationCount = count;
        this.cdr.detectChanges();
      }),
      this.events.subscribe('updateNotifications', (count) => {
        this.notifications.setUnreadCount(Number(count));
      })
    );
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }

  /**
   * Shows the banner only for ambassadors (role_id 5) whose banking setup needs
   * attention, and never on the banking pages themselves. Uses the
   * cached status in BankingService so navigating between pages doesn't refetch.
   */
  private async evaluateBankBanner(): Promise<void> {
    const user: any = await this.storage.get('apro_user');
    const onBankingPage = (this.router.url || '').includes('/banking');
    if (!user || Number(user.role_id) !== 5 || onBankingPage) {
      this.showBankBanner = false;
      this.showBankVerificationBanner = false;
      return;
    }
    this.banking.getSetupStatus().subscribe((status: string) => {
      this.showBankBanner = status === 'not_started';
      this.showBankVerificationBanner = status === 'pending_verification';
      this.cdr.detectChanges();
    });
  }

  goToDirectDeposit() {
    this.nav.navigateForward('/banking');
  }

  private async evaluateNotificationBell(): Promise<void> {
    const user: any = await this.storage.get('apro_user');
    this.showNotificationBell = this.canShowNotificationBell(user);
    if (this.showNotificationBell && user?.token) {
      this.notifications.refreshUnreadCount(user.token);
    }
    this.cdr.detectChanges();
  }

  goToNotifications() {
    this.storage.get('apro_user').then((user: any) => {
      const roleId = Number(user?.role_id);
      const route = roleId === 1 || roleId === 6 ? '/admin-notifications' : '/ambassador-notifications';
      this.nav.navigateForward(route);
    });
  }

  private canShowNotificationBell(user: any): boolean {
    const roleId = Number(user?.role_id);
    return roleId === 1 || roleId === 5 || roleId === 6;
  }

  openMenu() {
    console.log('=== HAMBURGER CLICK ===');
    const menu = this.menuCtrl.get('main-menu');
    console.log('MenuController.get("main-menu"):', menu);
    this.menuCtrl.isEnabled('main-menu').then((isEnabled: boolean) => {
      console.log('isEnabled("main-menu"):', isEnabled);
    });
    this.menuCtrl.isOpen('main-menu').then((isOpen: boolean) => {
      console.log('isOpen("main-menu") before open():', isOpen);
    });
    this.menuCtrl.open('main-menu').then(() => {
      console.log('open("main-menu") resolved successfully');
      this.menuCtrl.isOpen('main-menu').then((isOpen: boolean) => {
        console.log('isOpen("main-menu") after open():', isOpen);
      });
    }).catch((err: any) => {
      console.error('open("main-menu") rejected:', err);
    });
  }

  goBack() {
    this.nav.back();
  }
}
