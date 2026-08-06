import { yupResolver } from "@hookform/resolvers/yup";
import { Head } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    Paper,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
    Box,
} from "@mui/material";
import { includes } from "lodash";
import { Fragment, useEffect, useMemo, useState } from "react";
import {
    AutocompleteElement,
    SelectElement,
    useForm,
} from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as yup from "yup";

import DateRangePickerWithFilters from "@/Components/DateRangePickerPopOver";
import SimpleDataTable from "@/Components/SimpleDataTable";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import DetailsReportTableCell from "./DashboardComponents/DetailsReportTableCell";
import DispositionDataCellComponent from "./DashboardComponents/DispositionDataCellComponent";
import EventsTableCellComponent from "./DashboardComponents/EventsTableCellComponent";
import TotalSalesMade from "./DashboardComponents/MetaCardsComponents/TotalSalesMade";
import MetaDataCard from "./DashboardComponents/MetaDataCard";
import TargetsTableCell from "./DashboardComponents/TargetsTableCell";
import { AssignTargetComponent } from "./DashboardComponents/dialogs/AssignTargetComponent";
import TotalCallsMade from "./DashboardComponents/MetaCardsComponents/TotalCallsMade";
import AnalyticsOverview from "./DashboardComponents/AnalyticsOverview";
import { hasRole } from "@/utils/AccessManager";
import { ShoppingCart, Users, UserX, Activity, CalendarX } from "lucide-react";

export default function Dashboard({ auth, detail, reportData }) {
    const { roles } = auth;

    const isAdminOrManager =
        includes(roles, "Admin") ||
        includes(roles, "Business Development Manager");

    const isAdmin = includes(roles, "Admin");

    const isDEMOrDE = hasRole(auth, ["Data Entry Manager", "Data Entry"]);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Dashboard" />
            <MainContentTemplate
                title={`Welcome ${String(detail.name).trim().toUpperCase()} !`}
                subtitle="Dashboard to show the overall performance"
            >
                {!isDEMOrDE ? (
                    <Grid item container columns={12} spacing={2} xs={12}>
                        <Grid item xs={12} md={6} lg={3}>
                            {!isAdminOrManager ? (
                                <TotalCallsMade
                                    count={detail.total_call_made}
                                    trend={detail.trends?.total}
                                    sparklineData={detail.kpiSparklineData?.map(d => d.total_calls)}
                                />
                            ) : (
                                <TotalSalesMade
                                    count={detail.total_sales_made}
                                    trend={detail.trends?.sales}
                                    sparklineData={detail.kpiSparklineData?.map(d => d.sales)}
                                />
                            )}
                        </Grid>

                        <Grid item xs={12} md={6} lg={3}>
                            <MetaDataCard
                                format
                                title="Total Assigned"
                                count={detail.totalassigned || 0}
                                trend={detail.trends?.assigned}
                                trendLabel={detail.trends?.label}
                                icon={<Users size={24} color="#1976d2" />}
                                iconBg="#e3f2fd"
                            />
                        </Grid>

                        {detail.target_value >= 0 && (
                            <Grid item xs={12} md={6} lg={3}>
                                <MetaDataCard
                                    format
                                    title="Target Value"
                                    count={detail.target_value || 0}
                                />
                            </Grid>
                        )}

                        {detail.unassigned >= 0 && (
                            <Grid item xs={12} md={6} lg={3}>
                                <MetaDataCard
                                    format
                                    title="Unassigned"
                                    count={detail.unassigned || 0}
                                    trend={detail.trends?.unassigned}
                                    trendLabel={detail.trends?.label}
                                    icon={<UserX size={24} color="#f44336" />}
                                    iconBg="#ffebee"
                                />
                            </Grid>
                        )}

                        {detail.online_users >= 0 && (
                            <Grid item xs={12} md={6} lg={3}>
                                <MetaDataCard
                                    title="Online Users / Total Active Users"
                                    count={detail.online_users || 0}
                                    extraCount={detail.total_users || 0}
                                    icon={<Activity size={24} color="#4caf50" />}
                                    iconBg="#e8f5e9"
                                />
                            </Grid>
                        )}

                        {detail.target_percentage >= 0 && (
                            <Grid item xs={12} md={6} lg={3}>
                                <MetaDataCard
                                    title={`Target Achieved (${detail.target_month})`}
                                    count={`${detail.target_achieved}/${detail.target_value} (${detail.target_percentage}%)`}
                                />
                            </Grid>
                        )}

                        {detail.total_zoom_calls >= 0 && (
                            <Grid item xs={12} md={6} lg={3}>
                                <MetaDataCard
                                    title="Total Zoom Calls"
                                    count={detail.total_zoom_calls}
                                    trend={detail.trends?.zoom}
                                    sparklineData={detail.analyticsOverview?.dailyData?.map(d => d.zoom_calls)}
                                    icon={<Video size={24} color="#ff9800" />}
                                    iconBg="#fff3e0"
                                />
                            </Grid>
                        )}

                        {detail.analyticsOverview && (
                            <Grid item xs={12}>
                                <AnalyticsOverview data={detail.analyticsOverview} />
                            </Grid>
                        )}

                        {isAdminOrManager && (
                            <Grid item xs={12} md={6}>
                                <CallTargetComponent
                                    targets={detail.targets}
                                    isAdminOrManager={isAdminOrManager}
                                    managerList={reportData.manager}
                                    isAdmin={isAdmin}
                                />
                            </Grid>
                        )}

                        {isAdminOrManager && (
                            <Grid item xs={12} md={6}>
                                <DetailReportTable reportData={reportData} />
                            </Grid>
                        )}

                        <Grid item xs={12}>
                            <EventTableComponent
                                managerList={reportData.manager}
                                usersList={detail.user_detail}
                                events={detail.today_events}
                                isAdminOrManager={isAdminOrManager}
                            />
                        </Grid>
                    </Grid>
                ) : (
                    <Grid item container columns={12} spacing={2} xs={12}>
                        <Grid item xs={12}>
                            <Typography>
                                Dashboard is under development and you will get
                                the updates soon.
                            </Typography>
                        </Grid>
                    </Grid>
                )}
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
}

