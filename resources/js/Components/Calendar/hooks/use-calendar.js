import { useRef, useState, useCallback } from "react";

import useResponsive from "./use-responsive";
import { fDateTime, formatDateTime } from "@/utils/date-time-formatters";
import useUpdateSearchParam from "@/hooks/use-update-search-params";

// ----------------------------------------------------------------------

export default function useCalendar() {
    const calendarRef = useRef(null);

    const calendarEl = calendarRef.current;

    const smUp = useResponsive("up", "sm");

    const [date, setDate] = useState(new Date());

    const [openForm, setOpenForm] = useState(false);

    const [selectEventId, setSelectEventId] = useState("");

    const [selectedRange, setSelectedRange] = useState(null);

    const [view, setView] = useState(smUp ? "dayGridMonth" : "listWeek");

    const onOpenForm = useCallback(() => {
        setOpenForm(true);
    }, []);

    const onCloseForm = useCallback(() => {
        setOpenForm(false);
        setSelectedRange(null);
        setSelectEventId("");
    }, []);

    const onInitialView = useCallback(() => {
        if (calendarEl) {
            const calendarApi = calendarEl.getApi();

            const newView = smUp ? "dayGridMonth" : "listWeek";
            calendarApi.changeView(newView);
            setView(newView);
        }
    }, [calendarEl, smUp]);

    const onChangeView = useCallback(
        (newView) => {
            if (calendarEl) {
                const calendarApi = calendarEl.getApi();

                calendarApi.changeView(newView);
                setView(newView);
            }
        },
        [calendarEl]
    );

    const onDateToday = useCallback(() => {
        if (calendarEl) {
            const calendarApi = calendarEl.getApi();

            calendarApi.today();
            setDate(calendarApi.getDate());
        }
    }, [calendarEl]);

    const updateSearchParams = (newDate) => {
        const month = newDate.getMonth() + 1; // Get the month (1-indexed)
        const year = newDate.getFullYear(); // Get the year
        useUpdateSearchParam({ month, year }, "/events");
    };

    const onDatePrev = useCallback(() => {
        if (calendarEl) {
            const calendarApi = calendarEl.getApi();
            calendarApi.prev();

            const newDate = calendarApi.getDate();
            setDate(newDate);
            updateSearchParams(newDate);
        }
    }, [calendarEl]);

    const onDateNext = useCallback(() => {
        if (calendarEl) {
            const calendarApi = calendarEl.getApi();
            calendarApi.next();

            const newDate = calendarApi.getDate();
            setDate(newDate);
            updateSearchParams(newDate);
        }
    }, [calendarEl]);

    const onSelectRange = useCallback(
        (arg) => {
            if (calendarEl) {
                const calendarApi = calendarEl.getApi();

                calendarApi.unselect();
            }
            onOpenForm();
            setSelectedRange({
                start: fDateTime(arg.start),
                end: fDateTime(arg.end),
            });
        },
        [calendarEl, onOpenForm]
    );

    const onClickEvent = useCallback(
        (arg) => {
            const { event } = arg;

            onOpenForm();
            setSelectEventId(event.id);
        },
        [onOpenForm]
    );

    const onResizeEvent = useCallback(
        (arg, updateEvent, updateEventToStore) => {
            const { event } = arg;

            updateEvent({
                calendarid: event.id,
                title: event.title,
                description: event.extendedProps.description,
                allDay: event.allDay,
                start: fDateTime(event.start),
                end: fDateTime(event.end),
                color: event.backgroundColor,
            }).then(() => {
                updateEventToStore({
                    id: parseInt(event.id),
                    title: event.title,
                    description: event.extendedProps.description,
                    allDay: event.allDay === "1" ? true : false,
                    start: event.start,
                    end: event.end,
                    color: event.backgroundColor,
                    timezone: event.extendedProps.timezone,
                });
            });
        },
        []
    );

    const onDropEvent = useCallback((arg, updateEvent, updateEventToStore) => {
        const { event } = arg;

        updateEvent({
            calendarid: event.id,
            title: event.title,
            description: event.extendedProps.description,
            allDay: event.allDay,
            start: fDateTime(event.start),
            end: fDateTime(event.end),
            color: event.backgroundColor,
        }).then(() => {
            updateEventToStore({
                id: parseInt(event.id),
                title: event.title,
                description: event.extendedProps.description,
                allDay: event.allDay === "1" ? true : false,
                start: event.start,
                end: event.end,
                color: event.backgroundColor,
                timezone: event.extendedProps.timezone,
            });
        });
    }, []);

    const onClickEventInFilters = useCallback(
        (eventId) => {
            if (eventId) {
                onOpenForm();
                setSelectEventId(eventId);
            }
        },
        [onOpenForm]
    );

    return {
        calendarRef,
        //
        view,
        date,
        //
        onDatePrev,
        onDateNext,
        onDateToday,
        onDropEvent,
        onClickEvent,
        onChangeView,
        onSelectRange,
        onResizeEvent,
        onInitialView,
        //
        openForm,
        onOpenForm,
        onCloseForm,
        //
        selectEventId,
        selectedRange,
        //
        onClickEventInFilters,
    };
}
