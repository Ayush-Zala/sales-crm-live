import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

import { MainContentTemplate } from "@/Layouts/components/main-content-template";
import { Head } from "@inertiajs/react";
import {
    Button,
    CardActions,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Divider,
    Grid,
    IconButton,
    MenuItem,
    Select,
    Toolbar,
    Tooltip,
} from "@mui/material";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";

import CardContent from "@mui/material/CardContent";

import { BackupTableRounded } from "@mui/icons-material";
import DonutSmallIcon from "@mui/icons-material/DonutSmall";
import Typography from "@mui/material/Typography";

import { format } from "date-fns";

import { Datatable } from "@/Components/datatable";
import { Fragment, useState } from "react";
import { FormContainer } from "react-hook-form-mui";
import UserReportTableCell from "./UserReportComponants/UserReportTableCell";
import { BarChart } from "@mui/x-charts/BarChart";

const Report = ({ auth, report }) => {
    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="User report" />
            <MainContentTemplate
                title="User report"
                subtitle="View user reports here"
                button="Go back"
                href={route("user")}
            >
                <Grid item container columns={12} spacing={2}>
                    <Grid item container columns={12} spacing={2}>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Total Assigned"
                                value={report.total_assigned}
                                report={report}
                                disabled={true}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Currently Assigned"
                                report={report}
                                disabled={true}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Unassigned"
                                value={report.unassigned}
                                report={report}
                                disabled={true}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Total Calls Made"
                                value={report.total_call_made}
                                report={report.detail_report?.life}
                            />
                        </Grid>
                    </Grid>
                    <Grid item container columns={12} spacing={2}>
                        <Grid item xs={12} sm={6}>
                            <BasicTable
                                report={report.detail_report}
                                name={report.name}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Total Calls Made (EST)"
                                subTitle="Today"
                                value=""
                                report={report.detail_report?.today}
                            />
                        </Grid>
                        <Grid item xs={12} sm={6} lg={3}>
                            <CardPage
                                title="Contacted Companies (EST)"
                                subTitle="Today"
                                value=""
                                report={report}
                                disabled={true}
                            />
                        </Grid>
                    </Grid>
                </Grid>
            </MainContentTemplate>
        </AuthenticatedLayout>
    );
};

export default Report;

const CardPage = ({ title, subTitle, value, report, disabled }) => {
    const todaysDate = format(new Date(), "yyyy-MM-dd");

    const [open, setOpen] = useState(false);

    const handleOpen = () => {
        setOpen(true);
    };
    const handleClose = () => {
        setOpen(false);
    };

    return (
        <>
            <Card
                sx={{
                    maxHeight: "220px",
                    borderTop: "5px solid #0077cb",
                }}
            >
                <CardContent>
                    <Grid item>
                        <Typography
                            gutterBottom
                            sx={{
                                color: "text.secondary ",
                                fontSize: 15,
                                display: "flex",
                                justifyContent: "start",
                            }}
                        >
                            {title}
                        </Typography>

                        {subTitle && (
                            <Typography
                                gutterBottom
                                sx={{
                                    color: "text.secondary ",
                                    fontSize: 15,
                                    display: "flex",
                                    justifyContent: "start",
                                }}
                            >
                                {`${subTitle} (${todaysDate}) `}
                            </Typography>
                        )}

                        <Typography
                            sx={{
                                color: "text.primery",
                                fontSize: 45,
                                display: "flex",
                                justifyContent: "center",
                            }}
                        >
                            {value || 0}
                        </Typography>
                    </Grid>
                </CardContent>
                <Divider />
                <CardActions disableSpacing>
                    <IconButton
                        children={
                            <Tooltip title="Table" placement="top">
                                <BackupTableRounded />
                            </Tooltip>
                        }
                    />

                    <IconButton
                        onClick={handleOpen}
                        disabled={disabled}
                        children={
                            <Tooltip title="Graph" placement="top">
                                <DonutSmallIcon />
                            </Tooltip>
                        }
                    />

                    <GraphReport
                        open={open}
                        handleClose={handleClose}
                        report={report}
                    />
                </CardActions>
            </Card>
        </>
    );
};

