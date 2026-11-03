import { Capacitor } from '@capacitor/core';
import { environment as defaults } from './environment.prod';

const nativeBaseUrl = Capacitor.getPlatform() === 'android'
  ? 'http://127.0.0.1:8000/'
  : 'http://localhost:8000/';
const native = Capacitor.isNativePlatform();

export const environment = {
  ...defaults,
  production: false,
  base_url: native ? nativeBaseUrl : '/backend/',
  login_url: native ? `${nativeBaseUrl}api/` : '/api/',
  auth_url: native ? `${nativeBaseUrl}api/auth/v1/` : '/api/auth/v1/',
};
