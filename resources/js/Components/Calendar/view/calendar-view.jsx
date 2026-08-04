import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import listPlugin from "@fullcalendar/list";
import Calendar from "@fullcalendar/react";
import timeGridPlugin from "@fullcalendar/timegrid";
import timelinePlugin from "@fullcalendar/timeline";
import { usePage } from "@inertiajs/react";
import Card from "@mui/material/Card";
import Dialog from "@mui/material/Dialog";
import DialogTitle from "@mui/material/DialogTitle";
import Grid from "@mui/material/Grid";
import Typography from "@mui/material/Typography";
import { useTheme } from "@mui/material/styles";
import { useCallback, useEffect, useState } from "react";

import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import useEventsStore from "@/store/events-store";
import useMeetingStore from "@/store/meeting-store";
import { hasRole } from "@/utils/AccessManager";
import { convertToIST, isAfter, isBetween } from "@/utils/date-time-formatters";
import { BusinessRounded, PhoneRounded } from "@mui/icons-material";
import { Box, IconButton, Tooltip } from "@mui/material";
// import CALENDAR_COLOR_OPTIONS from "../_mock/_calendar";
import { updateEvent } from "../api/calendar";
import CalendarFilters from "../calendar-filters";
import CalendarFiltersResult from "../calendar-filters-result";
import CalendarForm from "../calendar-form";
import CalendarToolbar from "../calendar-toolbar";
import { useBoolean, useCalendar, useEvent, useResponsive } from "../hooks";
import { StyledCalendar } from "../styles";
import { error, secondary, success, warning } from "../theme/palette";
import { motion } from "framer-motion";

// ----------------------------------------------------------------------

const defaultFilters = {
    colors: [],
    startDate: null,
    endDate: null,
};

// ----------------------------------------------------------------------

// const events = [
//     {
//         id: "e99f09a7-dd88-49d5-b1c8-1daf80c2d7b2",
//         title: "Hello",
//         description:
//             "Atque eaque ducimus minima distinctio velit. Laborum et veniam officiis. Delectus ex saepe hic id laboriosam officia. Odit nostrum qui illum saepe debitis ullam. Laudantium beatae modi fugit ut. Dolores consequatur beatae nihil voluptates rem maiores.",
//         allDay: false,
//         color: "#00A76F",
//         start: "2024-10-12T10:51:49+00:00",
//         end: "2024-10-12T14:21:49+00:00",
//     },
// ];

