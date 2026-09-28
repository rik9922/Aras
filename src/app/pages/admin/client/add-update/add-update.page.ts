import { Component, OnInit } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { NavController } from '@ionic/angular';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { EventsService } from 'src/app/services/events.service';
import { StorageService } from 'src/app/services/storage.service';

@Component({
  standalone: false,
  selector: 'app-add-update',
  templateUrl: './add-update.page.html',
  styleUrls: ['./add-update.page.scss'],
})
export class AddUpdatePage implements OnInit {
  params: any = { title: 'Add Bill To', is_back: true };
  clientForm!: FormGroup;
  data: any;
  user: any;
  passwordType = 'password';
  passwordIcon = 'eye-off';
  isSpinner = false;
  brands: any[] = [];
  distributors: any[] = [];
  quickbooksId = 'Not assigned';

  validation_messages: any = {
    first_name: [{ type: 'required', message: 'First name is required.' }],
    last_name: [{ type: 'required', message: 'Last name is required.' }],
    email: [
      { type: 'required', message: 'Email is required.' },
      { type: 'email', message: 'Please enter a valid email address.' },
    ],
    phone: [{ type: 'required', message: 'Phone number is required.' }],
    brand_id: [{ type: 'required', message: 'Select at least one brand.' }],
    distributor_id: [{ type: 'required', message: 'Select at least one distributor.' }],
    address: [{ type: 'required', message: 'Address is required.' }],
    default_labor_amount: [{ type: 'required', message: 'Labor rate is required.' }],
    default_cups_amount: [{ type: 'required', message: 'Cup charge is required.' }],
    password: [{ type: 'required', message: 'Password is required.' }],
  };

  constructor(
    public router: Router,
    public formBuilder: FormBuilder,
    public storage: StorageService,
    public nav: NavController,
    public apiService: ApiService,
    public common: CommonService,
    public event: EventsService,
  ) {
    this.data = this.router.getCurrentNavigation()?.extras?.state;
    if (this.data?.id) {
      this.params.title = 'Edit Bill To';
    }
  }

  ngOnInit() {
    this.clientForm = this.formBuilder.group({
      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      phone: ['', Validators.required],
      status: ['1', Validators.required],
      contract_recieved: [false],
      brand_id: [[], Validators.required],
      distributor_id: [[], Validators.required],
      password: [''],
      default_labor_amount: ['', Validators.required],
      default_cups_amount: ['', Validators.required],
      default_receipt_percent: [''],
      default_buyback_amount: [''],
      address: ['', Validators.required],
      city: [''],
      state: [''],
      zip_code: [''],
      company_name: [''],
      primary_billing_contact: [''],
      country: ['USA'],
      notes: [''],
      cc_emails: this.formBuilder.array([]),
      budget_program_name: [''],
      budget_max_amount: [''],
      budget_max_events: [''],
      budget_warn_percent: [80],
      budget_alert_email: [''],
      budget_enforce: [false],
      budget_require_approval: [false],
    });

    if (!this.data?.id) {
      this.clientForm.controls['password'].setValidators([Validators.required, Validators.minLength(8)]);
    }
    this.clientForm.controls['password'].updateValueAndValidity();
  }

  ionViewWillEnter() {
    this.brands = [];
    this.distributors = [];
    this.storage.get('apro_user').then((data: any) => {
      if (!data) {
        this.nav.navigateRoot('/login');
        return;
      }

      this.user = data;
      this.getBrandsAndDistributors();
      if (this.data?.id) {
        this.getClientById(this.data.id);
      } else if (this.ccEmails.length === 0) {
        this.addCcEmail();
      }
    });
  }

  get ccEmails(): FormArray {
    return this.clientForm.get('cc_emails') as FormArray;
  }

  addCcEmail(value = '') {
    this.ccEmails.push(new FormControl(value, Validators.email));
  }

  removeCcEmail(index: number) {
    this.ccEmails.removeAt(index);
    if (this.ccEmails.length === 0) {
      this.addCcEmail();
    }
  }

