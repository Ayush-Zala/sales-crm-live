import { create } from "zustand";

export const useSelectionStore = create((set) => ({
    selection: [],
    setSelection: (selection) => set({ selection }),
}));