const BasicTable = ({ report, name }) => {
    const [menuName, setMenuName] = useState("today");

    // const selectedData = report[menuName] || {};
    const [selectedData, setselectedData] = useState(report[menuName] || {});

    // Define table columns
    const columns = [
        { name: "disposition", title: "Disposition" },
        { name: "count", title: "Count" },
    ];

    // Options for the select element
    const options = Object.keys(report).map((key) => ({
        value: key,
        label: key.charAt(0).toUpperCase() + key.slice(1), // Capitalize the first letter of each key
    }));

    // Convert dynamic report object into an array of rows
    const rows = Object.entries(selectedData).map(([key, value]) => ({
        disposition: key,
        count: String(value),
    }));

    const handleChange = (e) => {
        setMenuName(e.target.value);
        setselectedData(report[e.target.value]);
    };

    return (
        <>
            <Toolbar>
                <Typography variant="h6" id="tableTitle" component="div">
                    {`${
                        name.charAt(0).toUpperCase() + name.slice(1)
                    }'s Reports`}
                </Typography>

                <FormContainer defaultValues={{ type: menuName }}>
                    <Box sx={{ ml: 2 }}>
                        <Select
                            defaultValue="today"
                            name="type"
                            onChange={handleChange}
                            sx={{ height: "30px" }}
                        >
                            {options.map((option) => (
                                <MenuItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </MenuItem>
                            ))}
                        </Select>
                    </Box>
                </FormContainer>
            </Toolbar>

            {/* Render the table using the Datatable component */}
            <Datatable
                columns={columns} // Pass the columns here
                rows={rows} // Pass the dynamically converted rows here
                cellComponent={UserReportTableCell} // Pass the cell component
            />
        </>
    );
};

const GraphReport = ({ open, handleClose, report }) => {
    // Prepare the data for the PieChart in the correct format
    const colors = [
        "green",
        "red",
        "blue",
        "orange",
        "nevyblue",
        "orange",
        "cream",
        "yellow",
        "purple",
        "black",
        "violet",
        "pink",
        "brown",
        "grey",
        "skyblue",
    ];

    // Prepare the x-axis labels (keys) and y-axis data (values)
    const xLabels = report
        ? Object.keys(report).map(
              (key) => key.charAt(0).toUpperCase() + key.slice(1)
          )
        : [];

    const dataValues = report
        ? Object.values(report).map((value) => ({
              value,
          }))
        : [];

    // Extract the data values (numbers) and colors for each bar
    const dataSeries = dataValues.map(({ value }) => value);

    // Create series data for the BarChart
    const seriesData = [
        {
            label: "Report Data",
            data: dataSeries, // Extracted data values for the bars
            colors: colors,
        },
    ];

    // const xLabels = report
    //     ? Object.keys(report).map(
    //           (key) => key.charAt(0).toUpperCase() + key.slice(1)
    //       )
    //     : [];

    // const dataValues = report
    //     ? Object.values(report).map(
    //           (value, index) => (value, colors[index % colors.length])
    //       )
    //     : [];

    // const dataValues = report ? Object.values(report) : [];
    // const seriesData = [
    //     {
    //         label: xLabels,
    //         data: dataValues,
    //         colors: dataValues.map(
    //             (value, index) => (value, colors[index % colors.length])
    //         ),
    //     },
    // ];

    // const chartColors = dataValues.map((item) => item.color);

    // Create a separate series for each bar with its corresponding color
    // const seriesData = [
    //     {
    //         label: "Report Data", // Series label
    //         data: dataValues, // Array of Y-axis values
    //         color: colors, // Array of colors for the bars

    //     },
    // ];

    // const seriesData = {
    //     label: "All Data", // Label for the entire series
    //     data: dataValues, // All Y-axis values in a single array
    //     color: colors[index % colors.length], // Assign a single color to the entire series
    // };

    return (
        <Fragment>
            <Dialog
                open={open}
                aria-labelledby="alert-dialog-title"
                aria-describedby="alert-dialog-description"
                onClose={(_, reason) =>
                    reason !== "backdropClick" && handleClose()
                }
                maxWidth="lg"
            >
                <DialogTitle>Graph</DialogTitle>
                <DialogContent dividers>
                    {/* Pass the pieChartData directly as the data for the PieChart */}
                    <Box
                        sx={{ display: "flex", justifyItems: "space-between" }}
                    >
                        {/* <BarChart
                            width={900}
                            height={500}
                            series={[
                                {
                                    data: seriesData.map((item) => item.value),
                                    label: "Report Data",
                                    id: "reportId",
                                },
                            ]}
                            xAxis={[{ data: xLabels, scaleType: "band" }]}
                            colors={seriesData.map(({ color }) => color)}
                        /> */}
                        {/* <BarChart
                            width={900}
                            height={400}
                            series={[
                                {
                                    data: seriesData,
                                    label: "Report Data",
                                    id: "reportId",
                                },
                            ]}
                            xAxis={[{ data: xLabels, scaleType: "band" }]}
                            colors={seriesData.map((data) => data.color)}
                        /> */}

                        <BarChart
                            width={900}
                            height={400}
                            series={seriesData}
                            xAxis={[{ data: xLabels, scaleType: "band" }]}

                            // colors={[
                            //     "green",
                            //     "red",
                            //     "blue",
                            //     "orange",
                            //     "nevyblue",
                            //     "orange",
                            //     "cream",
                            //     "yellow",
                            //     "purple",
                            //     "black",
                            //     "violet",
                            //     "pink",
                            //     "brown",
                            //     "grey",
                            //     "skyblue",
                            // ]} // Pass the color array here
                        />
                    </Box>
                </DialogContent>
                <DialogActions>
                    <Button onClick={handleClose}>Close</Button>
                </DialogActions>
            </Dialog>
        </Fragment>
    );
};
