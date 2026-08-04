import { useTheme } from "@mui/material";
import {
    format,
    startOfWeek,
    endOfWeek,
    subWeeks,
    startOfMonth,
    endOfMonth,
    subMonths,
    subDays,
    startOfYear,
    endOfYear,
    subYears,
} from "date-fns";
import { useState } from "react";
import { DateRange } from "react-date-range";

import DateRangeRounded from "@mui/icons-material/DateRangeRounded";

import Box from "@mui/material/Box";
import Button from "@mui/material/Button";
import Divider from "@mui/material/Divider";
import List from "@mui/material/List";
import ListItem from "@mui/material/ListItem";
import ListItemButton from "@mui/material/ListItemButton";
import ListItemText from "@mui/material/ListItemText";
import Popover from "@mui/material/Popover";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";

import { matisse } from "@/theme/colors";

const _SPACE = 0.5;

const DateRangeFilter = ({ state, setState }) => {
    const theme = useTheme();

    const [anchorEl, setAnchorEl] = useState(null);
    const handleClick = (event) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);

    const open = Boolean(anchorEl);
    const id = open ? "date-range-popover" : undefined;

    const handleReset = () => {
        setState([
            {
                startDate: startOfMonth(new Date()),
                endDate: new Date(),
                key: "selection",
            },
        ]);

        handleClose();
    };

    const buttonLabel =
        state[0]?.startDate && state[0]?.endDate
            ? `${format(state[0].startDate, "dd/MM/yyyy")} to ${format(
                  state[0].endDate,
                  "dd/MM/yyyy"
              )}`
            : "Select date range";

    return (
        <>
            <Button
                fullWidth
                size="small"
                variant="outlined"
                aria-describedby={id}
                endIcon={<DateRangeRounded />}
                onClick={handleClick}
            >
                {buttonLabel}
            </Button>

            <Popover
                id={id}
                open={open}
                anchorEl={anchorEl}
                onClose={handleClose}
            >
                <Stack divider={<Divider orientation="horizontal" flexItem />}>
                    <Stack
                        direction="row"
                        divider={<Divider orientation="vertical" flexItem />}
                    >
                        <DateRange
                            editableDateInputs
                            onChange={(item) => setState([item.selection])}
                            moveRangeOnFirstSelection={false}
                            ranges={state}
                            months={1}
                            color={matisse.default}
                            direction="horizontal"
                            rangeColors={[
                                theme.palette.primary.main,
                                theme.palette.success.main,
                                theme.palette.warning.main,
                            ]}
                        />
                        <PresetFilters setState={setState} />
                    </Stack>
                    <Stack px={2} py={1} direction="column" spacing={_SPACE}>
                        <Button fullWidth onClick={handleReset}>
                            Reset date range to current month
                        </Button>
                    </Stack>
                </Stack>
            </Popover>
        </>
    );
};

export default DateRangeFilter;

const PresetFilters = ({ setState }) => {
    const [selectedDateRange, setSelectedDateRange] = useState(null);

    const presetFilters = [
        {
            label: "Today",
            range: {
                startDate: new Date(),
                endDate: new Date(),
                key: "selection",
            },
        },
        {
            label: "Yesterday",
            range: {
                startDate: subDays(new Date(), 1),
                endDate: subDays(new Date(), 1),
                key: "selection",
            },
        },
        {
            label: "This Week",
            range: {
                startDate: startOfWeek(new Date()),
                endDate: endOfWeek(new Date()),
                key: "selection",
            },
        },
        {
            label: "Previous Week",
            range: {
                startDate: startOfWeek(subWeeks(new Date(), 1)),
                endDate: endOfWeek(subWeeks(new Date(), 1)),
                key: "selection",
            },
        },
        {
            label: "This Month",
            range: {
                startDate: startOfMonth(new Date()),
                endDate: endOfMonth(new Date()),
                key: "selection",
            },
        },
        {
            label: "Previous Month",
            range: {
                startDate: startOfMonth(subMonths(new Date(), 1)),
                endDate: endOfMonth(subMonths(new Date(), 1)),
                key: "selection",
            },
        },
        {
            label: "Last 3 Months",
            range: {
                startDate: subMonths(new Date(), 3),
                endDate: new Date(),
                key: "selection",
            },
        },
        {
            label: "Last 6 Months",
            range: {
                startDate: subMonths(new Date(), 6),
                endDate: new Date(),
                key: "selection",
            },
        },
        {
            label: "This Year",
            range: {
                startDate: startOfYear(new Date()),
                endDate: endOfYear(new Date()),
                key: "selection",
            },
        },
        {
            label: "Previous Year",
            range: {
                startDate: startOfYear(subYears(new Date(), 1)),
                endDate: endOfYear(subYears(new Date(), 1)),
                key: "selection",
            },
        },
    ];

    const handleClick = (range, label) => {
        setState([range]);
        setSelectedDateRange(label);
    };

    return (
        <Box width={200}>
            <Stack justifyContent="space-between">
                <Box p={2}>
                    <Typography variant="subtitle2" color="text.secondary">
                        Preset filters
                    </Typography>
                </Box>
                <Divider orientation="horizontal" flexItem />
                <List
                    dense
                    disablePadding
                    sx={{ width: "100%", maxHeight: 366, overflowY: "auto" }}
                >
                    {presetFilters.map((filter) => (
                        <ListItem
                            disablePadding
                            divider={
                                presetFilters.indexOf(filter) !==
                                presetFilters.length - 1
                            }
                            key={filter.label}
                        >
                            <ListItemButton
                                disabled={selectedDateRange === filter.label}
                                selected={selectedDateRange === filter.label}
                                onClick={() =>
                                    handleClick(filter.range, filter.label)
                                }
                            >
                                <ListItemText primary={filter.label} />
                            </ListItemButton>
                        </ListItem>
                    ))}
                </List>
            </Stack>
        </Box>
    );
};
