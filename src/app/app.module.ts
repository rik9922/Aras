import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { RouteReuseStrategy } from '@angular/router';
import { HttpClientModule } from '@angular/common/http';
import { IonicModule, IonicRouteStrategy } from '@ionic/angular';
import { QuillModule } from 'ngx-quill'; 
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    IonicModule.forRoot({
      mode: 'ios',
      scrollPadding: true,
      scrollAssist: true
    }),
    HttpClientModule,
    AppRoutingModule,
    QuillModule.forRoot({
      readOnly: false,
      modules: {
        syntax: true,
        toolbar: [
          ['bold', 'italic', 'underline'],
        ]
      }
    }), QuillModule
  ],
  providers: [
    {
      provide: RouteReuseStrategy,
      useClass: IonicRouteStrategy,
    }
  ],
  bootstrap: [
    AppComponent
  ],
})
export class AppModule { }