const DetailReportTable = ({ reportData }) => {
    const [report, setReport] = useState(reportData || {});
    const [menuName, setMenuName] = useState("life");

    const [open, setOpen] = useState(false); // State for dialog
    const [callData, setCallData] = useState({}); // State for call data

    const temp = reportData?.manager;
    const [employeeName, setEmployeeName] = useState(temp?.[0]?.name || []);

    const defaultValues = {
        type: menuName,
        employee: reportData.manager?.[0]?.id || "",
    };

    // Define the schema for the form using yup
    const schema = yup.object().shape({
        type: yup.string().required("Please select a type"),
        employee: yup.string().required("Please select an employee"),
    });

    // useForm hook to handle the form
    const {
        control,
        watch,
        setValue,
        formState: { isLoading, isSubmitting },
        handleSubmit,
    } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const columns = [
        { id: "name", label: "Name" },
        { id: "totalCalls", label: "Total Calls" },
        { id: "zoomCalls", label: "Zoom Calls" },
        { id: "crmCalls", label: "CRM Calls" },
        { id: "totalSales", label: "Total Sales" },
    ];

    const options = [
        { label: "Life", value: "life" },
        { label: "Today", value: "today" },
        { label: "Yesterday", value: "yesterday" },
        { label: "Last 7 Days", value: "last_7_day" },
        { label: "Last 30 Days", value: "last_30_day" },
    ];

    const employeeOptions = reportData.manager?.map((manager) => ({
        value: manager.id,
        label: manager.name,
    }));

    const rows = useMemo(() => {
        if (employeeName.length > 0) {
            return report.manager[0].team.map((member) => ({
                id: member.id,
                name: member.name,
                totalCalls: member.totalCall,
                zoomCalls: member.zoomCalls,
                crmCalls: member.crmCalls,
                totalSales: member.totalSales,
            }));
        }
        return []; // Default to empty array
    }, [report]);

    useEffect(() => {
        setReport(reportData);
    }, [reportData]);

    useEffect(() => {
        setValue("employee", reportData?.manager?.[0]?.id || "");
        setMenuName("life"); // Reset menu name to default
    }, [reportData, setValue]);

    const handleClick = async () => {
        const watchType = watch("type", menuName);
        const watchEmployee = watch("employee", employeeName);

        // API call to fetch the updated report data
        fetch(
            route("dashboard.getreportdatamanagers", {
                userid: watchEmployee,
                duration: watchType,
            })
        )
            .then((response) => response.json())
            .then((res) => {
                setReport(res.reportData);
            })
            .catch((error) => {
                console.error("Error fetching data", error);
            });
    };

    const handleOpen = (row) => {
        if (!row) return; // Handle case when row is undefined or null

        // Get the selected employee ID and duration type
        const selectedEmployee = row.id;
        const employeeName = row.name;
        const selectedDuration = watch("type", menuName);

        // Make the API call with the selected employee and duration
        fetch(
            route("dashboard.getreportdata", {
                userid: selectedEmployee,
                duration: selectedDuration,
            })
        )
            .then((response) => response.json())
            .then((res) => {
                setCallData({
                    report: res.reportData,
                    name: employeeName,
                    id: selectedEmployee,
                }); // Store the data for display in the dialog
                setOpen(true);
            })
            .catch((error) => {
                console.error("Error fetching data", error); // Handle error
            });
    };

    const handleClose = () => setOpen(false);

    return (
        <>
            <Grid
                item
                container
                columns={12}
                spacing={2}
                xs={12}
                lg={12}
                sx={{
                    justifyContent: "flex-start",
                    alignItems: "center",
                }}
            >
                <Grid item xs={3}>
                    <Typography
                        noWrap
                        component="h1"
                        variant="h5"
                        fontWeight="700"
                        lineHeight={1}
                    >
                        Call Reports
                    </Typography>
                </Grid>
                <Grid item xs={6}>
                    <Stack direction="row" spacing={2}>
                        <SelectElement
                            control={control}
                            name="type"
                            options={options}
                            labelKey="label"
                            valueKey="value"
                            onChange={handleSubmit(handleClick)}
                            SelectProps={{
                                MenuProps: {
                                    slotProps: {
                                        paper: {
                                            style: {
                                                maxHeight: 300,
                                            },
                                        },
                                    },
                                },
                            }}
                        />
                        <SelectElement
                            control={control}
                            name="employee"
                            options={employeeOptions}
                            labelKey="label"
                            valueKey="value"
                            onChange={handleSubmit(handleClick)}
                            SelectProps={{
                                MenuProps: {
                                    slotProps: {
                                        paper: {
                                            maxHeight: 280,
                                        },
                                    },
                                },
                            }}
                        />
                    </Stack>
                </Grid>
                <Grid item xs={12}>
                    <SimpleDataTable
                        columns={columns}
                        rows={rows}
                        tableMaxHeight={"calc(100vh - 370px)"}
                        CellComponent={DetailsReportTableCell}
                        clickableRow={true}
                        handleClickRow={handleOpen}
                        isLoading={isSubmitting}
                        skeletonRows={10}
                    />
                </Grid>
            </Grid>
            <DetailReportDialog
                open={open}
                onClose={handleClose}
                data={callData.report}
                name={callData.name}
                userId={callData.id}
                filter={watch("type", menuName)}
            />
        </>
    );
};

