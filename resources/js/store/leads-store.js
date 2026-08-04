import { formatDateTime } from "@/utils/date-time-formatters";
import { create } from "zustand";

const useLeadsStore = create((set) => ({
    leads: [],
    setLeads: (newLeads) => set({ leads: newLeads }),

    // New methos to update the accounts data
    // updateLeadToStore: (id, status, updated_at) =>
    //     set((state) => {
    //         console.log(id, status, updated_at);
    //         return {
    //             ...state.accounts,
    //             data: state.accounts.data.map((account) => {
    //                 if (account.id === id) {
    //                     console.log({
    //                         ...account, // Spread the current account details
    //                         status: status,
    //                         updated_at: formatDateTime(updated_at),
    //                     });
    //                     return {
    //                         ...account, // Spread the current account details
    //                         disposition_status: status,
    //                         updated_at: formatDateTime(updated_at),
    //                     };
    //                 }
    //                 return state.accounts;
    //             }),
    //         };
    //     }),
}));

export default useLeadsStore;
