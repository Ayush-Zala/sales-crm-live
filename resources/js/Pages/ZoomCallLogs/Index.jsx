import PaginatedTable from "@/Components/SimplePaginatedTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { extractUrlParams } from "@/utils/ExtractUrlParams";
import { Head, usePage } from "@inertiajs/react";
import { Grid } from "@mui/material";
import ZoomCallLogsCellComponent from "./ZoomCallLogsCellComponent";
import ZoomCallFilter from "./ZoomCallFilter";
import ZoomUserSearchFilter from "./ZoomUserSearchFilter";
import ZoomCallLogDataSearch from "./ZoomCallLogDataSearch";
import ZoomCallAnalytics from "./ZoomCallAnalytics";
import ZoomCallDateFilter from "./ZoomCallDateFilter";
import ZoomCallCharts from "./ZoomCallCharts";
import ZoomCallAdvancedCharts from "./ZoomCallAdvancedCharts";
import ZoomSyncButton from "./ZoomSyncButton";

const Index = ({ auth, zoomCallLogs, users, analytics }) => {
    const page = usePage();
    const params = extractUrlParams(page.url);

    const allColumns = [
        {
            id: "result",
            label: "Result",
            align: "left",
        },
        {
            id: "caller_name",
            label: "Caller Name",
        },
        {
            id: "caller_number",
            label: "Caller",
        },
        {
            id: "callee_number",
            label: "Call To",
        },
        {
            id: "recording_id",
            label: "Transcript",
        },
        {
            id: "call_duration",
            label: "Call Duration",
        },
        {
            id: "talk_time",
            label: "Talk Time",
        },
        {
            id: "wait_time",
            label: "Wait Time",
        },
        {
            id: "hold_time",
            label: "Hold Time",
        },
        {
            id: "start_time",
            label: "Start Time",
        },
        {
            id: "answer_time",
            label: "Answer Time",
        },
        {
            id: "end_time",
            label: "End Time",
        },
        {
            id: "action",
            label: "Action",
            align: "right",
        },
    ];

    const { current_page, per_page, total, data: rows } = zoomCallLogs;

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Zoom call logs" />
            <MainContentTemplate
                title={"Zoom Call Logs"}
                subtitle={"View all zoom call logs"}
                buttonComponent={<ZoomSyncButton auth={auth} type="call_logs" />}
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
                    }}
                >
                    <Grid item>
                        <ZoomCallDateFilter startDateParam={params.start_date} endDateParam={params.end_date} />
                    </Grid>
                    <Grid item>
                        <ZoomCallFilter filter={params.filter} />
                    </Grid>
                    <Grid item xs={2}>
                        <ZoomUserSearchFilter
                            users={users}
                            search={params.user}
                        />
                    </Grid>
                    <Grid item xs={3}>
                        <ZoomCallLogDataSearch search={params.search} />
                    </Grid>
                </Grid>
                <Grid item xs={12} mt={3}>
                    <ZoomCallAnalytics analytics={analytics} />
                </Grid>
                <Grid item xs={12}>
                    <ZoomCallCharts analytics={analytics} />
                </Grid>
                <Grid item xs={12}>
                    <ZoomCallAdvancedCharts analytics={analytics} />
                </Grid>
                <Grid item xs={12}>
                    <PaginatedTable
                        columns={allColumns}
                        rows={rows}
                        current_page={current_page}
                        per_page={per_page}
                        total={total}
                        url={page.url}
                        CellComponent={ZoomCallLogsCellComponent}
                    />
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Index;
