import { Head } from "@inertiajs/react";

import { CalendarView } from "@/Components/Calendar/view";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import useEventsStore from "@/store/events-store";
import { useEffect } from "react";

const Calendar = ({ auth, calendardata }) => {
    const { setEvents } = useEventsStore();

    useEffect(() => {
        setEvents(calendardata);

        return () => setEvents([]);
    }, [calendardata]);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Events" />
            <CalendarView />
        </AuthenticatedLayout>
    );
};

export default Calendar;
