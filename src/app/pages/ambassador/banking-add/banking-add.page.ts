import { Component, OnInit } from '@angular/core';
import { NavController } from '@ionic/angular';
import { BankingService } from 'src/app/services/banking.service';
import { CommonService } from 'src/app/services/common.service';

@Component({
  standalone: false,
  selector: 'app-banking-add',
  templateUrl: './banking-add.page.html',
  styleUrls: ['./banking-add.page.scss'],
})
export class BankingAddPage implements OnInit {
  params: any = { title: 'Add Bank Account', is_back: true };

  isLoading = false;
  errors: any = {};

  form = {
    bank_name: '',
    account_holder_name: '',
    routing_number: '',
    account_number: '',
    account_number_confirm: '',
    account_type: 'checking',
    set_as_default: true,
  };

  constructor(
    private banking: BankingService,
    private common: CommonService,
    private nav: NavController,
  ) {}

  ngOnInit() {}

  validate(): boolean {
    this.errors = {};

    if (!this.form.bank_name.trim()) {
      this.errors.bank_name = 'Bank name is required.';
    }
    if (!this.form.account_holder_name.trim()) {
      this.errors.account_holder_name = 'Account holder name is required.';
    }
    if (!/^\d{9}$/.test(this.form.routing_number)) {
      this.errors.routing_number = 'Routing number must be exactly 9 digits.';
    }
    if (!/^\d{4,17}$/.test(this.form.account_number)) {
      this.errors.account_number = 'Account number must be 4-17 digits.';
    }
    if (this.form.account_number !== this.form.account_number_confirm) {
      this.errors.account_number_confirm = 'Account numbers do not match.';
    }
    if (!['checking', 'savings'].includes(this.form.account_type)) {
      this.errors.account_type = 'Select a valid account type.';
    }

    return Object.keys(this.errors).length === 0;
  }

  submit() {
    if (!this.validate()) return;

    this.isLoading = true;
    this.banking.addBank({
      bank_name: this.form.bank_name,
      account_holder_name: this.form.account_holder_name,
      routing_number: this.form.routing_number,
      account_number: this.form.account_number,
      account_type: this.form.account_type,
      set_as_default: this.form.set_as_default,
    }).subscribe({
      next: () => {
        this.isLoading = false;
        this.common.presentToast('Bank account added! Please verify with the two micro-deposit amounts.');
        this.nav.navigateBack('/banking');
      },
      error: (err: any) => {
        this.isLoading = false;
        const msg = err?.error?.message ?? 'Could not add bank account. Please check your details.';
        this.common.presentAlert(msg);
      },
    });
  }
}
