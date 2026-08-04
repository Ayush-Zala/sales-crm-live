import { formatDateTime } from "@/utils/date-time-formatters";
import styled from "@emotion/styled";
import { ArticleRounded, LinkRounded } from "@mui/icons-material";
import { IconButton, Link, Chip as MuiChip, Tooltip } from "@mui/material";
import { green, purple } from "@mui/material/colors";
import TranscriptDialog from "./cellComponents/TranscriptDialog";
import ZoomMeetingActions from "./cellComponents/ZoomMeetingActions";

export default function ZoomMeetingLogsCellComponent({ row, column }) {
    switch (column.id) {
        case "topic":
            return (
                <Tooltip title={row.topic} placement="top" arrow>
                    <span
                        style={{
                            display: "inline-block",
                            maxWidth: "250px", // Adjust width as needed
                            overflow: "hidden",
                            textOverflow: "ellipsis",
                            whiteSpace: "nowrap",
                            cursor: "pointer",
                        }}
                    >
                        {row.topic}
                    </span>
                </Tooltip>
            );
        case "user_name":
            return (
                row.user_name && (
                    <Chip
                        label={row.user_name}
                        sx={{ borderRadius: 1 }}
                        size="small"
                        bgColor={purple[100]}
                        textColor={purple[800]}
                        bgHoverColor={purple[800]}
                        bgTextColor={purple[100]}
                    />
                )
            );
        case "participants":
            let participants = [];
            try {
                const parsed = JSON.parse(row.participants);
                if (Array.isArray(parsed)) {
                    participants = parsed;
                }
            } catch (e) {
                console.error("Failed to parse participants JSON", e);
            }

            return (
                <Tooltip
                    title={
                        <div>
                            {participants.map((participant, index) => (
                                <div key={index}>{`${participant.name} ${
                                    participant.user_email &&
                                    `- ${participant.user_email}`
                                }`}</div>
                            ))}
                            {participants.length === 0 && <div>No participant details</div>}
                        </div>
                    }
                    placement="right"
                    arrow
                >
                    <Chip
                        label={`${participants.length} participants`}
                        sx={{ borderRadius: 1 }}
                        size="small"
                        bgColor={green[100]}
                        textColor={green[800]}
                        bgHoverColor={green[800]}
                        bgTextColor={green[100]}
                    />
                </Tooltip>
            );

        case "start_time":
            return (
                row.start_time && (
                    <Chip
                        label={formatDateTime(row.start_time)}
                        sx={{ borderRadius: 1 }}
                        size="small"
                    />
                )
            );
        case "transcript":
            return (
                row.transcript && (
                    <TranscriptDialog transcript={row.transcript} />
                )
            );
        case "duration":
            return `${row.duration} m`;
        case "action":
            return <ZoomMeetingActions row={row} />;
        default:
            return null;
    }
}

// Muichip - resuable component
const Chip = styled(MuiChip)(
    ({
        bgColor = green[100],
        textColor = green[800],
        bgHoverColor = green[800],
        bgTextColor = green[100],
    }) => ({
        fontWeight: 600,
        backgroundColor: bgColor,
        color: textColor,
        "&:hover": {
            backgroundColor: bgHoverColor,
            color: bgTextColor,
        },
    })
);
