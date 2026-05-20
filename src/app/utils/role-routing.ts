/** Admin (1) and Manager (6) share the same mobile home experience. */
export function homeRouteForRole(roleId: unknown): string | null {
  const rid = Number(roleId);
  if (rid === 1 || rid === 6) {
    return '/tabs/admin-home';
  }
  if (rid === 2) {
    return '/tabs/brand-home';
  }
  if (rid === 3) {
    return '/tabs/client-home';
  }
  if (rid === 4) {
    return '/tabs/location-home';
  }
  if (rid === 5) {
    return '/tabs/ambassador-home';
  }
  return null;
}

export function isAdminOrManager(roleId: unknown): boolean {
  const rid = Number(roleId);
  return rid === 1 || rid === 6;
}
