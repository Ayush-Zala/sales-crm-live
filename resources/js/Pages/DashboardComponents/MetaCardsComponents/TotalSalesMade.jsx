import { FilterAltRounded } from "@mui/icons-material";
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    Grid,
    IconButton,
    Menu,
    MenuItem,
    Paper,
    Stack,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
} from "@mui/material";
import React from "react";
import MetaDataCard from "../MetaDataCard";
import { useState } from "react";
import { formatDate, formatDateTime } from "@/utils/date-time-formatters";
import { DateCalendar } from "@mui/x-date-pickers";
import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { DollarSign } from "lucide-react";

const TotalSalesMade = ({ count, trend, sparklineData }) => {
    const urlFilter = new URLSearchParams(window.location.search).get("filter");
    const [filter, setFilter] = useState(urlFilter || "life_time");

    const [salesDetailsDialog, setSalesDetailsDialog] = useState(false);
    const [salesDetails, setSalesDetails] = useState({});

    const handleSalesDetailsDialogOpen = () => setSalesDetailsDialog(true);
    const handleSalesDetailsDialogClose = () => setSalesDetailsDialog(false);

    const handleGetSalesDetails = () => {
        if (filter === "custom") {
            // If filter is custom, use the custom date range

            // Check if both dates are selected
            if (!dateRange[0] || !dateRange[1]) {
                console.error("Please select both start and end dates");
                toast.error("Please select both start and end dates");
                return;
            }

            // Call API to get sales details
            try {
                fetch(
                    route("dashboard.getSalesDetailsByDateRange", {
                        start_date: formatDate(
                            dateRange[0].toISOString(),
                            "yyyy-MM-dd"
                        ),
                        end_date: formatDate(
                            dateRange[1].toISOString(),
                            "yyyy-MM-dd"
                        ),
                    })
                )
                    .then((response) => response.json())
                    .then((data) => {
                        setSalesDetails(data.sales);
                        handleSalesDetailsDialogOpen();
                    });
            } catch (error) {
                console.error("Error fetching sales details", error);
                toast.error("Error fetching sales details");
            }

            return;
        }

        // Call API to get sales details
        try {
            fetch(route("dashboard.gettotalsalesdetails", { filter }))
                .then((response) => response.json())
                .then((data) => {
                    setSalesDetails(data.sales);
                    handleSalesDetailsDialogOpen();
                });
        } catch (error) {
            console.error("Error fetching sales details", error);
            toast.error("Error fetching sales details");
        }
    };

    return (
        <>
            <MetaDataCard
                format
                title="Total Sales Made"
                count={count || 0}
                trend={trend || 0}
                icon={<DollarSign size={24} color="#9c27b0" />}
                iconBg="#f3e5f5"
                sparklineData={sparklineData}
                isFilterAllowed={true}
                isLinkOnCount={true}
                handleClickOnCount={handleGetSalesDetails}
                filter={filter}
                setFilter={setFilter}
                FilterComponent={FilterComponent}
            />

            <SalesDetailsDialogBox
                open={salesDetailsDialog}
                onClose={handleSalesDetailsDialogClose}
                sales={salesDetails}
            />
        </>
    );
};

export default TotalSalesMade;

const FilterComponent = ({ filter, setFilter }) => {
    const options = [
        { label: "Today", value: "today" },
        { label: "Yesterday", value: "yesterday" },
        { label: "Last Week", value: "last_week" },
        { label: "This Month", value: "this_month" },
        { label: "Life Time", value: "life_time" },
        { label: "Custom", value: "custom" },
    ];

    // State for dropdown menu
    const [anchorEl, setAnchorEl] = useState(null);

    // Open/close dropdown menu
    const handleMenuOpen = (event) => setAnchorEl(event.currentTarget);
    const handleMenuClose = () => setAnchorEl(null);

    // Open/close custom date range dialog
    const [openDialog, setOpenDialog] = useState(false);
    const [dateRange, setDateRange] = useState([null, null]);

    // Open/close custom date range dialog
    const handleDialogOpen = () => setOpenDialog(true);
    const handleDialogClose = () => setOpenDialog(false);

    // Handle filter selection
    const handleFilterChange = (newFilter) => {
        setFilter(newFilter);

        if (newFilter === "custom") {
            handleDialogOpen();
            handleMenuClose();
        } else {
            useUpdateSearchParam(
                {
                    filter: newFilter,
                },
                "/dashboard"
            );

            handleMenuClose();
        }
    };

    return (
        <>
            <IconButton onClick={handleMenuOpen}>
                <FilterAltRounded sx={{ color: "primary.main" }} />
            </IconButton>
            <Menu
                anchorEl={anchorEl}
                open={Boolean(anchorEl)}
                onClose={handleMenuClose}
            >
                {options.map((option) => (
                    <MenuItem
                        key={option.value}
                        onClick={() => handleFilterChange(option.value)}
                        sx={{
                            bgcolor: filter === option.value && "primary.main",
                            color: filter === option.value && "white",
                            ":hover": {
                                bgcolor: "primary.main",
                                color: "white",
                            },
                        }}
                    >
                        {option.label}
                    </MenuItem>
                ))}
            </Menu>

            <CustomDateRangeDialog
                open={openDialog}
                onClose={handleDialogClose}
                dateRange={dateRange}
                setDateRange={setDateRange}
            />
        </>
    );
};

