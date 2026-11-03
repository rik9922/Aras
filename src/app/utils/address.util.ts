/** Extract structured fields from a Google Places/Geocoder result. */
export function addressFields(place: any): Record<string, string> {
  const components: any[] = place?.address_components || [];
  const part = (type: string, short = false): string => {
    const value = components.find(component => component.types?.includes(type));
    return value?.[short ? 'short_name' : 'long_name'] || '';
  };
  const street = [part('street_number'), part('route')].filter(Boolean).join(' ');
  return {
    address: street || place?.formatted_address || '',
    city: part('locality') || part('postal_town') || part('sublocality') || part('administrative_area_level_2'),
    state: part('administrative_area_level_1', true),
    zip_code: part('postal_code'),
  };
}
