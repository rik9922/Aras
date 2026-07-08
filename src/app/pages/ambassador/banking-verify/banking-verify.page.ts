import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { Router } from '@angular/router';
import { BankingService } from 'src/app/services/banking.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  standalone: false,
  selector: 'app-banking-verify',
  templateUrl: './banking-verify.page.html',
  styleUrls: ['./banking-verify.page.scss'],
})
export class BankingVerifyPage implements OnInit {
  params: any = { title: 'Verify Bank Account', is_back: true };

  isLoading = false;
  errors: any = {};
  source: any = null;

  amount1 = '';
  amount2 = '';

  constructor(
    private banking: BankingService,
    private common: CommonService,
    private nav: NavController,
    private router: Router,
  ) {}

  ngOnInit() {
    const nav = this.router.getCurrentNavigation();
    this.source = nav?.extras?.state?.['source'] ?? null;
  }

  validate(): boolean {
    this.errors = {};
    const a1 = parseFloat(this.amount1);
    const a2 = parseFloat(this.amount2);

    if (!this.amount1 || isNaN(a1) || a1 < 0.01 || a1 > 0.99) {
      this.errors.amount1 = 'Enter a valid amount between $0.01 and $0.99.';
    }
    if (!this.amount2 || isNaN(a2) || a2 < 0.01 || a2 > 0.99) {
      this.errors.amount2 = 'Enter a valid amount between $0.01 and $0.99.';
    }

    return Object.keys(this.errors).length === 0;
  }

  submit() {
    if (!this.source) {
      this.common.presentAlert('Bank account not found. Please go back and try again.');
      return;
    }
    if (!this.validate()) return;

    this.isLoading = true;
    this.banking.verifyBank({
      funding_source_id: this.source.id,
      amount1: parseFloat(this.amount1),
      amount2: parseFloat(this.amount2),
    }).subscribe({
      next: () => {
        this.isLoading = false;
        this.common.presentToast('Bank account verified successfully!');
        this.nav.navigateBack('/banking');
      },
      error: (err: any) => {
        this.isLoading = false;
        const msg = err?.error?.message ?? 'Verification failed. Please check the amounts and try again.';
        this.common.presentAlert(msg);
      },
    });
  }
}