const SalesDetailsDialogBox = ({ open, onClose, sales }) => {
    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth={sales.length > 0 ? "md" : "sm"}
            fullWidth
        >
            <DialogTitle>Sales Details</DialogTitle>
            <DialogContent dividers>
                {sales.length > 0 ? (
                    <SalesDetailsTable sales={sales} />
                ) : (
                    <Typography
                        variant="body1"
                        color="error.main"
                        textAlign="center"
                        sx={{ py: 2 }}
                    >
                        No sales data found.
                    </Typography>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};

const CustomDateRangeDialog = ({ open, onClose, dateRange, setDateRange }) => {
    const handleDateChange = (index, newDate) => {
        const updatedDateRange = [...dateRange];
        updatedDateRange[index] = newDate;
        setDateRange(updatedDateRange);
    };

    // Call API when Apply button is clicked
    const handleApply = () => {
        useUpdateSearchParam(
            {
                filter: "custom",
                startDateFilter: formatDate(
                    dateRange[0]?.toISOString(),
                    "yyyy-MM-dd"
                ),
                endDateFilter: formatDate(
                    dateRange[1]?.toISOString(),
                    "yyyy-MM-dd"
                ),
            },
            "/dashboard"
        );

        onClose(); // Close dialog after applying
    };

    return (
        <Dialog
            open={open}
            onClose={(_, reason) => reason !== "backdropClick" && onClose()}
            maxWidth="md"
            fullWidth
        >
            <DialogTitle>Select Date Range</DialogTitle>
            <DialogContent dividers>
                <Grid container spacing={2} alignItems="flex-start">
                    {/* Start Date */}
                    <Grid item xs={6}>
                        <Typography variant="body1" fontWeight="bold">
                            Start Date:
                        </Typography>
                        <DateCalendar
                            value={dateRange[0]}
                            onChange={(newDate) => handleDateChange(0, newDate)}
                        />
                    </Grid>

                    {/* End Date */}
                    <Grid item xs={6}>
                        <Typography variant="body1" fontWeight="bold">
                            End Date:
                        </Typography>
                        <DateCalendar
                            value={dateRange[1]}
                            onChange={(newDate) => handleDateChange(1, newDate)}
                        />
                    </Grid>
                </Grid>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} color="error">
                    Cancel
                </Button>
                <Button onClick={handleApply} color="primary">
                    Apply
                </Button>
            </DialogActions>
        </Dialog>
    );
};

const SalesDetailsTable = ({ sales }) => {
    const columns = [
        { name: "company_name", title: "Company Name" },
        { name: "user_name", title: "User Name" },
        { name: "updated_at", title: "Date" },
    ];

    // get only company name, user name and updated at from the data
    const rows = sales.map((item) => ({
        company_name: item.company_name,
        user_name: item.user_name,
        updated_at: item.updated_at,
    }));

    return (
        <TableContainer component={Paper}>
            <Table>
                <TableHead>
                    <TableRow>
                        {columns.map((column) => (
                            <TableCell key={column.name}>
                                {column.title}
                            </TableCell>
                        ))}
                    </TableRow>
                </TableHead>
                <TableBody>
                    {rows.map((row, index) => (
                        <TableRow key={index}>
                            {columns.map((column) => (
                                <TableCell key={column.name}>
                                    {column.name === "updated_at"
                                        ? formatDateTime(row[column.name])
                                        : row[column.name]}
                                </TableCell>
                            ))}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </TableContainer>
    );
};