const CallTargetComponent = ({
    targets,
    isAdminOrManager,
    isAdmin,
    managerList,
}) => {
    // Create an object to group by time and accumulate target_achieved
    const [result, setResult] = useState([]);
    const [userData, setUserData] = useState([]);
    const [assignButtonLogin, setAssignButtonLogin] = useState(false);

    const year = new Date().getFullYear();
    const currentMonth = new Date()
        .toLocaleString("default", { month: "short" })
        .toUpperCase();

    // List of months for dropdown
    const months = [
        "JAN",
        "FEB",
        "MAR",
        "APR",
        "MAY",
        "JUN",
        "JUL",
        "AUG",
        "SEP",
        "OCT",
        "NOV",
        "DEC",
    ];

    const defaultValues = {
        month: currentMonth,
        year: year,
        manager: { label: "All", value: null } || null,
    };
    const schema = yup.object().shape({
        month: yup.string().required("Please select a month"),
        year: yup.number().required("Please select a year"),
        manager: yup.string().nullable(),
    });

    const {
        control,
        watch,
        handleSubmit,
        formState: { isLoading, isSubmitting },
    } = useForm({
        defaultValues,
        yupResolver: schema,
    });

    // List of years from 2017 to current year
    const years = Array.from(
        { length: year - 2017 + 1 },
        (_, index) => 2017 + index
    );

    const [open, setOpen] = useState(false);

    const handleOpen = () => {
        setAssignButtonLogin(true);
        fetch(
            route("target.getuserswithtargets", {
                month: watch("month"),
                year: watch("year"),
            })
        )
            .then((response) => {
                response.json().then((res) => {
                    // set the result
                    setUserData(res.targets);
                    setOpen(true);
                });
            })
            .catch((error) => {
                console.error("Error fetching data", error);
                toast.error("Error fetching data");
            })
            .finally(() => {
                setAssignButtonLogin(false);
            });
    };

    const handleClose = () => {
        setOpen(false);
    };

    useEffect(() => {
        const tempResult = [];

        targets &&
            targets.forEach((item) => {
                const { name } = item;

                // Check if the person already exists in the tempResult array
                const existingPerson = tempResult.find(
                    (person) => person.name === name
                );

                if (existingPerson) {
                    // If they exist, sum the target_achieved
                    existingPerson.target_achieved += item.target_achieved;
                } else {
                    // If not, add the person to the tempResult array
                    tempResult.push({
                        user_id: item.user_id,
                        target_achieved: item.target_achieved,
                        name: item.name,
                        target_value: item.target_value,
                        time: item.time,
                    });
                }
            });

        // After the loop, set the result state once
        setResult(tempResult);
    }, [targets]);

    const submit = (data) => {
        // api call
        fetch(
            route("target.getmonthdata", {
                ...data,
                manager: data.manager?.value || null,
            })
        ).then((response) => {
            if (response.ok) {
                response.json().then((res) => {
                    // set the result
                    setResult(res.data);
                });
            } else {
                toast.error("Error fetching data");
            }
        });
    };

    const columns = [
        { id: "name", label: "Name" },
        { id: "target_value", label: "Total Target" },
        { id: "target_achieved", label: "Target Achieved" },
        { id: "time", label: "Time" },
    ];

    return (
        <Fragment>
            <Grid
                item
                container
                columns={12}
                spacing={2}
                xs={12}
                alignItems="center"
            >
                <Grid item xs={2}>
                    <Typography
                        noWrap
                        component="h1"
                        variant="h5"
                        fontWeight="700"
                        lineHeight={1}
                    >
                        Targets
                    </Typography>
                </Grid>
                {isAdminOrManager && (
                    <Grid
                        item
                        container
                        columns={12}
                        spacing={2}
                        xs={10}
                        alignItems="center"
                    >
                        <Grid item xs={3}>
                            {/* Month Dropdown */}
                            <SelectElement
                                control={control}
                                name="month"
                                label="Month"
                                options={months.map((month) => ({
                                    label: month,
                                    value: month,
                                }))}
                                labelKey="label"
                                valueKey="value"
                                SelectProps={{
                                    MenuProps: {
                                        slotProps: {
                                            paper: {
                                                style: {
                                                    maxHeight: 300,
                                                },
                                            },
                                        },
                                    },
                                }}
                                onChange={handleSubmit(submit)}
                            />
                        </Grid>
                        <Grid item xs={3}>
                            {/* Year Dropdown */}
                            <SelectElement
                                control={control}
                                name="year"
                                label="Year"
                                options={years.map((year) => ({
                                    label: year,
                                    value: year,
                                }))}
                                labelKey="label"
                                valueKey="value"
                                SelectProps={{
                                    MenuProps: {
                                        slotProps: {
                                            paper: {
                                                style: {
                                                    maxHeight: 300,
                                                },
                                            },
                                        },
                                    },
                                }}
                                onChange={handleSubmit(submit)}
                            />
                        </Grid>
                        {isAdmin && (
                            <Grid item xs={3}>
                                {/* User Dropdown */}
                                <AutocompleteElement
                                    control={control}
                                    name="manager"
                                    label="Manager"
                                    options={[
                                        { label: "All", value: null },
                                        ...managerList.map((manager) => ({
                                            label: manager.name,
                                            value: manager.id,
                                        })),
                                    ]}
                                    labelKey="label"
                                    valueKey="value"
                                    SelectProps={{
                                        MenuProps: {
                                            slotProps: {
                                                paper: {
                                                    style: {
                                                        maxHeight: 300,
                                                    },
                                                },
                                            },
                                        },
                                    }}
                                    autocompleteProps={{
                                        onChange: handleSubmit(submit),
                                    }}
                                />
                            </Grid>
                        )}
                        <Grid item xs={3}>
                            <LoadingButton
                                fullWidth
                                variant="contained"
                                onClick={handleOpen}
                                loading={assignButtonLogin}
                            >
                                Assign
                            </LoadingButton>
                        </Grid>
                    </Grid>
                )}
                <Grid item xs={12}>
                    <SimpleDataTable
                        columns={columns}
                        rows={result}
                        tableMaxHeight={"calc(100vh - 370px)"}
                        CellComponent={TargetsTableCell}
                    />
                </Grid>
            </Grid>
            <AssignTargetComponent
                open={open}
                handleClose={handleClose}
                targets={userData}
                month={watch("month")}
                year={watch("year")}
            />
        </Fragment>
    );
};

