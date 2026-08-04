import { create } from "zustand";

export const useLayoutStore = create((set) => ({
    open: false,
    toggle: () => set(({ open }) => ({ open: !open })),
    close: () => set({ open: false }),
}));
