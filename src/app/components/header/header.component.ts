import { ChangeDetectorRef, Component, Input, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ModalController, NavController, MenuController, Platform } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
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
  // Controls the "Payment Setup Required" banner (mirrors the web portal).
  showBankBanner = false;
  private menuReady = false;
  private pendingOpen = false;
  constructor(
    public nav: NavController,
    public menuCtrl: MenuController,
    private platform: Platform,
    private storage: StorageService,
    private banking: BankingService,
    private router: Router,
    private cdr: ChangeDetectorRef,
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
  }

  /**
   * Shows the banner only for ambassadors (role_id 5) whose banking setup is
   * not yet verified, and never on the banking pages themselves. Uses the
   * cached status in BankingService so navigating between pages doesn't refetch.
   */
  private async evaluateBankBanner(): Promise<void> {
    const user: any = await this.storage.get('apro_user');
    const onBankingPage = (this.router.url || '').includes('/banking');
    if (!user || Number(user.role_id) !== 5 || onBankingPage) {
      this.showBankBanner = false;
      return;
    }
    this.banking.isSetupComplete().subscribe((complete: boolean) => {
      this.showBankBanner = !complete;
      this.cdr.detectChanges();
    });
  }

  goToDirectDeposit() {
    this.nav.navigateForward('/banking');
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