const EventTableComponent = ({
    usersList,
    managerList,
    events,
    isAdminOrManager,
}) => {
    // State management
    const [event, setEvent] = useState(
        events || {} // Fallback to an empty object if no events
    );

    // Options for the employee select
    const employeeOptions =
        isAdminOrManager &&
        usersList.map((key) => ({
            value: key.userid,
            label: key.username,
        }));

    const columns = [
        { id: "title", label: "Title" },
        { id: "company_name", label: "Company" },
        { id: "user_name", label: "User" },
        { id: "description", label: "Description" },
        { id: "start_date", label: "Start Date" },
        { id: "end_date", label: "End Date" },
        { id: "timezone", label: "TimeZone" },
        { id: "all_day", label: "All Day" },
        { id: "repeat_rule", label: "Repeat Rule" },
    ];

    return (
        <Grid
            item
            container
            columns={12}
            spacing={2}
            xs={12}
            sx={{ alignItems: "center" }}
        >
            <Grid item>
                <Typography
                    noWrap
                    component="h1"
                    variant="h5"
                    fontWeight="700"
                    lineHeight={1}
                >
                    Events
                </Typography>
            </Grid>

            {isAdminOrManager && (
                <Grid item xs={12} sm={10}>
                    <DateRangePickerWithFilters
                        apiRoute="calendar.geteventbyrange"
                        setData={setEvent}
                        managerOptions={managerList}
                        userList={employeeOptions}
                    />
                </Grid>
            )}

            <Grid item xs={12}>
                <SimpleDataTable
                    columns={columns}
                    rows={event}
                    tableMaxHeight={"calc(100vh - 370px)"}
                    CellComponent={EventsTableCellComponent}
                    noDataMessage={
                        <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', py: 5, gap: 2 }}>
                            <Box sx={{ bgcolor: '#f3e5f5', p: 2, borderRadius: '50%' }}>
                                <CalendarX size={48} color="#9c27b0" />
                            </Box>
                            <Typography variant="h6" color="text.secondary" fontWeight="bold">
                                No Events Found
                            </Typography>
                            <Typography variant="body2" color="text.secondary">
                                You have no upcoming events for this period.
                            </Typography>
                        </Box>
                    }
                />
            </Grid>
        </Grid>
    );
};

