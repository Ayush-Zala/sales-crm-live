import PaginatedTable from "@/Components/SimplePaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { Head, usePage } from "@inertiajs/react";
import { Grid } from "@mui/material";
import ZoomMeetingLogsCellComponent from "./ZoomMeetingLogsCellComponent";
import ZoomMeetingUserSearchFilter from "./ZoomMeetingUserSearchFilter";
import ZoomMeetingLogDataSearch from "./ZoomMeetingLogDataSearch";
import ZoomMeetingAnalytics from "./ZoomMeetingAnalytics";
import ZoomMeetingThirdSection from "./ZoomMeetingThirdSection";
import ZoomCallDateFilter from "../ZoomCallLogs/ZoomCallDateFilter";
import ZoomSyncButton from "../ZoomCallLogs/ZoomSyncButton";

const Index = ({ auth, zoomMeetings, users, analytics }) => {
    const page = usePage();
    const params = extractUrlParams(page.url);

    const allColumns = [
        {
            id: "topic",
            label: "Topic",
            width: "25%",
            align: "left",
        },
        {
            id: "user_name",
            label: "User",
            textAlign: "left",
        },
        {
            id: "participants",
            label: "Participants",
            textAlign: "left",
        },
        {
            id: "start_time",
            label: "Start Time",
            textAlign: "left",
        },
        {
            id: "transcript",
            label: "Transcript",
            textAlign: "left",
        },
        {
            id: "duration",
            label: "Duration",
            textAlign: "left",
        },
        {
            id: "action",
            label: "Action",
            align: "right",
        },
    ];

    const { current_page, per_page, total, data: rows } = zoomMeetings;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Zoom meetings logs" />
            <MainContentTemplate
                title={"Zoom Meeting Logs"}
                subtitle={"View all zoom meeting logs"}
                buttonComponent={<ZoomSyncButton auth={auth} type="meetings" />}
            >
                <Grid
                    container
                    item
                    xs={12}
                    sx={{
                        display: "flex",
                        justifyContent: "flex-end",
                        alignItems: "center",
                        gap: 2,
                        mb: 3,
                    }}
                >
                    <Grid item>
                        <ZoomCallDateFilter startDateParam={params.start_date} endDateParam={params.end_date} />
                    </Grid>
                    <Grid item xs={2}>
                        <ZoomMeetingUserSearchFilter
                            users={users}
                            search={params.user}
                        />
                    </Grid>
                    <Grid item xs={3}>
                        <ZoomMeetingLogDataSearch search={params.search} />
                    </Grid>
                </Grid>
                <Grid item xs={12} mt={3}>
                    <ZoomMeetingAnalytics analytics={analytics} />
                </Grid>
                <Grid item xs={12}>
                    <ZoomMeetingThirdSection analytics={analytics} />
                </Grid>
                <Grid item xs={12}>
                    <PaginatedTable
                        columns={allColumns}
                        rows={rows}
                        current_page={current_page}
                        per_page={per_page}
                        total={total}
                        url={page.url}
                        CellComponent={ZoomMeetingLogsCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Index;
