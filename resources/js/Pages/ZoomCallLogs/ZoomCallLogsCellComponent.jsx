import { formatDateTime } from "@/utils/date-time-formatters";
import styled from "@emotion/styled";
import {
    CallMadeRounded,
    CallMissedOutgoingRounded,
    CallReceivedRounded,
    InterpreterModeRounded,
} from "@mui/icons-material";
import { Chip as MuiChip, Tooltip } from "@mui/material";
import { green, purple } from "@mui/material/colors";

import ZoomCallLogsActions from "./CellComponent/ZoomCallLogsActions";
import ZoomUserNameComponent from "./CellComponent/ZoomUserNameComponent";
import ShowCallTranscript from "./CellComponent/ShowCallTranscript";

export default function ZoomCallLogsCellComponent({ row, column }) {
    switch (column.id) {
        case "result":
            return getResultIcon(row.direction, row.event, row.result);
        case "caller_name":
            return (
                row.caller_name && (
                    <ZoomUserNameComponent
                        name={row.caller_name}
                        email={row.caller_email}
                    />
                )
            );
        case "callee_name":
            return (
                row.calle_name && (
                    <ZoomUserNameComponent
                        name={row.calle_name}
                        email={row.calle_email}
                    />
                )
            );
        case "caller_number":
            return (
                row.caller_number && (
                    <Chip
                        label={row.caller_number}
                        size="small"
                        sx={{ borderRadius: 1 }}
                        bgcolor={purple[100]}
                        textcolor={purple[800]}
                        bghovercolor={purple[800]}
                        bgtextcolor={purple[100]}
                    />
                )
            );
        case "callee_number":
            return (
                row.callee_number && (
                    <Chip
                        label={row.callee_number}
                        size="small"
                        sx={{ borderRadius: 1 }}
                        bgcolor={purple[100]}
                        textcolor={purple[800]}
                        bghovercolor={purple[800]}
                        bgtextcolor={purple[100]}
                    />
                )
            );
        case "recording_id":
            if (row.recording_id || row.file_url) {
                return <ShowCallTranscript recordingId={row.recording_id} callId={row.call_id} />;
            }
            return null;
        case "call_duration":
            return row.call_duration && formatDuration(row.call_duration);
        case "talk_time":
            return row.talk_time && formatDuration(row.talk_time);
        case "wait_time":
            return row.wait_time && formatDuration(row.wait_time);
        case "hold_time":
            return row.hold_time && formatDuration(row.hold_time);
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
        case "answer_time":
            return (
                row.answer_time && (
                    <Chip
                        label={formatDateTime(row.answer_time)}
                        sx={{ borderRadius: 1 }}
                        size="small"
                    />
                )
            );
        case "end_time":
            return (
                row.end_time && (
                    <Chip
                        label={formatDateTime(row.end_time)}
                        sx={{ borderRadius: 1 }}
                        size="small"
                    />
                )
            );
        case "action":
            return <ZoomCallLogsActions row={row} />;
        default:
            return null;
    }
}

// Muichip - resuable component
const Chip = styled(MuiChip)(
    ({
        bgcolor = green[100],
        textcolor = green[800],
        bghovercolor = green[800],
        bgtextcolor = green[100],
    }) => ({
        fontWeight: 600,
        backgroundColor: bgcolor,
        color: textcolor,
        "&:hover": {
            backgroundColor: bghovercolor,
            color: bgtextcolor,
        },
    })
);

const getResultIcon = (direction, event, result) => {
    if (direction === "outbound") {
        if (event === "listen" || event === "whisper" || event === "shared") {
            if (result === "connected" || result === "succeeded") {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <InterpreterModeRounded color="success" />
                    </Tooltip>
                );
            } else {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <InterpreterModeRounded color="error" />
                    </Tooltip>
                );
            }
        } else {
            if (result === "connected" || result === "succeeded") {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallMadeRounded color="success" />
                    </Tooltip>
                );
            } else {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallMissedOutgoingRounded color="error" />
                    </Tooltip>
                );
            }
        }
    }

    if (direction === "inbound") {
        if (event === "shared") {
            if (result === "connected" || result === "succeeded") {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallReceivedRounded color="success" />
                    </Tooltip>
                );
            } else {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallReceivedRounded color="error" />
                    </Tooltip>
                );
            }
        } else {
            if (
                result === "connected" ||
                result === "succeeded" ||
                result === "answered"
            ) {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallReceivedRounded color="success" />
                    </Tooltip>
                );
            } else {
                return (
                    <Tooltip title={result} placement="right" arrow>
                        <CallReceivedRounded color="error" />
                    </Tooltip>
                );
            }
        }
    }
};

// "ring_to_member", "outgoing", "listen", "incoming", "whisper", "shared";

const formatDuration = (seconds) => {
    if (seconds >= 3600) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`;
    } else if (seconds >= 60) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return secs > 0 ? `${minutes}m ${secs}s` : `${minutes}m`;
    } else {
        return `${seconds}s`;
    }
};
