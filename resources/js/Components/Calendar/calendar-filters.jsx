import PropTypes from "prop-types";
import { useCallback } from "react";
import orderBy from "lodash/orderBy";

import Box from "@mui/material/Box";
import Stack from "@mui/material/Stack";
import Badge from "@mui/material/Badge";
import Drawer from "@mui/material/Drawer";
import Divider from "@mui/material/Divider";
import Tooltip from "@mui/material/Tooltip";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import ListItemText from "@mui/material/ListItemText";
import ListItemButton from "@mui/material/ListItemButton";
import { DatePicker } from "@mui/x-date-pickers/DatePicker";

// import Scrollbar from "src/components/scrollbar";
// import { ColorPicker } from "src/components/color-utils";
import { formatDateTime } from "@/utils/date-time-formatters";
import { CloseRounded, RestartAltRounded } from "@mui/icons-material";
import { Autocomplete, TextField } from "@mui/material";
import { ColorPicker } from "./color-utils";
import { DrawerHeader } from "@/theme/styles";
import { useState } from "react";
import debounce from "lodash.debounce";
import useUpdateSearchParam from "@/hooks/use-update-search-params";
import { useEffect } from "react";
import { hasPermission, hasRole } from "@/utils/AccessManager";
import { usePage } from "@inertiajs/react";

// ----------------------------------------------------------------------

