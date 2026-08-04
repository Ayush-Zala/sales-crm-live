import { alpha, keyframes, styled } from "@mui/material/styles";

// Define the blinking animation using keyframes
const blinkAnimation = keyframes`
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
`;

// Add the blink animation globally in the component
export const StyledCalendar = styled("div")(({ theme }) => ({
    width: "calc(100% + 2px)",
    marginLeft: -1,
    marginBottom: -1,

    // Apply blink animation globally (for demo, can be applied conditionally later)
    "&::before": {
        content: `"${blinkAnimation}"`,
    },

    "& .fc": {
        "--fc-border-color": alpha(theme.palette.grey[500], 0.16),
        "--fc-now-indicator-color": theme.palette.error.main,
        "--fc-today-bg-color": alpha(theme.palette.grey[500], 0.08),
        "--fc-page-bg-color": theme.palette.background.default,
        "--fc-neutral-bg-color": theme.palette.background.neutral,
        "--fc-list-event-hover-bg-color": theme.palette.action.hover,
        "--fc-highlight-color": theme.palette.action.hover,
    },

    // Event
    "& .fc .fc-event": {
        borderColor: "transparent !important",
        backgroundColor: "transparent !important",
    },

    // Set background color for each event
    "& .fc .fc-event .fc-event-main": {
        padding: "2px 4px",
        borderRadius: 6,
        backgroundColor: theme.palette.common.white,
        "&:before": {
            top: 0,
            left: 0,
            width: "100%",
            content: "''",
            opacity: 0.24,
            height: "100%",
            borderRadius: 6,
            position: "absolute",
            backgroundColor: "currentColor",
            transition: theme.transitions.create(["opacity"]),
            "&:hover": {
                "&:before": {
                    opacity: 0.32,
                },
            },
        },
    },

    // Conditional styles for blinking events
    "& .fc .fc-event.blink": {
        animation: `${blinkAnimation} 1s infinite`, // Apply the blinking effect
    },

    // Event text styles
    "& .fc .fc-daygrid-event .fc-event-title": {
        overflow: "hidden",
        whiteSpace: "nowrap",
        textOverflow: "ellipsis",
    },
    "& .fc .fc-event .fc-event-time": {
        overflow: "unset",
        fontWeight: theme.typography.fontWeightBold,
    },

    // Popover
    "& .fc .fc-popover": {
        border: 0,
        overflow: "scroll",
        boxShadow: theme.shadows[1],
        borderRadius: theme.shape.borderRadius * 1.5,
        backgroundColor: theme.palette.background.paper,
    },

    // Popover header gray background
    "& .fc-popover-header": {
        position: "sticky",
        top: 0,
        zIndex: 999,
        backgroundColor: theme.palette.grey[200],
    },

    // Popover body
    "& .fc-popover-body": {
        maxHeight: "300px",
    },

    // Other existing styles...
}));
