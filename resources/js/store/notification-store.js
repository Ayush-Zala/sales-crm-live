import { create } from "zustand";

export const useNotificationStore = create((set) => ({
    notifications: [],
    setNotifications: (notification) => set({ notifications: notification }),

    notificationCount: 0,
    setNotificationCount: (count) => set({ notificationCount: count }),
}));
