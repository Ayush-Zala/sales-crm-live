import { create } from "zustand";

export const useLeadsSelectionStore = create((set) => ({
    selection: [],
    setSelection: (selection) => set({ selection }),
}));
