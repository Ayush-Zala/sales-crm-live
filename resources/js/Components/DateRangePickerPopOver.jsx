import { hasRole } from "@/utils/AccessManager";
import { yupResolver } from "@hookform/resolvers/yup";
import { usePage } from "@inertiajs/react";
import { LoadingButton } from "@mui/lab";
import {
    Box,
    Button,
    Grid,
    List,
    ListItem,
    ListItemText,
    Popover,
    Typography,
} from "@mui/material";
import { DateCalendar } from "@mui/x-date-pickers";
import { format } from "date-fns";
import { useCallback, useMemo, useState } from "react";
import { AutocompleteElement, useForm } from "react-hook-form-mui";
import toast from "react-hot-toast";
import * as yup from "yup";

const DateRangePickerWithFilters = ({
    apiRoute,
    setData,
    managerOptions,
    userList,
}) => {
    const { auth } = usePage().props;
    const [anchorEl, setAnchorEl] = useState(null);
    const [dateRange, setDateRange] = useState([
        new Date(new Date().setDate(new Date().getDate() - 7)),
        new Date(),
    ]);
    const [selectedFilter, setSelectedFilter] = useState("Filter : Last Week");

    const [employeeOptions, setEmployeeOptions] = useState(userList);

    const isAdmin = hasRole(auth, "Admin");

    const managerOptionsArr = useMemo(() => {
        if (!managerOptions) return isAdmin ? [{ value: "all", label: "All" }] : [];
        const options = managerOptions.map((manager) => ({
            value: manager.id,
            label: manager.name,
        }));

        return isAdmin ? [{ value: "all", label: "All" }, ...options] : options;
    }, [managerOptions, isAdmin]);

    const defaultValues = {
        manager: isAdmin
            ? { value: "all", label: "All" }
            : managerOptions
            ? {
                  value: managerOptions[0].id,
                  label: managerOptions[0].name,
              }
            : null,
        user: null,
    };

    const schema = yup.object().shape({
        manager: yup.object().required("Manager is required"),
        user: yup.object().nullable(),
    });

    const { control, watch, setValue, handleSubmit } = useForm({
        defaultValues,
        resolver: yupResolver(schema),
    });

    const handleOpen = (event) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);

    const handleDateChange = (index, newDate) => {
        if (!(newDate instanceof Date)) return; // Ensure newDate is valid

        // const updatedDateRange = [...dateRange];
        // updatedDateRange[index] = newDate;

        // setDateRange(updatedDateRange);

        setDateRange((prev) => {
            const updatedDateRange = [...prev];
            updatedDateRange[index] = newDate;
            return updatedDateRange;
        });

        if (updatedDateRange[0] && updatedDateRange[1]) {
            const formattedDate = `${updatedDateRange[0].toDateString()} - ${updatedDateRange[1].toDateString()}`;
            setSelectedFilter(`Filter : ${formattedDate}`);
        }
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
            case "Last Week":
                startDate = new Date(now.setDate(now.getDate() - 7));
                endDate = new Date();
                break;
            default:
                break;
        }

        setDateRange([new Date(startDate), new Date(endDate)]);
        setSelectedFilter(`Filter : ${preset}`);
    };

    const handleApply = () => {
        if (!dateRange[0] || !dateRange[1]) {
            toast.error("Please select both start and end date");
            return;
        }

        if (dateRange[0] > dateRange[1]) {
            toast.error("Start date should be less than end date");
            return;
        }

        const startDate = format(new Date(dateRange[0]), "yyyy-MM-dd");
        const endDate = format(new Date(dateRange[1]), "yyyy-MM-dd");

        fetch(
            route(apiRoute, {
                startDate,
                endDate,
                manager: watch("manager").value,
                user: watch("user") ? watch("user").value : null,
            })
        )
            .then((response) => response.json())
            .then((res) => {
                if (!res.error) {
                    setData(res.data);
                    handleClose(); // Close popover after applying
                }
            })
            .catch((error) => {
                console.log("Error", error);
                toast.error("An error occurred. Please try again later.");
            });
    };

    return (
        <Box>
            <Grid
                container
                columns={12}
                spacing={2}
                sx={{ alignItems: "center", justifyContent: "flex-start" }}
            >
                <Grid item>
                    {/* Button to open Popover */}
                    <Button variant="outlined" onClick={handleOpen}>
                        {selectedFilter}
                    </Button>
                </Grid>

                <Grid item xs={2}>
                    <AutocompleteElement
                        control={control}
                        name="manager"
                        label="Manager"
                        options={managerOptionsArr}
                        SelectProps={{
                            MenuProps: {
                                slotProps: {
                                    paper: {
                                        style: {
                                            maxHeight: 280,
                                        },
                                    },
                                },
                            },
                        }}
                        autocompleteProps={{
                            isOptionEqualToValue: (option, value) =>
                                option.value === value.value,
                            onChange: (_, value) => {
                                setValue("user", null);
                                if (value.value !== "all") {
                                    setEmployeeOptions(
                                        managerOptions
                                            .find(
                                                (manager) =>
                                                    manager.id === value.value
                                            )
                                            .team.map((employee) => ({
                                                value: employee.id,
                                                label: employee.name,
                                            }))
                                    );
                                } else {
                                    // Flatten all teams from all managers into one list
                                    const allUsers = managerOptions
                                        ? managerOptions.flatMap((manager) =>
                                              (manager.team || []).map((employee) => ({
                                                  value: employee.id,
                                                  label: employee.name,
                                              }))
                                          )
                                        : userList;
                                    setEmployeeOptions(allUsers);
                                }
                                handleSubmit(handleApply)();
                            },
                        }}
                    />
                </Grid>

                <Grid item xs={2}>
                    <AutocompleteElement
                        control={control}
                        name="user"
                        label="User"
                        options={employeeOptions}
                        SelectProps={{
                            MenuProps: {
                                slotProps: {
                                    paper: {
                                        style: {
                                            maxHeight: 280,
                                        },
                                    },
                                },
                            },
                        }}
                        autocompleteProps={{
                            isOptionEqualToValue: (option, value) =>
                                option.id === value.id,
                            onChange: handleSubmit(handleApply),
                        }}
                    />
                </Grid>
            </Grid>

            {/* Popover */}
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
                        // width: "550px", // Reduce width
                        // height: "auto", // Set height to auto for smaller height
                        padding: "10px", // Add padding to give space
                    },
                }}
            >
                <Box>
                    <Grid container spacing={1} columns={12}>
                        {/* Quick Filters */}
                        <Grid item xs={2}>
                            <Typography
                                variant="body1"
                                fontWeight="bold"
                                fontSize={14}
                            >
                                Quick Filters
                            </Typography>
                            <List>
                                {["Today", "Yesterday", "Last Week"].map(
                                    (preset) => (
                                        <ListItem
                                            button
                                            key={preset}
                                            onClick={() =>
                                                handlePresetClick(preset)
                                            }
                                            sx={{
                                                padding: "3px",
                                                cursor: "pointer",
                                                bgcolor:
                                                    `Filter : ${preset}` ===
                                                    selectedFilter
                                                        ? "primary.main"
                                                        : "transparent",
                                                color:
                                                    `Filter : ${preset}` ===
                                                    selectedFilter
                                                        ? "primary.contrastText"
                                                        : "inherit",
                                                ":hover": {
                                                    bgcolor: "primary.main",
                                                    color: "primary.contrastText",
                                                },
                                            }}
                                        >
                                            <ListItemText
                                                primary={preset}
                                                sx={{ fontSize: 12 }}
                                            />
                                        </ListItem>
                                    )
                                )}
                            </List>
                        </Grid>

                        {/* Start Date */}
                        <Grid item xs={5}>
                            <Typography
                                variant="body1"
                                fontWeight="bold"
                                fontSize={14}
                            >
                                Start Date
                            </Typography>
                            <DateCalendar
                                value={dateRange[0]}
                                onChange={(newDate) =>
                                    handleDateChange(0, newDate)
                                }
                                sx={{ width: "100%", height: "auto" }} // Restrict calendar size
                            />
                        </Grid>

                        {/* End Date */}
                        <Grid item xs={5}>
                            <Typography
                                variant="body1"
                                fontWeight="bold"
                                fontSize={14}
                            >
                                End Date
                            </Typography>
                            <DateCalendar
                                value={dateRange[1]}
                                onChange={(newDate) =>
                                    handleDateChange(1, newDate)
                                }
                                sx={{ width: "100%", height: "auto" }} // Restrict calendar size
                            />
                        </Grid>
                    </Grid>

                    {/* Actions */}
                    <Box
                        mt={1}
                        display="flex"
                        justifyContent="flex-end"
                        sx={{ gap: 1 }}
                    >
                        <Button
                            onClick={handleClose}
                            color="error"
                            sx={{ fontSize: 12 }}
                        >
                            Cancel
                        </Button>
                        <LoadingButton
                            onClick={handleApply}
                            color="primary"
                            variant="contained"
                            sx={{ fontSize: 12 }}
                            loading={false}
                        >
                            Apply
                        </LoadingButton>
                    </Box>
                </Box>
            </Popover>
        </Box>
    );
};

export default DateRangePickerWithFilters;
