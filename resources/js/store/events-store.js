import { create } from "zustand";

const useEventsStore = create((set) => ({
    events: [],
    setEvents: (newEvents) => set({ events: newEvents }),

    // New method to add new event data
    addEventToStore: (eventData) => {
        return set((state) => ({
            events: [
                ...state.events,
                {
                    id: eventData.id,
                    title: eventData.title,
                    description: eventData.description,
                    allDay: eventData.all_day,
                    start: eventData.start_date,
                    end: eventData.end_date,
                    color: eventData.colors,
                    name: eventData.name,
                    userid: eventData.created_by,
                    rRule: eventData.repeat_rule,
                    timezone: eventData.timezone,
                    zoomMeeting: eventData.zoom_meeting,
                    zoomMeetingId: eventData.zoom_meeting_id,
                },
            ],
        }));
    },

    // New method to update the events data
    updateEventToStore: (eventData) =>
        set((state) => {
            const updatedEvents = state.events.map((event) => {
                if (event.id === eventData.id) {
                    // console.log("Updating event:", event); // Debug log
                    // console.log("With data:", eventData); // Debug log
                    return {
                        ...event,
                        title: eventData.title,
                        description: eventData.description,
                        allDay: eventData.allDay,
                        start: eventData.start,
                        end: eventData.end,
                        color: eventData.color,
                        timezone: eventData.timezone,
                    };
                }
                return event;
            });

            // const updatedEvent = updatedEvents.find(
            //     (event) => event.id === eventData.id
            // );
            // console.log("Updated Event:", updatedEvent);
            return { events: updatedEvents };
        }),

    // New method to delete the events data
    deleteEventFromStore: (eventId) =>
        set((state) => ({
            events: Array.isArray(state.events)
                ? state.events.filter((event) => event.id !== eventId)
                : [],
        })),
}));

export default useEventsStore;
