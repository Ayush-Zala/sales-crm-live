import toast from "react-hot-toast";

// ----------------------------------------------------------------------

// const URL = endpoints.calendar;

// const options = {
//     revalidateIfStale: false,
//     revalidateOnFocus: false,
//     revalidateOnReconnect: false,
// };

// export function useGetEvents() {
//     const { data, isLoading, error, isValidating } = useSWR(
//         URL,
//         fetcher,
//         options
//     );

//     const memoizedValue = useMemo(() => {
//         const events = data?.events.map((event) => ({
//             ...event,
//             textColor: event.color,
//         }));

//         return {
//             events: events || [],
//             eventsLoading: isLoading,
//             eventsError: error,
//             eventsValidating: isValidating,
//             eventsEmpty: !isLoading && !data?.events.length,
//         };
//     }, [data?.events, error, isLoading, isValidating]);

//     return memoizedValue;
// }

// ----------------------------------------------------------------------

export async function createEvent(eventData, addEventToStore) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to create event
    fetch(route("calendar.create"), {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify(eventData),
    })
        .then((response) => response.json())
        .then((res) => {
            addEventToStore(res.data);
            toast.success(res.message);
        });
}

export async function createZoomEvent(eventData, addEventToStore) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to create event
    fetch(route("zoom.index"), {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify(eventData),
    })
        .then((response) => response.json())
        .then((res) => {
            addEventToStore(res.data);
            toast.success(res.message);
        });
}

// ----------------------------------------------------------------------

export async function updateEvent(eventData, updateEventToStore) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to update event
    fetch(route("calendar.update"), {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify(eventData),
    })
        .then((response) => response.json())
        .then((res) => {
            updateEventToStore(eventData);
            toast.success(res.message);
        });
}

export async function updateZoomEvent(eventData, updateEventToStore) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to update event
    fetch(route("zoommeeting.update"), {
        method: "PATCH",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify(eventData),
    })
        .then((response) => response.json())
        .then((res) => {
            toast.success(res.message);
            updateEventToStore(eventData);
        });
}

// ----------------------------------------------------------------------

export async function deleteEvent(eventId, deleteEventFromStore) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to delete event
    fetch(route("calendar.delete"), {
        method: "DELETE",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            id: eventId,
        }),
    })
        .then((response) => response.json())
        .then((res) => {
            deleteEventFromStore(eventId);
            toast.success(res.message);
        })
        .catch((error) => {
            toast.error(error.message);
        });
}

export async function deleteZoomEvent(
    eventId,
    zoomMeetingId,
    deleteEventFromStore
) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    // api call to delete event
    fetch(route("zoommeeting.delete"), {
        method: "DELETE",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({
            id: eventId,
            meeting_id: zoomMeetingId,
        }),
    })
        .then((response) => response.json())
        .then((res) => {
            deleteEventFromStore(eventId);
            toast.success(res.message);
        })
        .catch((error) => {
            toast.error(error.message);
        });
}