  getClientById(id: any) {
    this.common.showLoader();
    this.apiService.getAuthData(`admin/get_client_details?client_id=${id}`, this.user?.token).subscribe({
      next: (res: any) => {
        this.common.dismissLoading();
        const client = res?.data ?? {};
        this.quickbooksId = client?.quickbooks?.customer_id || 'Not assigned';
        this.clientForm.patchValue({
          first_name: client?.first_name ?? '',
          last_name: client?.last_name ?? '',
          email: client?.email ?? '',
          phone: client?.phone ?? '',
          status: String(client?.status_value ?? '1'),
          contract_recieved: String(client?.contract_recieved ?? '0') === '1',
          brand_id: client?.brand_ids ?? [],
          distributor_id: client?.distributor_ids ?? [],
          default_labor_amount: client?.platform?.default_labor_amount ?? '',
          default_cups_amount: client?.platform?.default_cups_amount ?? '',
          default_receipt_percent: client?.default_receipt_percent ?? '',
          default_buyback_amount: client?.platform?.default_buyback_amount ?? '',
          address: client?.address?.address ?? '',
          city: client?.address?.city ?? '',
          state: client?.address?.state ?? '',
          zip_code: client?.address?.zip_code ?? '',
          company_name: client?.company_name ?? '',
          primary_billing_contact: client?.primary_billing_contact ?? '',
          country: client?.address?.country ?? 'USA',
          notes: client?.platform?.notes ?? '',
          budget_program_name: client?.budget?.budget_program_name ?? '',
          budget_max_amount: client?.budget?.budget_max_amount ?? '',
          budget_max_events: client?.budget?.budget_max_events ?? '',
          budget_warn_percent: client?.budget?.budget_warn_percent ?? 80,
          budget_alert_email: client?.budget?.budget_alert_email ?? '',
          budget_enforce: Boolean(client?.budget?.budget_enforce),
          budget_require_approval: Boolean(client?.budget?.budget_require_approval),
        });

        this.ccEmails.clear();
        const ccList = Array.isArray(client?.cc_emails) ? client.cc_emails : [];
        if (ccList.length) {
          ccList.forEach((email: string) => this.addCcEmail(email));
        } else {
          this.addCcEmail();
        }
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to load Bill To.');
      },
    });
  }

  getBrandsAndDistributors() {
    this.isSpinner = true;
    this.apiService.getAuthData('admin/get_brands_distributions_data', this.user?.token).subscribe({
      next: (res: any) => {
        this.isSpinner = false;
        this.distributors = res?.data?.distributors ?? [];
        this.brands = res?.data?.brands ?? [];
      },
      error: () => {
        this.isSpinner = false;
        this.common.presentAlert('Unable to load brands and distributors.');
      },
    });
  }

  hideShowPassword() {
    this.passwordType = this.passwordType === 'text' ? 'password' : 'text';
    this.passwordIcon = this.passwordIcon === 'eye-off' ? 'eye' : 'eye-off';
  }

  submit(formvalue: any) {
    if (this.clientForm.invalid) {
      this.clientForm.markAllAsTouched();
      return;
    }

    const payload = {
      ...formvalue,
      user_id: this.data?.id,
      role_id: 3,
      contract_recieved: formvalue.contract_recieved ? 1 : 0,
      budget_enforce: formvalue.budget_enforce ? 1 : 0,
      budget_require_approval: formvalue.budget_require_approval ? 1 : 0,
      cc_emails: (formvalue.cc_emails || []).filter((email: string) => !!String(email || '').trim()),
    };

    if (!payload.user_id) {
      delete payload.user_id;
    }
    if (!payload.password) {
      delete payload.password;
    }

    this.common.showLoader();
    this.apiService.sendAuthData('admin/update_client_profile', payload, this.user?.token).subscribe({
      next: async (res: any) => {
        this.common.dismissLoading();
        const check = await this.common.presentAlertWithPromise('Success', res?.message);
        this.event.publish('reload_clients', 1);
        if (check) {
          this.router.navigate(['/admin-client-list']);
        }
      },
      error: (err: any) => {
        this.common.dismissLoading();
        this.common.presentAlert(err?.error?.message || 'Unable to save Bill To.');
      },
    });
  }
}
