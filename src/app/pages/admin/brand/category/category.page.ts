import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/services/api.service';
import { CommonService } from 'src/app/services/common.service';
import { StorageService } from 'src/app/services/storage.service';
import { environment } from 'src/environments/environment';
import { dataTableAjaxDataFilter, resolveDataTableResponse } from 'src/app/utils/api-response.util';

declare var jQuery: any;

@Component({
  standalone:false,
  selector: 'app-category',
  templateUrl: './category.page.html',
  styleUrls: ['./category.page.scss'],
})
export class CategoryPage implements OnInit {
  params: any = { title: "Brand Categories",'is_back':true };
  user:any;
  data:any;
  is_ready:boolean=false;
  count:number=0;
  isModelOpen: boolean = false;
  categoryForm!:FormGroup;
  heading:any='Add ';
  constructor(
    public storage : StorageService,
    public apiService : ApiService,
    public common : CommonService,
    public router : Router,
    public formBuilder : FormBuilder
  ) {
    this.categoryForm = this.formBuilder.group({
      id: new FormControl(""),
      name: new FormControl("", Validators.compose([Validators.required,])),
      note: new FormControl("", Validators.compose([Validators.required,])),
    });
   }

  ngOnInit() {
  }

  ionViewWillEnter(){
    this.storage.get("apro_user").then((data) => {
      if (data != null) {
        this.user = data;
        this.getData();  
        this.data=null;         
      } else {
        this.router.navigate(["/login"]);
      }
    });
  }

  getData(){
    var self=this;
      var table= jQuery('#brand-category').DataTable({
        dom: 'frtip',
        processing: true,
        serverSide: true,
        ordering: false,
        responsive:true,
        bDestroy: true,
        bAutoWidth: false,
        bJQueryUI: true,
        sDom: '<"H"ilfr><"applicationsList"t><"F"p>',

        ajax: {
            url: environment.auth_url+"admin/get_brand_categories", // Replace with your server-side script URL
            type: "GET",
            headers: {
              "Authorization": "Bearer " + self.user?.token
            },
            dataFilter: dataTableAjaxDataFilter,

            dataSrc:function(a:any){
              const { payload, rows } = resolveDataTableResponse(a);
              table.processing(false);
              return rows},
        },
        error: function(err:any) {
            console.error("Error fetching data", err);
        },
        columns: [
            { data: "name" },
            { data: "note" },
            { data: "action" },
        ],
        drawCallback: function(settings:any) {
            console.log('Table redrawn');
            jQuery('ion-icon[name="create"]').on('click',function(e:any){
              var html=jQuery('#'+e.target.id).parent().parent();
              console.log(html)
              self.heading='Update ';
              var formvalue={
                id:e.target.id,
                name : jQuery(html).find('td:first-child').text(),
                note : jQuery(html).find('td:nth-child(2)').text(),
              }
              self.edit(formvalue)
            })
        }
      });
  }  

  model(type: any) {
    this.heading='Add ';
    this.isModelOpen = type
    this.categoryForm.reset()
  }

  edit(formvalue: any) {
    this.isModelOpen = true
    this.categoryForm.patchValue(formvalue);
  }

  submit(formvalue: any) {
    this.common.showLoader();
    this.apiService.sendAuthData('admin/save_brand_category', formvalue, this.user?.token).subscribe((res: any) => {
      this.common.dismissLoading()
      this.model(false);
      this.getData()
      this.common.presentAlert(res?.message)
    }, err => {
      this.common.dismissLoading()
      this.common.presentAlert(err?.error?.message);
      console.log(err)
    })
  }
}
  