export default function CalendarView() {
    const theme = useTheme();

    const { events, updateEventToStore } = useEventsStore();

    const { setMeetings } = useMeetingStore();

    const auth = usePage().props.auth;
    const isAdminOrBDM = hasRole(auth, [
        "Admin",
        "Business Development Manager",
    ]);

    const smUp = useResponsive("up", "sm");

    const openFilters = useBoolean();

    const [filters, setFilters] = useState(defaultFilters);

    const dateError = isAfter(filters.startDate, filters.endDate);

    useEffect(() => {
        // Get today's date in YYYY-MM-DD format
        const today = convertToIST(new Date().toISOString()).split(" ")[0];
        // console.log(events);
        // Filter events whose date (YYYY-MM-DD part) matches today's date
        try {
            const todaysEvents =
                events.length > 0 &&
                events.filter((event) => {
                    const eventDate = event.start?.split(" ")[0]; // Extract the date part from the datetime
                    return eventDate === today;
                });

            // console.log(todaysEvents);

            setMeetings(todaysEvents || []);
        } catch (e) {
            console.log(e);
        }

        return () => setMeetings([]);
    }, [events]);

    const {
        calendarRef,
        //
        view,
        date,
        //
        onDatePrev,
        onDateNext,
        onDateToday,
        onDropEvent,
        onChangeView,
        onSelectRange,
        onClickEvent,
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
    } = useCalendar();

    const currentEvent = useEvent(
        events,
        selectEventId,
        selectedRange,
        openForm
    );

    useEffect(() => {
        onInitialView();
    }, [onInitialView]);

    const handleFilters = useCallback((name, value) => {
        setFilters((prevState) => ({
            ...prevState,
            [name]: value,
        }));
    }, []);

    const handleResetFilters = useCallback(() => {
        setFilters(defaultFilters);
    }, []);

    const canReset =
        !!filters.colors.length || (!!filters.startDate && !!filters.endDate);

    const dataFiltered = applyFilter({ inputData: events, filters, dateError });

    const renderResults = (
        <CalendarFiltersResult
            filters={filters}
            onFilters={handleFilters}
            canReset={canReset}
            onResetFilters={handleResetFilters}
            results={dataFiltered.length}
            sx={{ mb: { xs: 3, md: 5 } }}
        />
    );

    return (
        <>
            <MainContentTemplate
                title="Events"
                subtitle="View Event details here"
                button="New Event"
                onClick={onOpenForm}
            >
                <Grid item xs={12}>
                    {canReset && renderResults}
                    <Card>
                        <StyledCalendar>
                            <CalendarToolbar
                                date={date}
                                view={view}
                                loading={false}
                                onNextDate={onDateNext}
                                onPrevDate={onDatePrev}
                                onToday={onDateToday}
                                onChangeView={onChangeView}
                                onOpenFilters={openFilters.onTrue}
                            />

                            <Calendar
                                weekends
                                editable
                                droppable
                                selectable
                                rerenderDelay={10}
                                allDayMaintainDuration
                                eventResizableFromStart
                                ref={calendarRef}
                                initialDate={date}
                                initialView={view}
                                dayMaxEventRows={3}
                                eventDisplay="block"
                                events={dataFiltered}
                                headerToolbar={false}
                                select={onSelectRange}
                                eventClick={onClickEvent}
                                height={smUp ? 720 : "auto"}
                                eventDrop={(arg) => {
                                    onDropEvent(
                                        arg,
                                        updateEvent,
                                        updateEventToStore
                                    );
                                }}
                                eventResize={(arg) => {
                                    onResizeEvent(
                                        arg,
                                        updateEvent,
                                        updateEventToStore
                                    );
                                }}
                                plugins={[
                                    listPlugin,
                                    dayGridPlugin,
                                    timelinePlugin,
                                    timeGridPlugin,
                                    interactionPlugin,
                                ]}
                                eventContent={(arg) => {
                                    const { event } = arg;

                                    // Get the event color based on the start date
                                    const eventColor = getEventColor(
                                        event.start,
                                        event.end
                                    );

                                    let backgroundColor;
                                    let shouldBlink = false;

                                    if (eventColor === "red") {
                                        backgroundColor = error.main; // Red for past events
                                        shouldBlink = true; // Apply blinking
                                    } else if (eventColor === "yellow") {
                                        backgroundColor = warning.main; // Yellow for events in progress or within 15 minutes
                                        shouldBlink = true; // Apply blinking
                                    } else if (eventColor === "green") {
                                        backgroundColor = success.dark; // Default color for future events
                                    } else {
                                        backgroundColor = secondary.main; // Default color for future events
                                    }

                                    return (
                                        <motion.div
                                            initial={{ opacity: 1 }}
                                            style={{
                                                backgroundColor:
                                                    backgroundColor,
                                                borderColor: "#000",
                                                padding: "8px 12px",
                                                borderRadius: "8px",
                                                color: event.textColor,
                                                boxShadow:
                                                    "0 4px 8px rgba(0, 0, 0, 0.1)",
                                            }}
                                            animate={
                                                shouldBlink
                                                    ? { opacity: [1, 0.5, 1] }
                                                    : {}
                                            }
                                            transition={
                                                shouldBlink
                                                    ? {
                                                          duration: 3, // Smooth and slow animation duration
                                                          ease: "easeInOut", // Easing for smooth transitions
                                                          repeat: Infinity, // Infinite loop
                                                      }
                                                    : {}
                                            }
                                        >
                                            {isAdminOrBDM ? (
                                                <Typography
                                                    variant="body2"
                                                    sx={{
                                                        overflow: "hidden",
                                                        textWrap: "initial",
                                                    }}
                                                >{`${arg.timeText} ${event.title} - ${event.extendedProps.name}'s Event`}</Typography>
                                            ) : (
                                                <Typography
                                                    variant="body2"
                                                    sx={{
                                                        overflow: "hidden",
                                                        textWrap: "initial",
                                                    }}
                                                >{`${arg.timeText} ${event.title}`}</Typography>
                                            )}
                                        </motion.div>
                                    );
                                }}
                            />
                        </StyledCalendar>
                    </Card>
                </Grid>
            </MainContentTemplate>

            <Dialog
                fullWidth
                maxWidth="xs"
                open={openForm}
                onClose={(_, reason) =>
                    reason !== "backdropClick" && onCloseForm()
                }
                transitionDuration={{
                    enter: theme.transitions.duration.shortest,
                    exit: theme.transitions.duration.shortest - 80,
                }}
            >
                <DialogTitle>
                    <Box
                        display="flex"
                        alignItems="center"
                        justifyContent="space-between"
                    >
                        {/* Event Title */}
                        <span>
                            {openForm && (
                                <>
                                    {currentEvent?.id && isAdminOrBDM
                                        ? `Edit ${`${currentEvent.name}'s Event`}`
                                        : currentEvent?.id && !isAdminOrBDM
                                        ? "Edit Event"
                                        : "Add Event"}
                                </>
                            )}
                        </span>

                        {/* Icons for calling and viewing */}
                        {currentEvent && (
                            <Box display="flex" gap={1}>
                                {currentEvent.phone && (
                                    <Tooltip title="Call" arrow>
                                        <IconButton
                                            color="primary"
                                            onClick={() =>
                                                window.open(
                                                    `tel:${currentEvent.phone}`,
                                                    "_self"
                                                )
                                            }
                                        >
                                            <PhoneRounded />
                                        </IconButton>
                                    </Tooltip>
                                )}
                                {currentEvent.companyId && (
                                    <Tooltip title="View Company" arrow>
                                        <IconButton
                                            LinkComponent="a"
                                            color="primary"
                                            href={route(
                                                "account.view",
                                                currentEvent.companyId
                                            )}
                                            target="_blank"
                                        >
                                            <BusinessRounded />
                                        </IconButton>
                                    </Tooltip>
                                )}
                            </Box>
                        )}
                    </Box>
                </DialogTitle>

                <CalendarForm
                    currentEvent={currentEvent}
                    onClose={onCloseForm}
                />
            </Dialog>

            <CalendarFilters
                open={openFilters.value}
                onClose={openFilters.onFalse}
                //
                filters={filters}
                onFilters={handleFilters}
                //
                canReset={canReset}
                onResetFilters={handleResetFilters}
                //
                dateError={dateError}
                //
                events={[]}
                // colorOptions={CALENDAR_COLOR_OPTIONS}
                onClickEvent={onClickEventInFilters}
            />
        </>
    );
}

