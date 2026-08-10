import React, { useState } from "react";
import { 
    Button, 
    Popover, 
    Box, 
    Grid, 
    Typography, 
    List, 
    ListItem, 
    ListItemText 
} from "@mui/material";
import { DateCalendar } from "@mui/x-date-pickers";
import { format } from "date-fns";
import useUpdateSearchParam from "@/hooks/use-update-search-params";
import CalendarMonthIcon from '@mui/icons-material/CalendarMonth';

const ZoomCallDateFilter = ({ startDateParam, endDateParam }) => {
    const [anchorEl, setAnchorEl] = useState(null);
    
    // Parse initial dates from URL or default to last 7 days
    const defaultStart = startDateParam ? new Date(startDateParam) : new Date(new Date().setDate(new Date().getDate() - 6));
    const defaultEnd = endDateParam ? new Date(endDateParam) : new Date();

    const [dateRange, setDateRange] = useState([defaultStart, defaultEnd]);

    const handleOpen = (event) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);

    const handleDateChange = (index, newDate) => {
        if (!(newDate instanceof Date)) return;
        setDateRange((prev) => {
            const updatedDateRange = [...prev];
            updatedDateRange[index] = newDate;
            return updatedDateRange;
        });
    };

    const handlePresetClick = (preset) => {
        const now = new Date();
        let startDate, endDate;

        switch (preset) {
            case "Today":
                startDate = new Date(now.setHours(0, 0, 0, 0));
                endDate = new Date(now.setHours(23, 59, 59, 999));
                break;
            case "Yesterday":
                startDate = new Date(now.setDate(now.getDate() - 1));
                endDate = new Date(startDate);
                endDate.setHours(23, 59, 59, 999);
                break;
            case "Last 7 Days":
                startDate = new Date(now.setDate(now.getDate() - 6));
                endDate = new Date();
                break;
            case "Last 30 Days":
                startDate = new Date(now.setDate(now.getDate() - 29));
                endDate = new Date();
                break;
            default:
                break;
        }

        setDateRange([new Date(startDate), new Date(endDate)]);
    };

    const handleApply = () => {
        if (!dateRange[0] || !dateRange[1]) {
            return;
        }
        
        const start_date = format(new Date(dateRange[0]), "yyyy-MM-dd");
        const end_date = format(new Date(dateRange[1]), "yyyy-MM-dd");

        // Use the hook to update the URL parameters and trigger an Inertia reload
        useUpdateSearchParam({ start_date, end_date }, "/zoom-calllogs");
        handleClose();
    };

    // Determine button label based on selected range
    const btnLabel = (startDateParam && endDateParam) 
        ? `${format(defaultStart, "MMM d, yyyy")} - ${format(defaultEnd, "MMM d, yyyy")}`
        : "Last 7 Days";

    return (
        <>
            <Button 
                variant="outlined" 
                onClick={handleOpen}
                startIcon={<CalendarMonthIcon />}
                sx={{ bgcolor: 'background.paper' }}
            >
                {btnLabel}
            </Button>

            <Popover
                open={Boolean(anchorEl)}
                anchorEl={anchorEl}
                onClose={handleClose}
                anchorOrigin={{
                    vertical: "bottom",
                    horizontal: "left",
                }}
                transformOrigin={{
                    vertical: "top",
                    horizontal: "left",
                }}
                sx={{
                    "& .MuiPopover-paper": {
                        padding: 2,
                    },
                }}
            >
                <Box>
                    <Grid container spacing={2} columns={12}>
                        {/* Quick Filters */}
                        <Grid item xs={12} sm={3}>
                            <Typography variant="body1" fontWeight="bold" fontSize={14} mb={1}>
                                Quick Filters
                            </Typography>
                            <List disablePadding>
                                {["Today", "Yesterday", "Last 7 Days", "Last 30 Days"].map((preset) => (
                                    <ListItem
                                        button
                                        key={preset}
                                        onClick={() => handlePresetClick(preset)}
                                        sx={{
                                            padding: "6px 12px",
                                            mb: 0.5,
                                            borderRadius: 1,
                                            cursor: "pointer",
                                            ":hover": {
                                                bgcolor: "primary.light",
                                                color: "primary.contrastText",
                                            },
                                        }}
                                    >
                                        <ListItemText primary={preset} primaryTypographyProps={{ fontSize: 13 }} />
                                    </ListItem>
                                ))}
                            </List>
                        </Grid>

                        {/* Start Date */}
                        <Grid item xs={12} sm={4.5}>
                            <Typography variant="body1" fontWeight="bold" fontSize={14} ml={2}>
                                Start Date
                            </Typography>
                            <DateCalendar
                                value={dateRange[0]}
                                onChange={(newDate) => handleDateChange(0, newDate)}
                            />
                        </Grid>

                        {/* End Date */}
                        <Grid item xs={12} sm={4.5}>
                            <Typography variant="body1" fontWeight="bold" fontSize={14} ml={2}>
                                End Date
                            </Typography>
                            <DateCalendar
                                value={dateRange[1]}
                                onChange={(newDate) => handleDateChange(1, newDate)}
                            />
                        </Grid>
                    </Grid>

                    {/* Actions */}
                    <Box mt={2} display="flex" justifyContent="flex-end" sx={{ gap: 1 }}>
                        <Button onClick={handleClose} color="inherit">
                            Cancel
                        </Button>
                        <Button onClick={handleApply} color="primary" variant="contained">
                            Apply
                        </Button>
                    </Box>
                </Box>
            </Popover>
        </>
    );
};

export default ZoomCallDateFilter;
