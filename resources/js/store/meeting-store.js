import { create } from "zustand";

const useMeetingStore = create((set) => ({
    meetings: [], // Array of meetings with their times
    setMeetings: (meetings) => set({ meetings }),
}));

export default useMeetingStore;