// ----------------------------------------------------------------------

function applyFilter({ inputData, filters, dateError }) {
    const { colors, startDate, endDate } = filters;

    const stabilizedThis = inputData.map((el, index) => [el, index]);

    inputData = stabilizedThis.map((el) => el[0]);

    if (colors.length) {
        inputData = inputData.filter((event) => colors.includes(event.color));
    }

    if (!dateError) {
        if (startDate && endDate) {
            inputData = inputData.filter((event) =>
                isBetween(event.start, startDate, endDate)
            );
        }
    }

    return inputData;
}

const getEventColor = (startDate, endDate, allDay) => {
    const now = new Date();
    const eventStartTime = new Date(startDate);

    // Fallback logic for missing endDate
    let eventEndTime;
    if (endDate) {
        eventEndTime = new Date(endDate);
    } else if (allDay) {
        eventEndTime = new Date(eventStartTime);
        eventEndTime.setDate(eventStartTime.getDate() + 1); // Default to the next day for all-day events
    } else {
        // Default duration (e.g., 1 hour) for timed events with no endDate
        eventEndTime = new Date(eventStartTime);
        eventEndTime.setHours(eventStartTime.getHours() + 1);
    }

    // Ensure eventEndTime is valid
    if (!eventEndTime || isNaN(eventEndTime.getTime())) {
        console.error(
            "Missing or invalid end date, unable to calculate event color.",
            {
                startDate,
                endDate,
                allDay,
            }
        );
        return "default";
    }

    // Check if the event has ended
    if (eventEndTime < now) {
        return "red"; // Past event
    }

    // Check if the event is ongoing or about to start
    const timeDifference = eventStartTime - now;
    const isWithin15Minutes =
        timeDifference <= 15 * 60 * 1000 && timeDifference >= 0;
    const isBetweenStartAndEnd = now >= eventStartTime && now <= eventEndTime;

    if (isWithin15Minutes || isBetweenStartAndEnd) {
        return "yellow";
    }

    return "default";
};
