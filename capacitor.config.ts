import type { CapacitorConfig } from '@capacitor/cli';
import { KeyboardResize, KeyboardStyle } from '@capacitor/keyboard';

const config: CapacitorConfig = {
  appId: 'io.araspromotions.com',
  appName: 'Aras Promotions',
  webDir: 'www',
  server: process.env['ARAS_LOCAL_NATIVE'] === '1' ? { cleartext: true } : undefined,
  android: {
    allowMixedContent: process.env['ARAS_LOCAL_NATIVE'] === '1',
  },
  plugins: {
    Keyboard: {
      resize: KeyboardResize.Body,
      style: KeyboardStyle.Dark,
      resizeOnFullScreen: true,
    },
    SplashScreen: {
      launchShowDuration: 3000,
      backgroundColor: '#d4aa2a',
      launchAutoHide: true,
      androidScaleType: 'CENTER_CROP',
      showSpinner: false,
    },
    StatusBar: {
      backgroundColor: '#d4aa2a',
    },
  },
};

export default config;