export default function CalendarFilters({
    open,
    onClose,
    //
    filters,
    onFilters,
    //
    canReset,
    onResetFilters,
    //
    dateError,
    //
    events,
    // colorOptions,
    onClickEvent,
}) {
    const [users, setUsers] = useState([]);
    const [selectedUser, setSelectedUser] = useState(null);
    const [loading, setLoading] = useState(false);

    const { auth } = usePage().props;
    const isAdminOrBDM = hasRole(auth, [
        "Admin",
        "Business Development Manager",
    ]);

    // Extract user ID from query parameters
    const getUserIdFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        return params.get("user"); // Adjust 'user' to match your query param name
    };

    // Fetch users for Autocomplete with debounce
    const fetchUsers = async (searchTerm) => {
        if (!searchTerm) return; // Don't fetch if search term is empty
        setLoading(true);
        try {
            fetch(route("calendar.searchusers", { user: searchTerm }))
                .then((response) => {
                    return response.json();
                })
                .then((data) => {
                    setUsers(data);
                });
        } catch (error) {
            console.error("Error fetching users:", error);
        } finally {
            setLoading(false);
        }
    };

    // Debounced search to avoid making API calls on every keystroke
    const debouncedFetchUsers = useCallback(
        debounce((searchTerm) => fetchUsers(searchTerm), 500), // Adjust the delay as needed
        []
    );

    useEffect(() => {
        const userId = getUserIdFromUrl();

        if (userId) {
            // Find the user with the matching ID from the users list
            const user = users.find((user) => user.id === parseInt(userId));
            if (user) {
                setSelectedUser(user); // Set the user if found
            }
        }
    }, [users]);

    const handleFilterUser = useCallback(
        (event, value) => {
            if (value) {
                setSelectedUser(value);
                onFilters("userId", value.id); // Pass the selected user's ID
                useUpdateSearchParam({ user: value.id }, "/events"); // Update the URL with the selected user's ID
            } else {
                setSelectedUser(null);
                onFilters("userId", null); // Reset the user filter if nothing is selected
                useUpdateSearchParam({ user: null }, "/events"); // Remove the user ID from the URL
            }
        },
        [onFilters]
    );

    const handleInputChange = (event, newInputValue) => {
        debouncedFetchUsers(newInputValue); // Fetch users only when the user types
    };

    // const handleFilterColors = useCallback(
    //     (newValue) => {
    //         onFilters("colors", newValue);
    //     },
    //     [onFilters]
    // );

    const handleFilterStartDate = useCallback(
        (newValue) => {
            onFilters("startDate", newValue);
        },
        [onFilters]
    );

    const handleFilterEndDate = useCallback(
        (newValue) => {
            onFilters("endDate", newValue);
        },
        [onFilters]
    );

    const renderHead = (
        <DrawerHeader>
            <Typography variant="h6" sx={{ flexGrow: 1 }}>
                Filters
            </Typography>

            <Tooltip title="Reset">
                <IconButton onClick={onResetFilters}>
                    <Badge color="error" variant="dot" invisible={!canReset}>
                        <RestartAltRounded />
                    </Badge>
                </IconButton>
            </Tooltip>

            <IconButton onClick={onClose}>
                <CloseRounded />
            </IconButton>
        </DrawerHeader>
    );

    // const renderColors = (
    //     <Stack spacing={1} sx={{ my: 3, px: 2.5 }}>
    //         <Typography variant="subtitle2">Colors</Typography>
    //         <ColorPicker
    //             colors={colorOptions}
    //             selected={filters.colors}
    //             onSelectColor={handleFilterColors}
    //         />
    //     </Stack>
    // );

    const renderDateRange = (
        <Stack spacing={1.5} sx={{ my: 2.5, px: 2.5 }}>
            <Typography variant="subtitle2">Range</Typography>

            <Stack spacing={2}>
                <DatePicker
                    label="Start date"
                    value={filters.startDate}
                    onChange={handleFilterStartDate}
                    renderInput={(params) => <TextField {...params} />}
                />

                <DatePicker
                    label="End date"
                    value={filters.endDate}
                    onChange={handleFilterEndDate}
                    // slotProps={{
                    //     textField: {
                    //         error: dateError,
                    //         helperText:
                    //             dateError &&
                    //             "End date must be later than start date",
                    //     },
                    // }}
                    renderInput={(params) => <TextField {...params} />}
                />
            </Stack>
        </Stack>
    );

    const renderUserSearch = (
        <Stack spacing={1} sx={{ my: 3, px: 2.5 }}>
            <Typography variant="subtitle2">Search User</Typography>
            <Autocomplete
                value={selectedUser}
                loading={loading}
                options={users}
                getOptionLabel={(option) => option.name}
                onChange={handleFilterUser}
                onInputChange={handleInputChange}
                renderInput={(params) => (
                    <TextField {...params} label="Search Users" />
                )}
            />
        </Stack>
    );

    const renderEvents = (
        <>
            <Typography variant="subtitle2" sx={{ px: 2.5, mb: 1 }}>
                Events ({events.length})
            </Typography>

            {/* <Scrollbar sx={{ height: 1 }}> */}
            {orderBy(events, ["end"], ["desc"]).map((event) => (
                <ListItemButton
                    key={event.id}
                    onClick={() => onClickEvent(`${event.id}`)}
                    sx={{
                        py: 1.5,
                        borderBottom: (theme) =>
                            `dashed 1px ${theme.palette.divider}`,
                    }}
                >
                    <Box
                        sx={{
                            top: 16,
                            left: 0,
                            width: 0,
                            height: 0,
                            position: "absolute",
                            borderRight: "10px solid transparent",
                            borderTop: `10px solid ${event.color}`,
                        }}
                    />

                    <ListItemText
                        disableTypography
                        primary={
                            <Typography
                                variant="subtitle2"
                                sx={{ fontSize: 13, mt: 0.5 }}
                            >
                                {event.title}
                            </Typography>
                        }
                        secondary={
                            <Typography
                                variant="caption"
                                component="div"
                                sx={{
                                    fontSize: 11,
                                    color: "text.disabled",
                                }}
                            >
                                {event.allDay ? (
                                    formatDateTime(event.start, "dd MMM yy")
                                ) : (
                                    <>
                                        {`${formatDateTime(
                                            event.start,
                                            "dd MMM yy p"
                                        )} - ${formatDateTime(
                                            event.end,
                                            "dd MMM yy p"
                                        )}`}
                                    </>
                                )}
                            </Typography>
                        }
                        sx={{
                            display: "flex",
                            flexDirection: "column-reverse",
                        }}
                    />
                </ListItemButton>
            ))}
            {/* </Scrollbar> */}
        </>
    );

    return (
        <Drawer
            anchor="right"
            open={open}
            onClose={onClose}
            slotProps={{
                backdrop: { invisible: true },
            }}
            sx={{
                zIndex: 1202,
            }}
            PaperProps={{
                sx: { width: 320 },
            }}
        >
            {renderHead}

            <Divider />

            {/* {renderColors} */}

            {renderDateRange}

            {isAdminOrBDM && renderUserSearch}

            {renderEvents}
        </Drawer>
    );
}

CalendarFilters.propTypes = {
    canReset: PropTypes.bool,
    colorOptions: PropTypes.arrayOf(PropTypes.string),
    dateError: PropTypes.bool,
    events: PropTypes.array,
    filters: PropTypes.object,
    onClickEvent: PropTypes.func,
    onClose: PropTypes.func,
    onFilters: PropTypes.func,
    onResetFilters: PropTypes.func,
    open: PropTypes.bool,
};
