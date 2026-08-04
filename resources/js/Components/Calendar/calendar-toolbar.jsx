import PropTypes from "prop-types";

import Button from "@mui/material/Button";
import IconButton from "@mui/material/IconButton";
import LinearProgress from "@mui/material/LinearProgress";
import MenuItem from "@mui/material/MenuItem";
import Stack from "@mui/material/Stack";
import Typography from "@mui/material/Typography";

import { fDate } from "@/utils/date-time-formatters";
import {
    ArticleTwoTone,
    CalendarMonthTwoTone,
    EventNoteTwoTone,
    FilterListRounded,
    KeyboardArrowDownRounded,
    KeyboardArrowLeftRounded,
    KeyboardArrowRightRounded,
    TodayTwoTone,
} from "@mui/icons-material";
import CustomPopover, { usePopover } from "./custom-popover";
import { useResponsive } from "./hooks";

// ----------------------------------------------------------------------

const VIEW_OPTIONS = [
    {
        value: "dayGridMonth",
        label: "Month",
        icon: <CalendarMonthTwoTone />,
    },
    // { value: "timeGridWeek", label: "Week", icon: <EventNoteTwoTone /> },
    // { value: "timeGridDay", label: "Day", icon: <TodayTwoTone /> },
    {
        value: "listWeek",
        label: "Agenda",
        icon: <ArticleTwoTone />,
    },
];

// ----------------------------------------------------------------------

export default function CalendarToolbar({
    date,
    view,
    loading,
    onToday,
    onNextDate,
    onPrevDate,
    onChangeView,
    onOpenFilters,
}) {
    const smUp = useResponsive("up", "sm");

    const popover = usePopover();

    const selectedItem = VIEW_OPTIONS.filter((item) => item.value === view)[0];

    return (
        <>
            <Stack
                direction="row"
                alignItems="center"
                justifyContent="space-between"
                sx={{ p: 2.5, pr: 2, position: "relative" }}
            >
                {smUp && (
                    <Button
                        size="small"
                        color="inherit"
                        onClick={popover.onOpen}
                        startIcon={selectedItem.icon}
                        endIcon={<KeyboardArrowDownRounded sx={{ ml: -0.5 }} />}
                    >
                        {selectedItem.label}
                    </Button>
                )}

                <Stack direction="row" alignItems="center" spacing={1}>
                    <IconButton onClick={onPrevDate}>
                        <KeyboardArrowLeftRounded />
                    </IconButton>

                    <Typography variant="h6">{fDate(date)}</Typography>

                    <IconButton onClick={onNextDate}>
                        <KeyboardArrowRightRounded />
                    </IconButton>
                </Stack>

                <Stack direction="row" alignItems="center" spacing={1}>
                    <Button
                        size="small"
                        color="error"
                        variant="contained"
                        onClick={onToday}
                    >
                        Today
                    </Button>

                    <IconButton onClick={onOpenFilters}>
                        <FilterListRounded />
                    </IconButton>
                </Stack>

                {loading && (
                    <LinearProgress
                        color="inherit"
                        sx={{
                            height: 2,
                            width: 1,
                            position: "absolute",
                            bottom: 0,
                            left: 0,
                        }}
                    />
                )}
            </Stack>

            <CustomPopover
                open={popover.open}
                onClose={popover.onClose}
                arrow="top-left"
                sx={{ width: 160 }}
            >
                {VIEW_OPTIONS.map((viewOption) => (
                    <MenuItem
                        key={viewOption.value}
                        selected={viewOption.value === view}
                        onClick={() => {
                            popover.onClose();
                            onChangeView(viewOption.value);
                        }}
                    >
                        {viewOption.icon}
                        {viewOption.label}
                    </MenuItem>
                ))}
            </CustomPopover>
        </>
    );
}

CalendarToolbar.propTypes = {
    date: PropTypes.object,
    loading: PropTypes.bool,
    onChangeView: PropTypes.func,
    onNextDate: PropTypes.func,
    onOpenFilters: PropTypes.func,
    onPrevDate: PropTypes.func,
    onToday: PropTypes.func,
    view: PropTypes.oneOf([
        "dayGridMonth",
        // "timeGridWeek",
        // "timeGridDay",
        "listWeek",
    ]),
};
