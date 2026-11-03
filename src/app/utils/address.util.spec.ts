import { addressFields } from './address.util';

describe('Venue address fields', () => {
  it('fills the street, city, state abbreviation and ZIP from a selected address', () => {
    const component = (type: string, long_name: string, short_name = long_name) => ({ types: [type], long_name, short_name });
    expect(addressFields({ address_components: [
      component('street_number', '6140'), component('route', 'West Executive Drive'),
      component('locality', 'Mequon'), component('administrative_area_level_1', 'Wisconsin', 'WI'),
      component('postal_code', '53092'),
    ] })).toEqual({ address: '6140 West Executive Drive', city: 'Mequon', state: 'WI', zip_code: '53092' });
  });

  it('supports postal towns and a formatted address when street components are absent', () => {
    expect(addressFields({ formatted_address: 'Venue address', address_components: [{ types: ['postal_town'], long_name: 'Town' }] }))
      .toEqual({ address: 'Venue address', city: 'Town', state: '', zip_code: '' });
  });
});