const DetailReportDialog = ({ open, onClose, data, name, userId, filter }) => {
    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth="sm"
            fullWidth
        >
            <DialogTitle>{`${name}'s Detail Report`}</DialogTitle>
            <DialogContent dividers>
                <DispositionTable data={data} userId={userId} filter={filter} />
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Cancel
                </Button>
            </DialogActions>
        </Dialog>
    );
};

const DispositionTable = ({ data, userId, filter }) => {
    const [open, setOpen] = useState(false);
    const [dispositionData, setDispositionData] = useState(null);

    const handleOpen = () => setOpen(true);
    const handleClose = () => setOpen(false);

    // Convert the object to an array of rows, excluding the total row
    const rows = Object.entries(data)
        .filter(([disposition]) => disposition !== "Total") // Exclude the "Total" disposition from rows
        .map(([disposition, calls]) => ({
            disposition,
            calls,
        }));

    const totalCalls = data["Total"]; // Get the total from the data

    const handleRowClick = (disposition) => {
        fetch(
            route("dashboard.getDispositionCallDetails", {
                dispositionName: disposition,
                userId,
                filter,
            })
        )
            .then((response) => response.json())
            .then((data) => {
                setDispositionData(data.dispositionDetails);
                handleOpen();
            })
            .catch((error) => {
                console.error("Error fetching data", error);
            });
    };

    return (
        <>
            <TableContainer component={Paper}>
                <Table>
                    <TableHead>
                        <TableRow>
                            <TableCell>Disposition</TableCell>
                            <TableCell>Calls</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {rows.map((row, index) => (
                            <TableRow
                                key={index}
                                onClick={() => handleRowClick(row.disposition)}
                                sx={{ cursor: "pointer" }}
                            >
                                <TableCell>{row.disposition}</TableCell>
                                <TableCell>{row.calls}</TableCell>
                            </TableRow>
                        ))}

                        {/* Total row */}
                        <TableRow>
                            <TableCell>
                                <Typography
                                    variant="body1"
                                    style={{ fontWeight: "bold" }}
                                >
                                    Total
                                </Typography>
                            </TableCell>
                            <TableCell>
                                <Typography
                                    variant="body1"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {totalCalls}
                                </Typography>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </TableContainer>

            {/* Display the disposition data details */}
            <DispositionDataDetails
                data={dispositionData}
                open={open}
                onClose={handleClose}
            />
        </>
    );
};

const DispositionDataDetails = ({ data, open, onClose }) => {
    const columns = [
        { id: "company_name", label: "Company Name" },
        { id: "user_name", label: "User Name" },
        { id: "updated_at", label: "Date" },
    ];

    const rows =
        data &&
        data.map((item) => ({
            company_name: item.company_name,
            user_name: item.user_name,
            updated_at: item.updated_at,
            company_id: item.company_id,
        }));

    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth="md"
            fullWidth
        >
            <DialogTitle>Disposition Details</DialogTitle>
            <DialogContent dividers>
                <SimpleDataTable
                    columns={columns}
                    rows={rows}
                    tableMaxHeight={"calc(100vh - 370px)"}
                    CellComponent={DispositionDataCellComponent}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
