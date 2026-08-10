import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { TabsPage } from './tabs.page';

const routes: Routes = [
  {
    path: 'tabs',
    component: TabsPage,
    children: [
      {
        path: 'admin-home',
        loadChildren: () => import('../pages/admin/admin-home/admin-home.module').then(m => m.AdminHomePageModule)
      },
      {
        path: 'admin-event-list',
        loadChildren: () => import('../pages/admin/events/list/list.module').then(m => m.ListPageModule)
      },
      {
        path: 'admin-recap-list',
        loadChildren: () => import('../pages/admin/recap/list/list.module').then(m => m.ListPageModule)
      },
      {
        path: 'admin-brand-list',
        loadChildren: () => import('../pages/admin/brand/list/list.module').then(m => m.ListPageModule)
      },
      {
        path: 'location-home',
        loadChildren: () => import('../pages/location/location-home/location-home.module').then(m => m.LocationHomePageModule)
      },
      {
        path: 'location-events',
        loadChildren: () => import('../pages/location/location-events/location-events.module').then(m => m.LocationEventsPageModule)
      },
      {
        path: 'location-account',
        loadChildren: () => import('../pages/location/location-account/location-account.module').then(m => m.LocationAccountPageModule)
      },
      {
        path: 'brand-home',
        loadChildren: () => import('../pages/brand/brand-home/brand-home.module').then(m => m.BrandHomePageModule)
      },
      {
        path: 'brand-events',
        loadChildren: () => import('../pages/brand/brand-events/brand-events.module').then(m => m.BrandEventsPageModule)
      },
      {
        path: 'client-home',
        loadChildren: () => import('../pages/client/client-home/client-home.module').then(m => m.ClientHomePageModule)
      },
      {
        path: 'client-recap',
        loadChildren: () => import('../pages/client/client-recap/client-recap.module').then(m => m.ClientRecapPageModule)
      },
      {
        path: 'client-events',
        loadChildren: () => import('../pages/client/client-events/client-events.module').then(m => m.ClientEventsPageModule)
      },
      {
        path: 'client-account',
        loadChildren: () => import('../pages/client/client-account/client-account.module').then(m => m.ClientAccountPageModule)
      },

      {
        path: 'more',
        loadChildren: () => import('../commonPages/more/more.module').then(m => m.MorePageModule)
      },
      {
        path: 'ambassador-home',
        loadChildren: () => import('../pages/ambassador/ambassador-home/ambassador-home.module').then(m => m.AmbassadorHomePageModule)
      },
      {
        path: 'ambassador-profile',
        loadChildren: () => import('../pages/ambassador/ambassador-profile/ambassador-profile.module').then(m => m.AmbassadorProfilePageModule)
      },
      {
        path: 'ambassador-events',
        loadChildren: () => import('../pages/ambassador/ambassador-events/ambassador-events.module').then(m => m.AmbassadorEventsPageModule)
      },
      {
        path: 'ambassador-create-recap',
        loadChildren: () => import('../pages/ambassador/create-recap/create-recap.module').then(m => m.CreateRecapPageModule)
      },
      {
        path: '',
        redirectTo: '/tabs/admin-home',
        pathMatch: 'full'
      }
    ]
  },
  {
    path: '',
    redirectTo: '/tabs/admin-home',
    pathMatch: 'full'
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
})
export class TabsPageRoutingModule { }
