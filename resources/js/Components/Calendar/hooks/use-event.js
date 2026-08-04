import { useMemo } from "react";
import merge from "lodash/merge";
import CALENDAR_COLOR_OPTIONS from "../_mock/_calendar";

// ----------------------------------------------------------------------

export default function useEvent(
    events,
    selectEventId,
    selectedRange,
    openForm
) {
    // const currentEvent = events.find((event) => event.id === selectEventId);

    const currentEvent = events?.length
        ? events.find((event) => {
              return String(event.id) === String(selectEventId); // Coerce to the same type
          })
        : null;

    const defaultValues = useMemo(
        () => ({
            id: "",
            title: "",
            description: "",
            color: CALENDAR_COLOR_OPTIONS[1],
            allDay: false,
            start: selectedRange ? selectedRange.start : new Date().getTime(),
            end: selectedRange ? selectedRange.end : new Date().getTime(),
        }),
        [selectedRange]
    );

    if (!openForm) {
        return undefined;
    }

    if (currentEvent || selectedRange) {
        return merge({}, defaultValues, currentEvent);
    }

    return defaultValues;
}
