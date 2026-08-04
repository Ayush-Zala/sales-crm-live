export const hasRole = (auth, requiredRoles) => {
    const roles = auth?.roles ?? [];

    return roles.some((role) => requiredRoles.includes(role));
};

// Helper method to check if the user has a specific permission
export const hasPermission = (auth, requiredPermission) => {
    const permissions = auth?.permissions ?? [];

    return permissions.includes(requiredPermission);
};
